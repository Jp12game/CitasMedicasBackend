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
        $user = $request->user();

        $query = Appointment::query()
            ->with(['patient', 'doctor', 'payment'])
            ->latest('date_time_begin');

        if ($user->hasRole('medico')) {
            $query->where('doctor_id', $user->id);
        }
        
        if ($request->filled('patient_id')){
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('doctor_id') && ! $user->hasRole('doctor')) {
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
        $appointment = Appointment::create($request->validated());
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita creada correctamente.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita obtenida correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update($request->validated());
        $appointment->load(['patient', 'doctor', 'payment']);

        return response()->json([
            'message' => 'Cita actualizada correctamente.',
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'data' => new AppointmentResource($appointment->fresh(['patient', 'doctor', 'payment'])),
        ]);
    }
}