# Simple client. Quick tour.

The simple client library takes Enqueue client classes and Symfony components and makes an easy to use client facade.
It reduces the boiler plate code you have to write to start using the Enqueue client features.

* [Install](#install)
* [Configure](#configure)
* [Producer message](#produce-message)
* [Consume messages](#consume-messages)

## Install

```bash
$ composer require enqueue/simple-client enqueue/amqp-ext
```

## Configure

```php
<?php
use Enqueue\SimpleClient\SimpleClient;

include __DIR__.'/vendor/autoload.php';

$client = new SimpleClient('amqp://');
```

## Produce message

```php
<?php

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$client->send('a_bar_topic', 'aMessageData');

// or an array

$client->send('a_bar_topic', ['foo', 'bar']);

// or an json serializable object
$client->send('a_bar_topic', new class() implements \JsonSerializable {
    public function jsonSerialize() {
        return ['foo', 'bar'];
    }
});
```

## Consume messages

```php
<?php

use Enqueue\Psr\PsrMessage;

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$client->bind('a_bar_topic', 'a_processor_name', function(PsrMessage $psrMessage) {
    // processing logic here
});

$client->consume();
```

## Cli commands

```php
#!/usr/bin/env php
<?php

// bin/enqueue.php

use Enqueue\Symfony\Client\ConsumeMessagesCommand;
use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Enqueue\Symfony\Client\ProduceMessageCommand;
use Enqueue\Symfony\Client\SetupBrokerCommand;
use Symfony\Component\Console\Application;

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$application = new Application();
$application->add(new SetupBrokerCommand($client->getDriver()));
$application->add(new ProduceMessageCommand($client->getProducer()));
$application->add(new QueuesCommand($client->getQueueMetaRegistry()));
$application->add(new TopicsCommand($client->getTopicMetaRegistry()));
$application->add(new ConsumeMessagesCommand(
    $client->getQueueConsumer(),
    $client->getDelegateProcessor(),
    $client->getQueueMetaRegistry(),
    $client->getDriver()
));

$application->run();
```

and run to see what is there:

```bash
$ php bin/enqueue.php 
```

or consume messages

```bash
$ php bin/enqueue.php enqueue:consume -vvv --setup-broker 
```

[back to index](../index.md)
