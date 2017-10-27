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
* [Send delayed message](#send-delayed-message)
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
$factory = new AmqpConnectionFactory();

// same as above
$factory = new AmqpConnectionFactory('amqp:');

// same as above
$factory = new AmqpConnectionFactory([]);

// connect to AMQP broker at example.com
$factory = new AmqpConnectionFactory([
    'host' => 'example.com',
    'port' => 1000,
    'vhost' => '/',
    'user' => 'user',
    'pass' => 'pass',
    'persisted' => false,
]);

// same as above but given as DSN string
$factory = new AmqpConnectionFactory('amqp://user:pass@example.com:10000/%2f');

// SSL or secure connection 
$factory = new AmqpConnectionFactory([
    'dsn' => 'amqps:',
    'ssl_cacert' => '/path/to/cacert.pem',
    'ssl_cert' => '/path/to/cert.pem',
    'ssl_key' => '/path/to/key.pem',
]);

$psrContext = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a function from there to create the context
$psrContext = \Enqueue\dsn_to_context('amqp:');
$psrContext = \Enqueue\dsn_to_context('amqp+lib:');
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

## Send delayed message

AMQP specification says nothing about message delaying hence the producer throws `DeliveryDelayNotSupportedException`. 
Though the producer (and the context) accepts a delivry delay strategy and if it is set it uses it to send delayed message.
The `enqueue/amqp-tools` package provides two RabbitMQ delay strategies, to use them you have to install that package

```php
<?php
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;

/** @var \Enqueue\AmqpExt\AmqpContext $psrContext */
/** @var \Interop\Amqp\Impl\AmqpQueue $fooQueue */

// make sure you run "composer require enqueue/amqp-tools".

$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()
    ->setDelayStrategy(new RabbitMqDlxDelayStrategy())
    ->setDeliveryDelay(5000) // 5 sec
    
    ->send($fooQueue, $message)
;
````

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