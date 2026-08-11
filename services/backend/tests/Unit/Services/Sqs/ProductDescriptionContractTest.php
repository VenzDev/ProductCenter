<?php

use App\Services\Sqs\Data\ProductDescriptionRequestData;
use App\Services\Sqs\SqsQueue;
use Opis\JsonSchema\Validator;

// Checks that the DTO's wire format still matches contracts/product-description-requested.schema.json
// — the spec the AI service will implement against once it exists. Kept as a test rather than
// runtime validation on the publish path: with a single, fully-typed producer, PHPStan/Pest
// already guarantee the shape, so a schema check only earns its keep in CI for now.
function assertMatchesProductDescriptionContract(ProductDescriptionRequestData $data): void
{
    $schema = file_get_contents(SqsQueue::ProductDescriptionRequested->contractPath());
    $result = (new Validator)->validate(json_decode(json_encode($data)), $schema);

    expect($result->isValid())->toBeTrue((string) $result);
}

test('a full product description request matches the contract', function () {
    assertMatchesProductDescriptionContract(new ProductDescriptionRequestData(
        productId: 1,
        locale: 'pl',
        name: ['en' => 'Widget', 'pl' => 'Gadżet'],
        attributes: ['weight_kg' => 1.2],
    ));
});

test('a request with null attributes matches the contract', function () {
    assertMatchesProductDescriptionContract(new ProductDescriptionRequestData(
        productId: 1,
        locale: 'en',
        name: ['en' => 'Widget'],
        attributes: null,
    ));
});
