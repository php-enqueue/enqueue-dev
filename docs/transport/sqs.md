<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

[back to index](../index.md)
