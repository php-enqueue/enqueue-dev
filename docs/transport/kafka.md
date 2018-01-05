# Kafka transport

The transport uses [Kafka](https://kafka.apache.org/) streaming platform as a MQ broker.

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)
* [Serialize message](#serialize-message)
* [Chnage offset](#change-offset)

## Installation

```bash
$ composer require enqueue/rdkafka
```

## Create context

```php
<?php
use Enqueue\RdKafka\RdKafkaConnectionFactory;

// connects to localhost:9092
$connectionFactory = new RdKafkaConnectionFactory();

// same as above
$connectionFactory = new RdKafkaConnectionFactory('kafka:');

// same as above
$connectionFactory = new RdKafkaConnectionFactory([]);

// connect to Kafka broker at example.com:1000 plus custom options
$connectionFactory = new RdKafkaConnectionFactory([ 
    'global' => [
        'group.id' => uniqid('', true),
        'metadata.broker.list' => 'example.com:1000',
        'enable.auto.commit' => 'false',
    ],
    'topic' => [
        'auto.offset.reset' => 'beginning',
    ],
]);

$psrContext = $connectionFactory->createContext();

// if you have enqueue/enqueue library installed you can use a function from there to create the context
$psrContext = \Enqueue\dsn_to_context('kafka:');
```

## Send message to topic 

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooTopic = $psrContext->createTopic('foo');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$message = $psrContext->createMessage('Hello world!');

$fooQueue = $psrContext->createQueue('foo');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');

$consumer = $psrContext->createConsumer($fooQueue);

// Enable async commit to gain better performance. 
//$consumer->setCommitAsync(true);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Serialize message

By default the transport serializes messages to json format but you might want to use another format such as [Apache Avro](https://avro.apache.org/docs/1.2.0/).
For that you have to implement Serializer interface and set it to the context, producer or consumer. 
If a serializer set to context it will be injected to all consumers and producers created by the context.

```php
<?php
use Enqueue\RdKafka\Serializer;
use Enqueue\RdKafka\RdKafkaMessage;

class FooSerializer implements Serializer
{
    public function toMessage($string) {}
    
    public function toString(RdKafkaMessage $message) {}
}

/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$psrContext->setSerializer(new FooSerializer());
```

## Change offset

By default consumers starts from the beginning of the topic and updates the offset while you are processing messages.
There is an ability to change the current offset.

```php
<?php
/** @var \Enqueue\RdKafka\RdKafkaContext $psrContext */

$fooQueue = $psrContext->createQueue('foo');

$consumer = $psrContext->createConsumer($fooQueue);
$consumer->setOffset(123);

$message = $consumer->receive(2000);
```

[back to index](index.md)