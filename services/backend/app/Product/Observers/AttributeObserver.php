<?php

declare(strict_types=1);

namespace App\Product\Observers;

use App\Product\Support\AttributeDefinitions;

class AttributeObserver
{
    public function saved(): void
    {
        AttributeDefinitions::forget();
    }

    public function deleted(): void
    {
        AttributeDefinitions::forget();
    }
}
