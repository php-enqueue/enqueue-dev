<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

First, you have to configure a transport layer and set one to be default.

```yaml
# app/config/config.yml

enqueue:
    default:
        transport: "amqp:"
        client: ~
```

Once you configured everything you can start producing messages:

```php
<?php
use Enqueue\Client\ProducerInterface;

/** @var ProducerInterface $producer **/
$producer = $container->get(ProducerInterface::class);


// send event to many consumers
$producer->sendEvent('aFooTopic', 'Something has happened');

// send command to ONE consumer
$producer->sendCommand('aProcessorName', 'Something has happened');
```

To consume messages you have to first create a message processor:

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

Register it as a container service and subscribe to the topic:

```yaml
foo_message_processor:
    class: 'FooProcessor'
    tags:
        - { name: 'enqueue.topic_subscriber' }
```

Now you can start consuming messages:

```bash
$ ./bin/console enqueue:consume --setup-broker -vvv
```

_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._


[back to index](../index.md)
