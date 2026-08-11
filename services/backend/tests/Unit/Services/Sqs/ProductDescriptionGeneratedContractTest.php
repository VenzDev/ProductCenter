<?php

use App\Services\Sqs\SqsQueue;
use Opis\JsonSchema\Validator;

// Checks contracts/product-description-generated.schema.json against sample payloads —
// unlike the requested-side contract, this one IS enforced at runtime too (see
// ProductDescriptionGeneratedConsumer), since the producer (the AI service) isn't a
// same-repo, fully-typed PHP class the way ProductDescriptionRequestData is.
function assertMatchesProductDescriptionGeneratedContract(array $payload): bool
{
    $schema = file_get_contents(SqsQueue::ProductDescriptionGenerated->contractPath());

    return (new Validator)->validate(json_decode(json_encode($payload)), $schema)->isValid();
}

test('a valid generated description matches the contract', function () {
    expect(assertMatchesProductDescriptionGeneratedContract([
        'product_id' => 7,
        'locale' => 'pl',
        'description' => 'Świetny gadżet.',
    ]))->toBeTrue();
});

test('a missing required field fails the contract', function () {
    expect(assertMatchesProductDescriptionGeneratedContract([
        'locale' => 'pl',
        'description' => 'Świetny gadżet.',
    ]))->toBeFalse();
});

test('an unsupported locale fails the contract', function () {
    expect(assertMatchesProductDescriptionGeneratedContract([
        'product_id' => 7,
        'locale' => 'de',
        'description' => 'Ein tolles Gadget.',
    ]))->toBeFalse();
});

test('an empty description fails the contract', function () {
    expect(assertMatchesProductDescriptionGeneratedContract([
        'product_id' => 7,
        'locale' => 'pl',
        'description' => '',
    ]))->toBeFalse();
});
