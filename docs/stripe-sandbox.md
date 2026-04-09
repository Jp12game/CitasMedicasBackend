# Stripe sandbox

Este proyecto ya soporta el flujo de pago para pacientes con Stripe en modo test.

## Variables requeridas

- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
- `MAIL_MAILER=resend`
- `RESEND_API_KEY`
- `MAIL_FROM_ADDRESS`

## Flujo para pacientes

1. El paciente agenda una cita.
2. El frontend llama `POST /api/v1/payments/create-intent`.
3. La API responde con `client_secret` y deja el pago en estado `pending`.
4. En integracion real, el frontend confirma el pago con Stripe usando el `client_secret`.
5. Stripe envia el webhook a `POST /api/stripe/webhook`.
6. La API valida la firma del webhook y marca el pago como `paid` o `failed`.

## Simulacion local

Para pruebas locales o QA sin frontend Stripe completo:

1. Cree el PaymentIntent con `POST /api/v1/payments/create-intent`.
2. Como el mismo paciente autenticado, llame:

```http
POST /api/v1/payments/simulate
Authorization: Bearer <token>
Content-Type: application/json

{
  "payment_intent_id": "pi_xxx",
  "payment_method": "pm_card_visa"
}
```

Si Stripe devuelve `succeeded`, la API:

- marca el pago como `paid`
- marca la cita como `completed`
- envia el correo `PaymentSuccessful`

## Webhook local con Stripe CLI

Para probar el webhook firmado real:

```bash
stripe listen --forward-to http://localhost/api/stripe/webhook
```

Luego copie el `whsec_...` entregado por Stripe CLI en `STRIPE_WEBHOOK_SECRET`.

## Notas

- El endpoint `/api/v1/payments/simulate` responde `404` en produccion.
- El webhook rechaza requests con firma invalida con `400`.
- El endpoint de simulacion esta pensado para tarjetas de prueba como `pm_card_visa`.
