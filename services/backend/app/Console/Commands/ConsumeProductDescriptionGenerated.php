<?php

namespace App\Console\Commands;

use App\Services\Sqs\ProductDescriptionGeneratedConsumer;
use App\Services\Sqs\SqsQueue;
use Aws\Sqs\SqsClient;
use Illuminate\Console\Command;
use Throwable;

class ConsumeProductDescriptionGenerated extends Command
{
    protected $signature = 'sqs:consume-product-descriptions';

    protected $description = 'Poll the product-description-generated SQS queue and write results onto products';

    private bool $shouldStop = false;

    public function __construct(
        private readonly SqsClient $client,
        private readonly ProductDescriptionGeneratedConsumer $consumer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queueUrl = $this->client->getQueueUrl([
            'QueueName' => SqsQueue::ProductDescriptionGenerated->queueName(),
        ])->get('QueueUrl');

        $this->trap([SIGTERM, SIGINT], function (): void {
            $this->shouldStop = true;
        });

        $this->info("Listening on [{$queueUrl}]...");

        while (! $this->shouldStop) {
            $this->pollOnce($queueUrl);
        }

        return self::SUCCESS;
    }

    public function pollOnce(string $queueUrl): void
    {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $queueUrl,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => 20,
        ]);

        foreach ($result->get('Messages') ?? [] as $message) {
            try {
                $shouldDelete = $this->consumer->consume($message['Body']);
            } catch (Throwable $e) {
                $this->error("Unexpected error processing message: {$e->getMessage()}");
                $shouldDelete = false;
            }

            if ($shouldDelete) {
                $this->client->deleteMessage([
                    'QueueUrl' => $queueUrl,
                    'ReceiptHandle' => $message['ReceiptHandle'],
                ]);
            }
        }
    }
}
