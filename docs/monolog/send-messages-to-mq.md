---
layout: default
nav_exclude: true
---
{% include support.md %}

# Enqueue Monolog Handlers

The package provides handlers for [Monolog](https://github.com/Seldaek/monolog).
These handler allows to send logs to MQ using any [queue-interop](https://github.com/queue-interop/queue-interop) compatible transports.

## Installation

You have to install monolog itself, queue interop handlers and one of [the transports](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/index.md#transports).
For the simplicity we are going to install the filesystem based MQ.

```
composer require enqueue/monolog-queue-handler monolog/monolog enqueue/fs
```

## Usage

```php
<?php

use Monolog\Handler\QueueInteropHandler;
use Monolog\Logger;

require_once __DIR__.'/vendor/autoload.php';

$context = (new \Enqueue\Fs\FsConnectionFactory('file://'.__DIR__.'/queue'))->createContext();

// create a log channel
$log = new Logger('name');
$log->pushHandler(new QueueInteropHandler($context));

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

the consumer may look like this:

```php
<?php

use Enqueue\Consumption\QueueConsumer;
use Interop\Queue\Message;
use Interop\Queue\Processor;

require_once __DIR__.'/vendor/autoload.php';

$context = (new \Enqueue\Fs\FsConnectionFactory('file://'.__DIR__.'/queue'))->createContext();

$consumer = new QueueConsumer($context);
$consumer->bindCallback('log', function(Message $message) {
    echo $message->getBody().PHP_EOL;

    return Processor::ACK;
});

$consumer->consume();

```

[back to index](../index.md)
