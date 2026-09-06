<?php

declare(strict_types=1);

namespace App\Payment\Gateway;

interface PaymentGateway
{
    /**
     * @param  array<string, string>  $metadata
     */
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): PaymentIntent;

    public function parseWebhookEvent(string $payload, string $signatureHeader): PaymentWebhookEvent;
}
