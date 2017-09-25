# Google Pub Sub transport

A transport for [Google Pub Sub](https://cloud.google.com/pubsub/docs/) cloud MQ.
It uses internally official google sdk library [google/cloud-pubsub](https://packagist.org/packages/google/cloud-pubsub) 

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/gps
```

## Create context

To enable the Google Cloud Pub/Sub Emulator, set the `PUBSUB_EMULATOR_HOST` environment variable. 
There is a handy docker container [google/cloud-sdk](https://hub.docker.com/r/google/cloud-sdk/).  

```php
<?php
use Enqueue\Gps\GpsConnectionFactory;

putenv('PUBSUB_EMULATOR_HOST=http://localhost:8900');

$connectionFactory = new GpsConnectionFactory();

// save as above 
$connectionFactory = new GpsConnectionFactory('gps:');

$psrContext = $connectionFactory->createContext();
```

## Send message to topic

Before you can send message you have to declare a topic. 
The operation creates a topic on a broker side. 
Google allows messages to be sent only to topic. 

```php
<?php
/** @var \Enqueue\Gps\GpsContext $psrContext */

$fooTopic = $psrContext->createTopic('foo');
$message = $psrContext->createMessage('Hello world!');

$psrContext->declareTopic($fooTopic);

$psrContext->createProducer()->send($fooTopic, $message);
```

## Consume message:

Before you can consume message you have to subscribe a queue to the topic. 
Google does not allow consuming message from the topic directly. 

```php
<?php
/** @var \Enqueue\Gps\GpsContext $psrContext */

$fooTopic = $psrContext->createTopic('foo');
$fooQueue = $psrContext->createQueue('foo');

$psrContext->subscribe($fooTopic, $fooQueue);

$consumer = $psrContext->createConsumer($fooQueue);
$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](../index.md)