<?php

use App\Services\Sqs\Data\ProductDescriptionGeneratedData;

test('fromValidated maps a decoded message onto the DTO', function () {
    $decoded = json_decode(json_encode([
        'product_id' => 7,
        'locale' => 'pl',
        'description' => 'Świetny gadżet.',
    ]));

    $data = ProductDescriptionGeneratedData::fromValidated($decoded);

    expect($data->productId)->toBe(7);
    expect($data->locale)->toBe('pl');
    expect($data->description)->toBe('Świetny gadżet.');
});
