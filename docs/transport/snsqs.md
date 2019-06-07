---
layout: default
title: Amazon SNS-SQS
parent: Transports
nav_order: 3
---
{% include support.md %}

# Amazon SNS-SQS transport

Utilize two Amazon services [SNS-SQS](https://docs.aws.amazon.com/sns/latest/dg/sns-sqs-as-subscriber.html) to
implement [Publish-Subscribe](https://www.enterpriseintegrationpatterns.com/patterns/messaging/PublishSubscribeChannel.html)
enterprise integration pattern. As opposed to single SQS transport this adds ability to use [MessageBus](https://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html)
with enqueue.

A transport for [Amazon SQS](https://aws.amazon.com/sqs/) broker.
It uses internally official [aws sdk library](https://packagist.org/packages/aws/aws-sdk-php)

* [Installation](#installation)
* [Create context](#create-context)
* [Declare topic, queue and bind them together](#declare-topic-queue-and-bind-them-together)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)
* [Purge queue messages](#purge-queue-messages)
* [Queue from another AWS account](#queue-from-another-aws-account)

## Installation

```bash
$ composer require enqueue/snsqs
```

## Create context

```php
<?php
use Enqueue\SnsQs\SnsQsConnectionFactory;

$factory = new SnsQsConnectionFactory([
    'key' => 'aKey',
    'secret' => 'aSecret',
    'region' => 'aRegion',

    // or you can segregate options using prefixes "sns_", "sqs_"
    'key' => 'aKey',              // common option for both SNS and SQS
    'sns_region' => 'aSnsRegion', // SNS transport option
    'sqs_region' => 'aSqsRegion', // SQS transport option
]);

// same as above but given as DSN string. You may need to url encode secret if it contains special char (like +)
$factory = new SnsQsConnectionFactory('snsqs:?key=aKey&secret=aSecret&region=aRegion');

$context = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('snsqs:')->createContext();
```

## Declare topic, queue and bind them together

Declare topic, queue operation creates a topic, queue on a broker side.
Bind creates connection between topic and queue. You publish message to
the topic and topic sends message to each queue connected to the topic.


```php
<?php
/** @var \Enqueue\SnsQs\SnsQsContext $context */

$inTopic = $context->createTopic('in');
$context->declareTopic($inTopic);

$out1Queue = $context->createQueue('out1');
$context->declareQueue($out1Queue);

$out2Queue = $context->createQueue('out2');
$context->declareQueue($out2Queue);

$context->bind($inTopic, $out1Queue);
$context->bind($inTopic, $out2Queue);

// to remove topic/queue use deleteTopic/deleteQueue method
//$context->deleteTopic($inTopic);
//$context->deleteQueue($out1Queue);
//$context->unbind(inTopic, $out1Queue);
```

## Send message to topic

```php
<?php
/** @var \Enqueue\SnsQs\SnsQsContext $context */

$inTopic = $context->createTopic('in');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($inTopic, $message);
```

## Send message to queue

You can bypass topic and publish message directly to the queue

```php
<?php
/** @var \Enqueue\SnsQs\SnsQsContext $context */

$fooQueue = $context->createQueue('foo');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```


## Consume message:

```php
<?php
/** @var \Enqueue\SnsQs\SnsQsContext $context */

$out1Queue = $context->createQueue('out1');
$consumer = $context->createConsumer($out1Queue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\SnsQs\SnsQsContext $context */

$fooQueue = $context->createQueue('foo');

$context->purgeQueue($fooQueue);
```

## Queue from another AWS account

SQS allows to use queues from another account. You could set it globally for all queues via option `queue_owner_aws_account_id` or
per queue using `SnsQsQueue::setQueueOwnerAWSAccountId` method.

```php
<?php
use Enqueue\SnsQs\SnsQsConnectionFactory;

// globally for all queues
$factory = new SnsQsConnectionFactory('snsqs:?sqs_queue_owner_aws_account_id=awsAccountId');

$context = (new SnsQsConnectionFactory('snsqs:'))->createContext();

// per queue.
$queue = $context->createQueue('foo');
$queue->setQueueOwnerAWSAccountId('awsAccountId');
```

## Multi region examples

Enqueue SNSQS provides a generic multi-region support. This enables users to specify which AWS Region to send a command to by setting region on SnsQsQueue.
If not specified the default region is used.

```php
<?php
use Enqueue\SnsQs\SnsQsConnectionFactory;

$context = (new SnsQsConnectionFactory('snsqs:?region=eu-west-2'))->createContext();

$queue = $context->createQueue('foo');
$queue->setRegion('us-west-2');

// the request goes to US West (Oregon) Region
$context->declareQueue($queue);
```

[back to index](../index.md)
