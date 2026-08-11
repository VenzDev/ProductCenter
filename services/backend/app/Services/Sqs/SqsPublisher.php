<?php

namespace App\Services\Sqs;

use Aws\Sqs\SqsClient;
use JsonSerializable;

// Generic publisher shared by every SQS queue — new events just need a JsonSerializable
// payload and an SqsQueue case, not a bespoke publisher class. Message shape is checked
// against contracts/*.schema.json in tests (see tests/Feature/Sqs), not at runtime — see
// SqsQueue::contractPath().
class SqsPublisher
{
    public function __construct(private readonly SqsClient $client) {}

    public function publish(SqsQueue $queue, JsonSerializable $message): void
    {
        $queueUrl = $this->client->getQueueUrl([
            'QueueName' => $queue->queueName(),
        ])->get('QueueUrl');

        $this->client->sendMessage([
            'QueueUrl' => $queueUrl,
            'MessageBody' => json_encode($message, JSON_THROW_ON_ERROR),
        ]);
    }
}
