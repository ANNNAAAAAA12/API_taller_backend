<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\SendGridService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class PaymentController extends Controller
{
    public function showCheckout(): View
    {
        return view('checkout');
    }

    public function createPaymentIntent(Request $request, StripeService $stripeService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        try {
            $paymentIntent = $stripeService->createPaymentIntent(
                (float) $request->amount,
                $request->currency,
                $request->description,
                $request->email,
            );

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear el PaymentIntent', ['error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request, StripeService $stripeService, SendGridService $sendGridService)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        try {
            $event = $stripeService->constructWebhookEvent($payload, (string) $sigHeader);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            Log::info("¡Pago exitoso recibido vía Webhook! ID: {$paymentIntent->id}, Monto: ".($paymentIntent->amount / 100)." {$paymentIntent->currency}");

            $customerEmail = $paymentIntent->metadata->customer_email
                ?? $paymentIntent->receipt_email
                ?? null;

            if (! $customerEmail || ! filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'El pago no contiene un correo válido'], 400);
            }

            $alreadyConfirmed = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntent->id)
                ->where('status', 'succeeded')
                ->exists();

            $payment = Payment::query()->updateOrCreate(
                ['stripe_payment_intent_id' => $paymentIntent->id],
                [
                    'stripe_event_id' => $event->id,
                    'customer_email' => $customerEmail,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'status' => 'succeeded',
                    'description' => $paymentIntent->metadata->description ?? null,
                ],
            );

            if (! $alreadyConfirmed) {
                $sent = $sendGridService->sendEmail(
                    $customerEmail,
                    'Confirmación de pago CloudSpace',
                    'Tu pago por '.number_format($paymentIntent->amount / 100, 2).' '.strtoupper($paymentIntent->currency).' fue confirmado correctamente.',
                );

                if (! $sent) {
                    Log::warning('El pago fue registrado, pero SendGrid no pudo enviar la confirmación.', [
                        'payment_intent_id' => $paymentIntent->id,
                        'customer_email' => $customerEmail,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
