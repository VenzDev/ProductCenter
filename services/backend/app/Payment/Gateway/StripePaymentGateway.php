<?php

declare(strict_types=1);

namespace App\Payment\Gateway;

use Stripe\StripeClient;
use Stripe\Webhook;

final readonly class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private StripeClient $stripe,
        private string $webhookSecret,
    ) {}

    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): PaymentIntent
    {
        $intent = $this->stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => strtolower($currency),
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return new PaymentIntent($intent->id, (string) $intent->client_secret);
    }

    public function parseWebhookEvent(string $payload, string $signatureHeader): PaymentWebhookEvent
    {
        $event = Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);

        /** @var \Stripe\PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        return new PaymentWebhookEvent($event->type, $paymentIntent->id);
    }
}
