#!/bin/sh
set -e

# Runs automatically on every LocalStack start (mounted into /etc/localstack/init/ready.d/).
# LocalStack has no persistent state volume, so queues need recreating each time.

# Plain queues — no dead-letter queue needed. One name per entry in
# config/services.php's services.sqs.queues that doesn't have a consumer below.
QUEUES="product-description-requested"

for queue in $QUEUES; do
  awslocal sqs create-queue --queue-name "$queue" --region us-east-1
done

# product-description-generated: its consumer (ConsumeProductDescriptionGenerated) validates
# every message against its contract before acting — a message that fails validation can
# never succeed on retry, so without a DLQ it would be redelivered forever. maxReceiveCount=5
# gives transient failures (e.g. a brief DB outage) a few tries before giving up.
DLQ_URL=$(awslocal sqs create-queue --queue-name product-description-generated-dlq --region us-east-1 --query QueueUrl --output text)
DLQ_ARN=$(awslocal sqs get-queue-attributes --queue-url "$DLQ_URL" --attribute-names QueueArn --region us-east-1 --query Attributes.QueueArn --output text)

cat > /tmp/product-description-generated-attributes.json <<EOF
{
  "RedrivePolicy": "{\"deadLetterTargetArn\":\"${DLQ_ARN}\",\"maxReceiveCount\":\"5\"}"
}
EOF

awslocal sqs create-queue --queue-name product-description-generated --region us-east-1 \
  --attributes file:///tmp/product-description-generated-attributes.json
