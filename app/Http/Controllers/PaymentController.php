<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\StorePaymentIntentRequest;
use App\Http\Resources\PaymentResource;
use App\Mail\PaymentFailed;
use App\Mail\PaymentSuccessful;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function createIntent(
        StorePaymentIntentRequest $request,
        PaymentService $paymentService
    ): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);
        $amount = $request->integer('amount');
        $currency = strtolower((string) $request->input('currency', 'usd'));
        $intent = $paymentService->createIntent($appointment, $amount, $currency);

        $payment = Payment::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id'               => $appointment->patient_id,
                'stripe_payment_intent_id' => $intent->id,
                'status'                   => 'pending',
                'amount'                   => $amount,
                'currency'                 => $currency,
            ]
        );

        return response()->json([
            'message'       => 'PaymentIntent creado correctamente.',
            'client_secret' => $intent->client_secret,
            'payment'       => new PaymentResource($payment),
        ]);
    }

    public function confirm(
        ConfirmPaymentRequest $request,
        PaymentService $paymentService
    ): JsonResponse
    {
        $payment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)->firstOrFail();
        $intent = $paymentService->retrieveIntent($request->payment_intent_id);

        if ($intent->status !== 'succeeded') {
            return response()->json(['message' => 'El pago aún no ha sido confirmado por Stripe.'], 422);
        }

        $payment->update(['status' => 'paid']);
        $payment->appointment->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Pago confirmado correctamente.',
            'payment' => new PaymentResource($payment->fresh()),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $type = (string) $request->input('type');
        $intentId = (string) $request->input('data.object.id');

        if ($intentId === '') {
            return response()->json(['message' => 'Webhook procesado correctamente.']);
        }

        $payment = Payment::with(['patient', 'appointment'])
            ->where('stripe_payment_intent_id', $intentId)
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Webhook procesado correctamente.']);
        }

        if ($type === 'payment_intent.succeeded') {
            $payment->update(['status' => 'paid']);
            $payment->appointment?->update(['status' => 'completed']);
            $payment = $payment->fresh(['patient', 'appointment']);

            if ($payment->patient?->email) {
                Mail::to($payment->patient->email)->send(new PaymentSuccessful($payment));
            }
        }

        if ($type === 'payment_intent.payment_failed') {
            $payment->update(['status' => 'failed']);
            $payment = $payment->fresh(['patient', 'appointment']);

            if ($payment->patient?->email) {
                Mail::to($payment->patient->email)->send(new PaymentFailed($payment));
            }
        }

        return response()->json(['message' => 'Webhook procesado correctamente.']);
    }
}
