# AMQP transport

Implements [AMQP specifications](https://www.rabbitmq.com/specification.html) and implements [amqp interop](https://github.com/queue-interop/amqp-interop) interfaces.
Build on top of [php amqp lib](https://github.com/php-amqplib/php-amqplib).

* [Installation](#installation)
* [Create context](#create-context)
* [Declare topic](#declare-topic)
* [Declare queue](#decalre-queue)
* [Bind queue to topic](#bind-queue-to-topic)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send priority message](#send-priority-message)
* [Send expiration message](#send-expiration-message)
* [Consume message](#consume-message)
* [Purge queue messages](#purge-queue-messages)

## Installation

```bash
$ composer require enqueue/amqp-lib
```

## Create context

```php
<?php
use Enqueue\AmqpLib\AmqpConnectionFactory;

// connects to localhost
$connectionFactory = new AmqpConnectionFactory();

// same as above
$connectionFactory = new AmqpConnectionFactory('amqp://');

// same as above
$connectionFactory = new AmqpConnectionFactory([]);

// connect to AMQP broker at example.com
$connectionFactory = new AmqpConnectionFactory([
    'host' => 'example.com',
    'port' => 1000,
    'vhost' => '/',
    'user' => 'user',
    'pass' => 'pass',
    'persisted' => false,
]);

// same as above but given as DSN string
$connectionFactory = new AmqpConnectionFactory('amqp://user:pass@example.com:10000/%2f');

$psrContext = $connectionFactory->createContext();
```

## Declare topic.

Declare topic operation creates a topic on a broker side. 
 
```php
<?php
use Interop\Amqp\AmqpTopic;

/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */

$fooTopic = $psrContext->createTopic('foo');
$fooTopic->setType(AmqpTopic::TYPE_FANOUT);
$psrContext->declareTopic($fooTopic);

// to remove topic use delete topic method
//$psrContext->deleteTopic($fooTopic);
```

## Declare queue.

Declare queue operation creates a queue on a broker side. 
 
```php
<?php
use Interop\Amqp\AmqpQueue;

/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
$psrContext->declareQueue($fooQueue);

// to remove topic use delete queue method
//$psrContext->deleteQueue($fooQueue);
```

## Bind queue to topic

Connects a queue to the topic. So messages from that topic comes to the queue and could be processed. 

```php
<?php
use Interop\Amqp\Impl\AmqpBind;

/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */
/** @var \Interop\Amqp\Impl\AmqpTopic $fooTopic */

$psrContext->bind(new AmqpBind($fooTopic, $fooQueue));
```

## Send message to topic 

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpTopic $fooTopic */

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Send priority message

```php
<?php
/** @var \Enqueue\AmqpExt\AmqpContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');
$fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);
$fooQueue->setArguments(['x-max-priority' => 10]);
$psrContext->declareQueue($fooQueue);

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setPriority(5) // the higher priority the sooner a message gets to a consumer
    //    
    ->send($fooQueue, $message)
;
```

## Send expiration message

```php
<?php
/** @var \Enqueue\AmqpExt\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setTimeToLive(60000) // 60 sec
    //    
    ->send($fooQueue, $message)
;
```

## Consume message:

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\AmqpLib\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

$queue = $psrContext->createQueue('aQueue');

$psrContext->purgeQueue($queue);
```

[back to index](../index.md)