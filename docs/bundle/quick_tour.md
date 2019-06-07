---
layout: default
parent: "Symfony bundle"
title: Quick tour
nav_order: 1
---
{% include support.md %}

# EnqueueBundle. Quick tour.

The [EnqueueBundle](https://github.com/php-enqueue/enqueue-bundle) integrates enqueue library.
It adds easy to use [configuration layer](config_reference.md), register services, adds handy [cli commands](cli_commands.md).

## Install

```bash
$ composer require enqueue/enqueue-bundle enqueue/fs
```

_**Note**: You could various other [transports](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport)._

_**Note**: If you are looking for a way to migrate from `php-amqplib/rabbitmq-bundle` read this [article](https://blog.forma-pro.com/the-how-and-why-of-the-migration-from-rabbitmqbundle-to-enqueuebundle-6c4054135e2b)._

## Enable the Bundle

Then, enable the bundle by adding `new Enqueue\Bundle\EnqueueBundle()` to the bundles array of the registerBundles method in your project's `app/AppKernel.php` file:

```php
<?php
// src/Kernel.php
namespace App;

use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Enqueue\Bundle\EnqueueBundle(),
        );

        // ...
    }

    // ...
}
```

## Usage

First, you have to configure a transport layer.
You can optionally configure multiple transports if you want to. One of them will automatically become the default,
based on the following:
1. If there is a transport named `default`, then it will become the default.
2. First one specified otherwise.

Default transport's services will be available to you in the usual Symfony container under their respective class
interfaces (see below)

```yaml
# app/config/config.yml

enqueue:
    default:
        transport: "amqp:"
        client: ~
    some_other_transport:
        transport: "amqp:"
        client: ~
```

Once you configured everything you can start producing messages.
As stated previously, default transport services are available in container. Here we are using `ProducerInterface` to
produce message to the `default` transport.

```php
<?php
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;

/** @var ProducerInterface $producer **/
$producer = $container->get(ProducerInterface::class);

// If you want a different producer than default (for example the other specified in sample above) then use
// $producer = $container->get('enqueue.client.some_other_transport.producer');

// send event to many consumers
$producer->sendEvent('aFooTopic', 'Something has happened');
// You can also pass an instance of Enqueue\Client\Message as second argument if you need more flexibility.
$properties = [];
$headers = [];
$message = new Message('Message body', $properties, $headers);
$producer->sendEvent('aBarTopic', $message);

// send command to ONE consumer
$producer->sendCommand('aProcessorName', 'Something has happened');
```

To consume messages you have to first create a message processor.

Example below shows how to create a Processor that will receive messages from `aFooTopic` topic (and only that one).
It assumes that you're using default Symfony services configuration and this class is
[autoconfigured](https://symfony.com/doc/current/service_container.html#the-autoconfigure-option). Otherwise you'll
have to tag it manually. This is especially true if you're using multiple transports: if left autoconfigured, processor
will be attached to the default transport only.

Note: Topic in enqueue and topic on some transports (for example Kafka) are two different things.

```php
<?php
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\TopicSubscriberInterface;

class FooProcessor implements Processor, TopicSubscriberInterface
{
    public function process(Message $message, Context $session)
    {
        echo $message->getBody();

        return self::ACK;
        // return self::REJECT; // when the message is broken
        // return self::REQUEUE; // the message is fine but you want to postpone processing
    }

    public static function getSubscribedTopics()
    {
        return ['aFooTopic'];
    }
}
```

Register it as a container service. Subscribe it to the topic if you are not using autowiring.

```yaml
foo_message_processor:
    class: 'FooProcessor'
    tags:
        - { name: 'enqueue.topic_subscriber' }
        # Use the variant below to attach to a specific client
        # Also note that if you don't disable autoconfigure, above tag will be applied automatically for default client
        # - { name: 'enqueue.topic_subsciber', client: 'some_other_transport' }
```

Now you can start consuming messages:

```bash
$ ./bin/console enqueue:consume --setup-broker -vvv
```

You can select a specific client for consumption:

```bash
$ ./bin/console enqueue:consume --setup-broker --client="some_other_transport" -vvv
```


_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._


[back to index](index.md)
