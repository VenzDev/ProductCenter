<?php

declare(strict_types=1);

namespace App\Payment\Gateway;

final readonly class PaymentIntent
{
    public function __construct(
        public string $id,
        public string $clientSecret,
    ) {}
}
