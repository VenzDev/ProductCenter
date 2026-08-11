<?php

use App\Console\Commands\ConsumeProductDescriptionGenerated;
use App\Services\Sqs\ProductDescriptionGeneratedConsumer;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function commandWithOutput(SqsClient $client, ProductDescriptionGeneratedConsumer $consumer): ConsumeProductDescriptionGenerated
{
    $command = new ConsumeProductDescriptionGenerated($client, $consumer);
    // Only set when Artisan actually runs a command (which the infinite handle() loop
    // prevents testing here) — $this->error()/$this->info() need it regardless.
    $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

    return $command;
}

test('a message the consumer handles successfully is deleted from the queue', function () {
    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(new Result(['Messages' => [
            ['Body' => '{"product_id":1}', 'ReceiptHandle' => 'receipt-1'],
        ]]));
    $client->shouldReceive('deleteMessage')
        ->once()
        ->with(['QueueUrl' => 'http://localstack:4566/000000000000/product-description-generated', 'ReceiptHandle' => 'receipt-1']);

    $consumer = Mockery::mock(ProductDescriptionGeneratedConsumer::class);
    $consumer->shouldReceive('consume')->once()->with('{"product_id":1}')->andReturn(true);

    commandWithOutput($client, $consumer)
        ->pollOnce('http://localstack:4566/000000000000/product-description-generated');
});

test('a message the consumer reports as not handled is left on the queue', function () {
    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(new Result(['Messages' => [
            ['Body' => '{"product_id":1}', 'ReceiptHandle' => 'receipt-1'],
        ]]));
    // No deleteMessage expectation: Mockery fails the test if it's called anyway.

    $consumer = Mockery::mock(ProductDescriptionGeneratedConsumer::class);
    $consumer->shouldReceive('consume')->once()->andReturn(false);

    commandWithOutput($client, $consumer)
        ->pollOnce('http://localstack:4566/000000000000/product-description-generated');
});

test('a message that makes the consumer throw is left on the queue instead of crashing the poll loop', function () {
    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(new Result(['Messages' => [
            ['Body' => '{"product_id":1}', 'ReceiptHandle' => 'receipt-1'],
        ]]));
    // No deleteMessage expectation: Mockery fails the test if it's called anyway.

    $consumer = Mockery::mock(ProductDescriptionGeneratedConsumer::class);
    $consumer->shouldReceive('consume')->once()->andThrow(new RuntimeException('db is briefly down'));

    commandWithOutput($client, $consumer)
        ->pollOnce('http://localstack:4566/000000000000/product-description-generated');
});

test('an empty poll does nothing', function () {
    $client = Mockery::mock(SqsClient::class);
    $client->shouldReceive('receiveMessage')->once()->andReturn(new Result([]));

    $consumer = Mockery::mock(ProductDescriptionGeneratedConsumer::class);

    commandWithOutput($client, $consumer)
        ->pollOnce('http://localstack:4566/000000000000/product-description-generated');
});
