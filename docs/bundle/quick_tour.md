# EnqueueBundle. Quick tour.

The [EnqueueBundle](https://github.com/php-enqueue/enqueue-bundle) integrates enqueue library.
It adds easy to use [configuration layer](config_reference.md), register services, adds handy [cli commands](cli_commands.md).

## Install

```bash
$ composer require enqueue/enqueue-bundle enqueue/amqp-ext # or enqueue/amqp-bunny, enqueue/amqp-lib
```

_**Note**: You could use not only AMQP transport but any other [available](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport)._

_**Note**: If you are looking for a way to migrate from `php-amqplib/rabbitmq-bundle` read this [article](https://blog.forma-pro.com/the-how-and-why-of-the-migration-from-rabbitmqbundle-to-enqueuebundle-6c4054135e2b)._

## Enable the Bundle

Then, enable the bundle by adding `new Enqueue\Bundle\EnqueueBundle()` to the bundles array of the registerBundles method in your project's `app/AppKernel.php` file:

```php
<?php

// app/AppKernel.php

// ...
class AppKernel extends Kernel
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

First, you have to configure a transport layer and set one to be default.

```yaml
# app/config/config.yml

enqueue:
    transport:
        default: "amqp:"
    client: ~
```

Once you configured everything you can start producing messages:

```php
<?php
use Enqueue\Client\Producer;

/** @var Producer $producer **/
$producer = $container->get('enqueue.producer');


// send event to many consumers
$producer->sendEvent('aFooTopic', 'Something has happened');

// send command to ONE consumer
$producer->sendCommand('aProcessorName', 'Something has happened');
```

To consume messages you have to first create a message processor:

```php
<?php
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;

class FooProcessor implements PsrProcessor, TopicSubscriberInterface
{
    public function process(PsrMessage $message, PsrContext $session)
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

Register it as a container service and subscribe to the topic:

```yaml
foo_message_processor:
    class: 'FooProcessor'
    tags:
        - { name: 'enqueue.client.processor' }
```

Now you can start consuming messages:

```bash
$ ./app/console enqueue:consume --setup-broker
```

_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._


[back to index](../index.md)
