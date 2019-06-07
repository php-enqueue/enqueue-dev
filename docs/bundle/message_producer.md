---
layout: default
parent: "Symfony bundle"
title: Message producer
nav_order: 4
---
{% include support.md %}

# Message producer

You can choose how to send messages either using a transport directly or with the client.
Transport gives you the access to all transport specific features so you can tune things where the client provides you with easy to use abstraction.

## Transport

```php
<?php

/** @var Symfony\Component\DependencyInjection\ContainerInterface $container */

/** @var Interop\Queue\Context $context */
$context = $container->get('enqueue.transport.[transport_name].context');

$context->createProducer()->send(
    $context->createQueue('a_queue'),
    $context->createMessage('Hello there!')
);
```

## Client

The client is shipped with two types of producers. The first one sends messages immediately
where another one (it is called spool producer) collects them in memory and sends them `onTerminate` event (the response is already sent).

The producer has two types on send methods:

* `sendEvent` - Message is sent to topic and many consumers can subscribe to it. It is "fire and forget" strategy. The event could be sent to "message bus" to other applications.
* `sendCommand` - Message is to ONE exact consumer. It could be used as "fire and forget" or as RPC. The command message is always sent in scope of current application.

### Send event

```php
<?php

use Enqueue\Client\ProducerInterface;
use Enqueue\Client\SpoolProducer;

/** @var Symfony\Component\DependencyInjection\ContainerInterface $container */

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer = $container->get(ProducerInterface::class);

// message is being sent right now
$producer->sendEvent('a_topic', 'Hello there!');


/** @var \Enqueue\Client\SpoolProducer $spoolProducer */
$spoolProducer = $container->get(SpoolProducer::class);

// message is being sent on console.terminate or kernel.terminate event
$spoolProducer->sendEvent('a_topic', 'Hello there!');

// you could send queued messages manually by calling flush method
$spoolProducer->flush();
```

### Send command

```php
<?php

use Enqueue\Client\ProducerInterface;
use Enqueue\Client\SpoolProducer;

/** @var Symfony\Component\DependencyInjection\ContainerInterface $container */

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer = $container->get(ProducerInterface::class);

// message is being sent right now, we use it as RPC
$promise = $producer->sendCommand('a_processor_name', 'Hello there!', $needReply = true);

$replyMessage = $promise->receive();


/** @var \Enqueue\Client\SpoolProducer $spoolProducer */
$spoolProducer = $container->get(SpoolProducer::class);

// message is being sent on console.terminate or kernel.terminate event
$spoolProducer->sendCommand('a_processor_name', 'Hello there!');

// you could send queued messages manually by calling flush method
$spoolProducer->flush();
```

[back to index](index.md)
