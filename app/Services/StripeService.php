<?php

namespace App\Services;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeService
{
    private const SUPPORTED_CURRENCIES = ['usd', 'eur'];

    public function createPaymentIntent(float $amount, string $currency, ?string $description = null, ?string $email = null): PaymentIntent
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor que cero.');
        }

        $currency = strtolower($currency);
        if (! in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new \InvalidArgumentException('La moneda no está soportada.');
        }

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo electrónico no es válido.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        return PaymentIntent::create([
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'description' => $description ?: 'Pago por servicio CloudSpace',
                'customer_email' => $email,
            ],
            'receipt_email' => $email,
        ]);
    }

    /**
     * @throws UnexpectedValueException
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $endpointSecret = config('services.stripe.webhook_secret');

        if (! is_string($endpointSecret) || $endpointSecret === '') {
            throw new UnexpectedValueException('El secreto del webhook de Stripe no está configurado.');
        }

        return Webhook::constructEvent($payload, $signature, $endpointSecret);
    }
}
