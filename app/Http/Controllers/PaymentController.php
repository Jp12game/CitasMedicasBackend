<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\StorePaymentIntentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    public function createIntent(StorePaymentIntentRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);

        $stripe = new StripeClient(config('cashier.secret'));

        $intent = $stripe->paymentIntents->create([
            'amount'   => $request->amount,
            'currency' => $request->currency ?? 'usd',
            'metadata' => [
                'appointment_id' => $appointment->id,
                'patient_id'     => $appointment->patient_id,
            ],
        ]);

        $payment = Payment::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id'               => $appointment->patient_id,
                'stripe_payment_intent_id' => $intent->id,
                'status'                   => 'pending',
                'amount'                   => $request->amount,
                'currency'                 => $request->currency ?? 'usd',
            ]
        );

        return response()->json([
            'message'       => 'PaymentIntent creado correctamente.',
            'client_secret' => $intent->client_secret,
            'payment'       => new PaymentResource($payment),
        ], 201);
    }

    public function confirm(ConfirmPaymentRequest $request): JsonResponse
    {
        $payment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)->firstOrFail();

        $stripe = new StripeClient(config('cashier.secret'));
        $intent = $stripe->paymentIntents->retrieve($request->payment_intent_id);

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
}
