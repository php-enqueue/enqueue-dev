---
layout: default
parent: Magento 2
title: Quick tour
nav_order: 1
---
{% include support.md %}

# Magento2 EnqueueModule

The module integrates [Enqueue Client](../client/quick_tour.md) with Magento2. You can send and consume messages to different message queues such as RabbitMQ, AMQP, STOMP, Amazon SQS, Kafka, Redis, Google PubSub, Gearman, Beanstalk, Google PubSub and others. Or integrate Magento2 app with other applications or service via [Message Bus](../client/message_bus.md).
There is [a module](../magento/quick_tour.md) for Magento1 too.

## Installation

We recommend using [composer](https://getcomposer.org/) to install [magento2-enqueue](https://github.com/php-enqueue/magento-enqueue) module. To install libraries run the commands in the application root directory.

```bash
composer require "enqueue/magento2-enqueue:*@dev" "enqueue/amqp-ext"
```

Run setup:upgrade so Magento2 picks up the installed module.

```bash
php bin/magento setup:upgrade
```

## Configuration

At this stage we have configure the Enqueue extension in Magento backend.
The config is here: `Stores -> Configuration -> General -> Enqueue Message Queue`.
Here's the example of Amqp transport that connects to RabbitMQ broker on localhost:

![Сonfiguration](../images/magento2_enqueue_configuration.png)

## Publish Message

To send a message you have to take enqueue helper and call `send` method.

```php
<?php

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$enqueueManager = $objectManager->create('Enqueue\Magento2\Model\EnqueueManager');
$enqueueManager->sendEvent('a_topic', 'aMessage');

// or a command with a possible reply
$reply = $enqueueManager->sendCommand('a_topic', 'aMessage', true);

$replyMessage = $reply->receive(5000); // wait for 5 sec
```

## Message Consumption

I assume you have `acme` Magento module properly created, configured and registered.
To consume messages you have to define a processor class first:

```php
<?php
// app/code/Acme/Module/Helper/Async/Foo.php

namespace Acme\Module\Helper\Async;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class Foo implements Processor
{
    public function process(PsrMessage $message, PsrContext $context)
    {
        // do job
        // $message->getBody() -> 'payload'

        return self::ACK;         // acknowledge message
        // return self::REJECT;   // reject message
        // return self::REQUEUE;  // requeue message
    }
}
```

than subscribe it to a topic or several topics:


```xml
<!-- app/code/Acme/Module/etc/config.xml -->

<config>
  <default>
    <enqueue>
      <processors>
        <foo-processor>
          <topic>a_topic</topic>
          <helper>Acme\Module\Helper\Async\foo</helper>
        </foo-processor>
      </processors>
    </enqueue>
  </default>
</config>
```

and run message consume command:

```bash
$ php bin/magento enqueue:consume -vvv --setup-broker
```

[back to index](../index.md#magento2)
