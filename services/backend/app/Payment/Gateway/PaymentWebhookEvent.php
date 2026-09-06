<?php

declare(strict_types=1);

namespace App\Payment\Gateway;

final readonly class PaymentWebhookEvent
{
    public function __construct(
        public string $type,
        public string $paymentIntentId,
    ) {}
}
