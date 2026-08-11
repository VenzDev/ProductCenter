<?php

namespace App\Services\Sqs;

use App\Models\Product;
use App\Services\Sqs\Data\ProductDescriptionGeneratedData;
use Illuminate\Support\Facades\Log;
use Opis\JsonSchema\Validator;

class ProductDescriptionGeneratedConsumer
{
    public function __construct(private readonly Validator $validator) {}

    public function consume(string $rawBody): bool
    {
        $decoded = json_decode($rawBody);
        $schema = file_get_contents(SqsQueue::ProductDescriptionGenerated->contractPath());
        $result = $this->validator->validate($decoded, $schema);

        if (! $result->isValid()) {
            Log::warning("product-description-generated message failed contract validation: {$result}");

            return true;
        }

        $data = ProductDescriptionGeneratedData::fromValidated($decoded);
        $product = Product::find($data->productId);

        if (! $product) {
            Log::info("product-description-generated: product [{$data->productId}] no longer exists, skipping");

            return true;
        }

        $product->setTranslation('description', $data->locale, $data->description);
        $product->save();

        return true;
    }
}
