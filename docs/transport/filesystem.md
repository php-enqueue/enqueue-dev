# Filesystem transport

Use files on local filesystem as queues. 
It creates a file per queue\topic. 
A message is a line inside the file.
**Limitations** It works only in auto ack mode hence If consumer crashes the message is lost. Local by nature therefor messages are not visible on other servers.  

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Send expiration message](#send-expiration-message)
* [Consume message](#consume-message)
* [Purge queue messages](#purge-queue-messages)

## Installation

```bash
$ composer require enqueue/fs
```

## Create context

```php
<?php
use Enqueue\Fs\FsConnectionFactory;

// stores messages in /tmp/enqueue folder
$connectionFactory = new FsConnectionFactory();

// same as above
$connectionFactory = new FsConnectionFactory('file:');

// stores in custom folder
$connectionFactory = new FsConnectionFactory('/path/to/queue/dir');

// same as above
$connectionFactory = new FsConnectionFactory('file://path/to/queue/dir');

// with options
$connectionFactory = new FsConnectionFactory('file://path/to/queue/dir?pre_fetch_count=1');

// as an array
$connectionFactory = new FsConnectionFactory([
    'path' => '/path/to/queue/dir',
    'pre_fetch_count' => 1,
]);

$psrContext = $connectionFactory->createContext();
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Fs\FsContext $psrContext */

$fooTopic = $psrContext->createTopic('aTopic');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Fs\FsContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Send expiration message

```php
<?php
/** @var \Enqueue\Fs\FsContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
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
/** @var \Enqueue\Fs\FsContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

## Purge queue messages:

```php
<?php
/** @var \Enqueue\Fs\FsContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');

$psrContext->purge($fooQueue);
```

[back to index](../index.md)