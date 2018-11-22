<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

The code below shows how to use simple client with AMQP transport. There are other [supported brokers](supported_brokers.md).

```php
<?php
use Enqueue\SimpleClient\SimpleClient;

include __DIR__.'/vendor/autoload.php';

$client = new SimpleClient('amqp:');
```

## Produce message

There two types of message a client can produce: events and commands.
Events are used to notify others about something, in other words it is an implementation of [publish-subscribe pattern](https://en.wikipedia.org/wiki/Publish%E2%80%93subscribe_pattern), sometimes called "fire-and-forget" too.
With events there is no way to get a reply as a producer is not aware of any subscribed consumers.
Commands are used to request a job to be done. It is an implementation of one-to-one messaging pattern.
A producer can request a reply from the consumer though it is up to the consumer whether send it or not. 

Commands work inside the app [scope](message_examples.md#scope) where events work inside the app scope as well as on [message bus](message_bus.md) scope.      

Send event examples:
  
```php
<?php

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$client->setupBroker();

$client->sendEvent('user_updated', 'aMessageData');

// or an array

$client->sendEvent('order_price_calculated', ['foo', 'bar']);

// or an json serializable object
$client->sendEvent('user_activated', new class() implements \JsonSerializable {
    public function jsonSerialize() {
        return ['foo', 'bar'];
    }
});
```

Send command examples:
  
```php
<?php

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$client->setupBroker();

// accepts same type of arguments as sendEvent method
$client->sendCommand('calculate_statistics', 'aMessageData');

$reply = $client->sendCommand('build_category_tree', 'aMessageData', true);

$replyMessage = $reply->receive(5000); // wait for reply for 5 seconds

$replyMessage->getBody();
```

## Consume messages

```php
<?php

use Interop\Queue\Message;
use Interop\Queue\Processor;

/** @var \Enqueue\SimpleClient\SimpleClient $client */

$client->bindTopic('a_bar_topic', function(Message $psrMessage) {
    // processing logic here
    
    return Processor::ACK;
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
