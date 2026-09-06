<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Payment\Gateway\PaymentGateway;
use App\Payment\Gateway\PaymentIntent;
use App\Payment\Gateway\PaymentWebhookEvent;

class FakePaymentGateway implements PaymentGateway
{
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): PaymentIntent
    {
        return new PaymentIntent('pi_fake_123', 'pi_fake_123_secret');
    }

    /**
     * Not a real Stripe payload/signature — tests construct their own
     * {"type": ..., "payment_intent_id": ...} JSON body directly.
     */
    public function parseWebhookEvent(string $payload, string $signatureHeader): PaymentWebhookEvent
    {
        $data = json_decode($payload, true);

        return new PaymentWebhookEvent($data['type'], $data['payment_intent_id']);
    }
}
