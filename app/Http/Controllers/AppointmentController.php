<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();

        $query = Appointment::query()
            ->with(['patient', 'doctor', 'payment'])
            ->latest('date_time_begin');

        if ($user->hasRole('medico')) {
            $query->where('doctor_id', $user->id);
        } elseif ($user->hasRole('paciente')) {
            $query->whereHas('patient', fn ($patientQuery) => $patientQuery->where('email', $user->email));
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('doctor_id') && ! $user->hasRole('medico')) {
            $query->where('doctor_id', $request->integer('doctor_id'));
        }

        $appointments = $query->paginate(10);

        return response()->json([
            'message' => 'Listado de citas obtenido correctamente.',
            'data' => AppointmentResource::collection($appointments),
        ]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $appointment = Appointment::create($request->validated());
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita creada correctamente.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita obtenida correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        return $this->rescheduleAppointment(
            $appointment,
            $request->validated(),
            'Cita actualizada correctamente.'
        );
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        return $this->cancelAppointment($appointment);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();

        $query = Appointment::with(['patient', 'doctor', 'payment'])
            ->orderBy('date_time_begin', 'desc');

        if ($user->hasRole('medico')) {
            $query->where('doctor_id', $user->id);
        } elseif ($user->hasRole('paciente')) {
            $query->whereHas('patient', fn ($q) => $q->where('email', $user->email));
        }

        $appointments = $query->paginate(15);

        return response()->json([
            'message' => 'Historial de citas obtenido correctamente.',
            'data'    => AppointmentResource::collection($appointments),
        ]);
    }

    public function confirm(Appointment $appointment): JsonResponse
    {
        $this->authorize('confirm', $appointment);

        $appointment->update(['status' => 'completed']);
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita confirmada correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function reschedule(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('reschedule', $appointment);

        return $this->rescheduleAppointment(
            $appointment,
            $request->validated(),
            'Cita reagendada correctamente.'
        );
    }

    public function cancel(Appointment $appointment): JsonResponse
    {
        $this->authorize('cancel', $appointment);

        return $this->cancelAppointment($appointment);
    }

    private function rescheduleAppointment(
        Appointment $appointment,
        array $payload,
        string $message
    ): JsonResponse {
        $appointment->update([
            'doctor_id' => $payload['doctor_id'],
            'date_time_begin' => $payload['date_time_begin'],
            'date_time_end' => $payload['date_time_end'],
            'status' => 'scheduled',
        ]);
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => $message,
            'data' => new AppointmentResource($appointment),
        ]);
    }

    private function cancelAppointment(Appointment $appointment): JsonResponse
    {
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'data' => new AppointmentResource($appointment->fresh(['patient', 'doctor', 'payment'])),
        ]);
    }
}
