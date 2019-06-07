---
layout: default
nav_exclude: true
---
{% include support.md %}

# Async event dispatcher (Symfony)

The doc shows how you can setup async event dispatching in plain PHP.
If you are looking for the ways to use it in Symfony application [read this post instead](../bundle/async_events.md)

* [Installation](#installation)
* [Configuration](#configuration)
* [Dispatch event](#dispatch-event)
* [Process async events](#process-async-events)

## Installation

You need the async dispatcher library and a one of [the supported transports](../transport)

```bash
$ composer require enqueue/async-event-dispatcher enqueue/fs
```

## Configuration

```php
<?php

// config.php

use Enqueue\AsyncEventDispatcher\AsyncListener;
use Enqueue\AsyncEventDispatcher\AsyncProcessor;
use Enqueue\AsyncEventDispatcher\PhpSerializerEventTransformer;
use Enqueue\AsyncEventDispatcher\AsyncEventDispatcher;
use Enqueue\AsyncEventDispatcher\SimpleRegistry;
use Enqueue\Fs\FsConnectionFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once __DIR__.'/vendor/autoload.php';

// it could be any other queue-interop/queue-interop compatible context.
$context = (new FsConnectionFactory('file://'.__DIR__.'/queues'))->createContext();
$eventQueue = $context->createQueue('symfony_events');

$registry = new SimpleRegistry(
    ['the_event' => 'default'],
    ['default' => new PhpSerializerEventTransformer($context)]
);

$asyncListener = new AsyncListener($context, $registry, $eventQueue);

$dispatcher = new EventDispatcher();

// the listener sends even as a message through MQ
$dispatcher->addListener('the_event', $asyncListener);

$asyncDispatcher = new AsyncEventDispatcher($dispatcher, $asyncListener);

// the listener is executed on consumer side.
$asyncDispatcher->addListener('the_event', function() {
});

$asyncProcessor = new AsyncProcessor($registry, $asyncDispatcher);
```

## Dispatch event

```php
<?php

// send.php

use Symfony\Component\EventDispatcher\GenericEvent;

require_once __DIR__.'/vendor/autoload.php';

include __DIR__.'/config.php';

$dispatcher->dispatch('the_event', new GenericEvent('theSubject'));
```

## Process async events

```php
<?php

// consume.php

use Interop\Queue\Processor;

require_once __DIR__.'/vendor/autoload.php';
include __DIR__.'/config.php';

$consumer = $context->createConsumer($eventQueue);

while (true) {
    if ($message = $consumer->receive(5000)) {
        $result = $asyncProcessor->process($message, $context);

        switch ((string) $result) {
            case Processor::ACK:
                $consumer->acknowledge($message);
                break;
            case Processor::REJECT:
                $consumer->reject($message);
                break;
            case Processor::REQUEUE:
                $consumer->reject($message, true);
                break;
            default:
                throw new \LogicException('Result is not supported');
        }
    }
}
```

[back to index](../index.md)
