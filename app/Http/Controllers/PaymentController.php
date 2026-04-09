<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\SimulatePaymentRequest;
use App\Http\Requests\StorePaymentIntentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

class PaymentController extends Controller
{
    public function createIntent(
        StorePaymentIntentRequest $request,
        PaymentService $paymentService
    ): JsonResponse
    {
        $appointment = Appointment::findOrFail($request->appointment_id);
        $amount = $paymentService->calculateAmount($appointment);
        $currency = 'usd';
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

        $payment = $paymentService->markAsPaid($payment);

        return response()->json([
            'message' => 'Pago confirmado correctamente.',
            'payment' => new PaymentResource($payment),
        ]);
    }

    public function simulate(
        SimulatePaymentRequest $request,
        PaymentService $paymentService
    ): JsonResponse
    {
        if (app()->isProduction()) {
            abort(404);
        }

        $payment = Payment::with(['patient', 'appointment'])
            ->where('stripe_payment_intent_id', $request->payment_intent_id)
            ->firstOrFail();

        $intent = $paymentService->confirmIntent(
            $request->payment_intent_id,
            $request->input('payment_method', 'pm_card_visa'),
        );

        if ($intent->status !== 'succeeded') {
            return response()->json([
                'message' => 'La simulación no produjo un pago exitoso.',
                'payment' => new PaymentResource($payment->fresh()),
                'simulation' => true,
                'stripe_status' => $intent->status,
            ], 422);
        }

        $payment = $paymentService->markAsPaid($payment);

        return response()->json([
            'message' => 'Pago simulado correctamente.',
            'payment' => new PaymentResource($payment),
            'simulation' => true,
        ]);
    }

    public function webhook(Request $request, PaymentService $paymentService): JsonResponse
    {
        try {
            $event = $paymentService->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException|RuntimeException) {
            return response()->json(['message' => 'Webhook de Stripe inválido.'], 400);
        }

        $type = (string) $event->type;
        $intentId = (string) data_get($event->data, 'object.id');

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
            $paymentService->markAsPaid($payment);
        }

        if ($type === 'payment_intent.payment_failed') {
            $paymentService->markAsFailed($payment);
        }

        return response()->json(['message' => 'Webhook procesado correctamente.']);
    }
}
