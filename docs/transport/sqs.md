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

## Installation

```bash
$ composer require enqueue/sqs
```

## Create context

```php
<?php
use Enqueue\Sqs\SqsConnectionFactory;

$connectionFactory = new SqsConnectionFactory([
    'key' => 'aKey',
    'secret' => 'aSecret',
    'region' => 'aRegion',
]);

$psrContext = $connectionFactory->createContext();
```

## Declare queue.

Declare queue operation creates a queue on a broker side. 
 
```php
<?php
/** @var \Enqueue\Sqs\SqsContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$psrContext->declareQueue($fooQueue);

// to remove queue use deleteQueue method
//$psrContext->deleteQueue($fooQueue);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Send delay message

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setDeliveryDelay(60000) // 60 sec
    
    ->send($fooQueue, $message)
;
```

## Consume message:

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\Sqs\SqsContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');

$psrContext->purge($fooQueue);
```

[back to index](../index.md)