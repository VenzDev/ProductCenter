#!/bin/sh
set -e

# Runs automatically on every LocalStack start (mounted into /etc/localstack/init/ready.d/).
# LocalStack has no persistent state volume, so queues need recreating each time.
# One name per queue defined in config/services.php's services.sqs.queues — add a line to
# both when a new async event is introduced.
QUEUES="product-description-requested"

for queue in $QUEUES; do
  awslocal sqs create-queue --queue-name "$queue" --region us-east-1
done
