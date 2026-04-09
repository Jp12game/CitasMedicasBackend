<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortalAppointmentRequest;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing(['roles', 'patient']);
        $patient = $user->patient;

        $appointments = collect();
        $payments = collect();

        if ($patient) {
            $appointments = Appointment::query()
                ->with(['doctor', 'payment'])
                ->where('patient_id', $patient->id)
                ->orderByDesc('date_time_begin')
                ->get();

            $payments = Payment::query()
                ->with(['appointment.doctor'])
                ->where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->get();
        }

        $doctors = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'medico'))
            ->with(['schedules' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')])
            ->orderBy('name')
            ->get();

        $doctorAppointments = collect();

        if ($user->hasRole('medico')) {
            $doctorAppointments = Appointment::query()
                ->with(['patient', 'payment'])
                ->where('doctor_id', $user->id)
                ->orderBy('date_time_begin')
                ->limit(8)
                ->get();
        }

        return view('dashboard', [
            'user' => $user,
            'patient' => $patient,
            'appointments' => $appointments,
            'payments' => $payments,
            'doctors' => $doctors,
            'doctorAppointments' => $doctorAppointments,
            'appointmentPrice' => 5000,
            'dashboardPayment' => session('dashboard_payment'),
            'adminStats' => [
                'usuarios' => User::query()->count(),
                'pacientes' => Patient::query()->count(),
                'medicos' => User::query()
                    ->whereHas('roles', fn ($query) => $query->where('name', 'medico'))
                    ->count(),
                'citas_hoy' => Appointment::query()->whereDate('date_time_begin', today())->count(),
                'pagos_pendientes' => Payment::query()->where('status', 'pending')->count(),
            ],
            'today' => Carbon::today(),
        ]);
    }

    public function updatePatientProfile(UpdatePatientProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('paciente'), 403);

        $payload = $request->validated();
        $patient = $user->patient ?? new Patient();

        $patient->fill($payload);
        $patient->user()->associate($user);
        $patient->save();

        $user->name = $payload['name'];

        if ($user->email !== $payload['email']) {
            $user->email = $payload['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'Tu perfil de paciente se actualizó correctamente.');
    }

    public function storeAppointment(
        StorePortalAppointmentRequest $request,
        AppointmentService $appointmentService,
        PaymentService $paymentService
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user->hasRole('paciente'), 403);

        $patient = $this->ensurePatientProfile($user);
        $payload = $request->validated();

        if (! $appointmentService->isSlotAvailable(
            $payload['doctor_id'],
            $payload['date_time_begin'],
            $payload['date_time_end'],
        )) {
            throw ValidationException::withMessages([
                'date_time_begin' => 'Ese horario ya no está disponible para el doctor seleccionado.',
            ]);
        }

        DB::beginTransaction();

        try {
            $appointment = Appointment::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $payload['doctor_id'],
                'date_time_begin' => $payload['date_time_begin'],
                'date_time_end' => $payload['date_time_end'],
                'status' => 'scheduled',
            ]);

            $intent = $paymentService->createIntent($appointment);

            $payment = Payment::query()->updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'patient_id' => $patient->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'status' => 'pending',
                    'amount' => $paymentService->calculateAmount($appointment),
                    'currency' => 'usd',
                ]
            );

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'booking' => 'No fue posible abrir el pago de Stripe para esta cita. Revisa tus llaves sandbox e intenta de nuevo.',
                ]);
        }

        $appointment = $appointment->fresh(['patient', 'doctor', 'payment']);

        if ($appointment->patient?->email) {
            Mail::to($appointment->patient->email)->send(new AppointmentConfirmed($appointment));
        }

        return redirect()
            ->route('dashboard', ['focus' => 'payments'])
            ->with('status', 'Tu cita quedó apartada. Completa el pago de prueba para confirmarla.')
            ->with('dashboard_payment', [
                'payment_id' => $payment->id,
                'client_secret' => $intent->client_secret,
                'stripe_payment_intent_id' => $intent->id,
            ]);
    }

    public function simulatePayment(
        Request $request,
        Payment $payment,
        PaymentService $paymentService
    ): RedirectResponse {
        $user = $request->user();
        $payment->loadMissing(['patient', 'appointment.doctor']);

        abort_unless($user->hasRole('paciente'), 403);
        abort_unless($payment->patient?->belongsToUser($user) ?? false, 403);

        $payload = $request->validate([
            'payment_method' => 'required|string',
        ]);

        if (! $payment->stripe_payment_intent_id) {
            return back()->withErrors([
                'payment' => 'Este pago no tiene un PaymentIntent activo. Crea una cita nueva o vuelve a generar el intento desde Stripe.',
            ]);
        }

        try {
            $intent = $paymentService->confirmIntent(
                $payment->stripe_payment_intent_id,
                $payload['payment_method'],
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => 'Stripe no aceptó la simulación del pago. Revisa tu configuración sandbox.',
            ]);
        }

        if ($intent->status === 'succeeded') {
            $paymentService->markAsPaid($payment);

            return redirect()
                ->route('dashboard', ['focus' => 'payments'])
                ->with('status', 'Pago de prueba completado correctamente.');
        }

        $paymentService->markAsFailed($payment);

        return redirect()
            ->route('dashboard', ['focus' => 'payments'])
            ->withErrors([
                'payment' => 'La simulación dejó el pago en estado '.$intent->status.'.',
            ]);
    }

    private function ensurePatientProfile(User $user): Patient
    {
        return $user->patient ?? Patient::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
