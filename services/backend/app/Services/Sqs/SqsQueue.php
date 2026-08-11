<?php

namespace App\Services\Sqs;

// One case per SQS queue the backend publishes to or consumes from, so callers reference a
// queue by name (compiler-checked) instead of a raw config-key string. Add a case here plus
// a matching entry under config('services.sqs.queues') and a schema under /contracts for
// each new queue.
enum SqsQueue: string
{
    case ProductDescriptionRequested = 'product_description_requested';

    public function queueName(): string
    {
        return (string) config("services.sqs.queues.{$this->value}");
    }

    public function contractPath(): string
    {
        return match ($this) {
            self::ProductDescriptionRequested => base_path('contracts/product-description-requested.schema.json'),
        };
    }
}
