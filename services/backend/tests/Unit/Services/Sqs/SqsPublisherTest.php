<?php

use App\Services\Sqs\Data\ProductDescriptionRequestData;
use App\Services\Sqs\SqsPublisher;
use App\Services\Sqs\SqsQueue;
use Aws\Result;
use Aws\Sqs\SqsClient;

test('publish resolves the queue url for the given queue and sends the message as JSON', function () {
    config(['services.sqs.queues.product_description_requested' => 'product-description-requested']);

    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('getQueueUrl')
        ->once()
        ->with(['QueueName' => 'product-description-requested'])
        ->andReturn(new Result(['QueueUrl' => 'http://localstack:4566/000000000000/product-description-requested']));
    $client->shouldReceive('sendMessage')
        ->once()
        ->with([
            'QueueUrl' => 'http://localstack:4566/000000000000/product-description-requested',
            'MessageBody' => json_encode([
                'product_id' => 42,
                'locale' => 'pl',
                'name' => ['en' => 'Widget', 'pl' => 'Gadżet'],
                'attributes' => ['weight_kg' => 1.2],
            ]),
        ]);

    $data = new ProductDescriptionRequestData(
        productId: 42,
        locale: 'pl',
        name: ['en' => 'Widget', 'pl' => 'Gadżet'],
        attributes: ['weight_kg' => 1.2],
    );

    (new SqsPublisher($client))->publish(SqsQueue::ProductDescriptionRequested, $data);
});

test('SqsQueue::queueName reads from the matching services.sqs.queues config entry', function () {
    config(['services.sqs.queues.product_description_requested' => 'custom-queue-name']);

    expect(SqsQueue::ProductDescriptionRequested->queueName())->toBe('custom-queue-name');
});
