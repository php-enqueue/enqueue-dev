# Gearman transport

The transport uses [Gearman](http://gearman.org/) job manager. 
The transport uses [Gearman PHP extension](http://php.net/manual/en/book.gearman.php) internally.      

* [Installation](#installation)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/gearman
```


## Create context

```php
<?php
use Enqueue\Gearman\GearmanConnectionFactory;

// connects to localhost:4730
$factory = new GearmanConnectionFactory();

// same as above
$factory = new GearmanConnectionFactory('gearman://');

// connects to example host and port 5555
$factory = new GearmanConnectionFactory('gearman://example:5555');

// same as above but configured by array
$factory = new GearmanConnectionFactory([
    'host' => 'example',
    'port' => 5555
]);
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Gearman\GearmanContext $psrContext */

$fooTopic = $psrContext->createTopic('aTopic');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Gearman\GearmanContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Gearman\GearmanContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive(2000); // wait for 2 seconds

$message = $consumer->receiveNoWait(); // fetch message or return null immediately 

// process a message

$consumer->acknowledge($message);
// $consumer->reject($message);
```

[back to index](../index.md)