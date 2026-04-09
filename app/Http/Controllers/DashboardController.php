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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): View
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

        $dashboardPayment = session('dashboard_payment');
        $stripeCheckout = null;

        if ($user->hasRole('paciente') && $payments->isNotEmpty()) {
            $stripeCheckout = $this->resolveStripeCheckout(
                $request,
                $payments,
                is_array($dashboardPayment) ? $dashboardPayment : [],
                $paymentService,
            );
        }

        return view('dashboard', [
            'user' => $user,
            'patient' => $patient,
            'appointments' => $appointments,
            'payments' => $payments,
            'doctors' => $doctors,
            'doctorAppointments' => $doctorAppointments,
            'appointmentPrice' => 5000,
            'dashboardPayment' => $dashboardPayment,
            'stripeCheckout' => $stripeCheckout,
            'stripePublishableKey' => (string) config('cashier.key'),
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

        if (Patient::usesUserLinkColumn()) {
            $patient->user()->associate($user);
        }

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

    public function finalizePayment(
        Request $request,
        Payment $payment,
        PaymentService $paymentService
    ): JsonResponse {
        $user = $request->user();
        $payment->loadMissing(['patient', 'appointment.doctor']);

        abort_unless($user->hasRole('paciente'), 403);
        abort_unless($payment->patient?->belongsToUser($user) ?? false, 403);

        $payload = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        if ($payment->stripe_payment_intent_id !== $payload['payment_intent_id']) {
            return response()->json([
                'message' => 'El intento de pago no coincide con el pago seleccionado.',
            ], 422);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'El pago ya estaba confirmado.',
                'payment' => $payment,
            ]);
        }

        try {
            $intent = $paymentService->retrieveIntent($payment->stripe_payment_intent_id);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible verificar el estado del pago con Stripe.',
            ], 502);
        }

        if ($intent->status === 'succeeded') {
            $payment = $paymentService->markAsPaid($payment);

            return response()->json([
                'message' => 'Pago confirmado correctamente.',
                'payment' => $payment,
                'redirect_url' => route('dashboard', ['focus' => 'payments']),
            ]);
        }

        if (in_array($intent->status, ['requires_payment_method', 'canceled'], true)) {
            $payment = $paymentService->markAsFailed($payment);

            return response()->json([
                'message' => 'Stripe dejó el pago en estado '.$intent->status.'.',
                'payment' => $payment,
                'stripe_status' => $intent->status,
            ], 422);
        }

        return response()->json([
            'message' => 'Stripe todavía no cerró este pago.',
            'payment' => $payment->fresh(['patient', 'appointment.doctor']),
            'stripe_status' => $intent->status,
        ], 409);
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
        if ($user->patient) {
            return $user->patient;
        }

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        if (Patient::usesUserLinkColumn()) {
            $payload['user_id'] = $user->id;
        }

        return Patient::query()->create($payload);
    }

    private function resolveStripeCheckout(
        Request $request,
        $payments,
        array $dashboardPayment,
        PaymentService $paymentService
    ): ?array {
        $selectedPayment = null;
        $requestedPaymentId = $request->integer('payment');
        $flashedPaymentId = isset($dashboardPayment['payment_id']) ? (int) $dashboardPayment['payment_id'] : null;

        if ($requestedPaymentId > 0) {
            $selectedPayment = $payments->firstWhere('id', $requestedPaymentId);
        }

        if (! $selectedPayment && $flashedPaymentId) {
            $selectedPayment = $payments->firstWhere('id', $flashedPaymentId);
        }

        if (! $selectedPayment) {
            $selectedPayment = $payments->first(fn (Payment $payment) => $payment->status !== 'paid');
        }

        if (! $selectedPayment instanceof Payment) {
            return null;
        }

        $clientSecret = null;
        $error = null;

        if (
            $flashedPaymentId === (int) $selectedPayment->id
            && filled($dashboardPayment['client_secret'] ?? null)
        ) {
            $clientSecret = (string) $dashboardPayment['client_secret'];
        } elseif (
            filled($selectedPayment->stripe_payment_intent_id)
            && filled(config('cashier.secret'))
        ) {
            try {
                $intent = $paymentService->retrieveIntent($selectedPayment->stripe_payment_intent_id);
                $clientSecret = (string) ($intent->client_secret ?? '');
            } catch (Throwable $exception) {
                report($exception);
                $error = 'No fue posible recuperar el formulario de Stripe para este pago.';
            }
        } elseif ($selectedPayment->status !== 'paid') {
            $error = 'Configura las llaves de Stripe para habilitar el pago sandbox.';
        }

        return [
            'payment' => $selectedPayment,
            'payment_id' => $selectedPayment->id,
            'amount' => $selectedPayment->amount,
            'currency' => $selectedPayment->currency,
            'status' => $selectedPayment->status,
            'client_secret' => $clientSecret,
            'stripe_payment_intent_id' => $selectedPayment->stripe_payment_intent_id,
            'error' => $error,
        ];
    }
}
