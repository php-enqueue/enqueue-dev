---
layout: default
title: Amazon SQS
parent: Transports
nav_order: 3
---
{% include support.md %}

# Amazon SQS transport

A transport for [Amazon SQS](https://aws.amazon.com/sqs/) broker.
It uses internally official [aws sdk library](https://packagist.org/packages/aws/aws-sdk-php)

* [Installation](#installation)
* [Create context](#create-context)
* [Declare queue](#decalre-queue)
* [Send message to queue](#send-message-to-queue)
* [Send delay message](#send-delay-message)
* [Consume message](#consume-message)
* [Purge queue messages](#purge-queue-messages)
* [Queue from another AWS account](#queue-from-another-aws-account)

## Installation

```bash
$ composer require enqueue/sqs
```

## Create context

```php
<?php
use Enqueue\Sqs\SqsConnectionFactory;

$factory = new SqsConnectionFactory([
    'key' => 'aKey',
    'secret' => 'aSecret',
    'region' => 'aRegion',
]);

// same as above but given as DSN string. You may need to url encode secret if it contains special char (like +)
$factory = new SqsConnectionFactory('sqs:?key=aKey&secret=aSecret&region=aRegion');

$context = $factory->createContext();

// using a pre-configured client
$client = new Aws\Sqs\SqsClient([ /* ... */ ]);
$factory = new SqsConnectionFactory($client);

// if you have enqueue/enqueue library installed you can use a factory to build context from DSN
$context = (new \Enqueue\ConnectionFactoryFactory())->create('sqs:')->createContext();
```

## Declare queue.

Declare queue operation creates a queue on a broker side.

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $context */

$fooQueue = $context->createQueue('foo');
$context->declareQueue($fooQueue);

// to remove queue use deleteQueue method
//$context->deleteQueue($fooQueue);
```

## Send message to queue

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $context */

$fooQueue = $context->createQueue('foo');
$message = $context->createMessage('Hello world!');

$context->createProducer()->send($fooQueue, $message);
```

## Send delay message

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $context */

$fooQueue = $context->createQueue('foo');
$message = $context->createMessage('Hello world!');

$context->createProducer()
    ->setDeliveryDelay(60000) // 60 sec

    ->send($fooQueue, $message)
;
```

## Consume message:

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $context */

$fooQueue = $context->createQueue('foo');
$consumer = $context->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $context */

$fooQueue = $context->createQueue('foo');

$context->purgeQueue($fooQueue);
```

## Queue from another AWS account

SQS allows to use queues from another account. You could set it globally for all queues via option `queue_owner_aws_account_id` or
per queue using `SqsDestination::setQueueOwnerAWSAccountId` method.

```php
<?php
use Enqueue\Sqs\SqsConnectionFactory;

// globally for all queues
$factory = new SqsConnectionFactory('sqs:?queue_owner_aws_account_id=awsAccountId');

$context = (new SqsConnectionFactory('sqs:'))->createContext();

// per queue.
$queue = $context->createQueue('foo');
$queue->setQueueOwnerAWSAccountId('awsAccountId');
```

## Multi region examples

Enqueue SQS provides a generic multi-region support. This enables users to specify which AWS Region to send a command to by setting region on SqsDestination.
You might need it to access SQS FIFO queue because they are not available for all regions.
If not specified the default region is used.

```php
<?php
use Enqueue\Sqs\SqsConnectionFactory;

$context = (new SqsConnectionFactory('sqs:?region=eu-west-2'))->createContext();

$queue = $context->createQueue('foo');
$queue->setRegion('us-west-2');

// the request goes to US West (Oregon) Region
$context->declareQueue($queue);
```

[back to index](../index.md)
