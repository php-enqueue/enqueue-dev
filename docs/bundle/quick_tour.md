# EnqueueBundle. Quick tour.

The bundle integrates enqueue library.
It adds easy to use [configuration layer](config_reference.md), register services, adds handy [cli commands](cli_commands.md).

## Install

```bash
$ composer require enqueue/enqueue-bundle enqueue/amqp-ext
```

## Usage

First, you have to configure a transport layer and set one to be default.

```yaml
# app/config/config.yml

enqueue:
    transport:
        default: 'amqp'
        amqp:
            host: 'localhost'
            port: 5672
            login: 'guest'
            password: 'guest'
            vhost: '/'
    client: ~
```

Once you configured everything you can start producing messages:

```php
<?php
use Enqueue\Client\MessageProducer;

/** @var MessageProducer $messageProducer **/
$messageProducer = $container->get('enqueue.message_producer');

$messageProducer->send('aFooTopic', 'Something has happened');
```

To consume messages you have to first create a message processor:

```php
<?php
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Consumption\Result;
use Enqueue\Client\TopicSubscriberInterface;

class FooMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function process(Message $message, Context $session)
    {
        echo $message->getBody();

        return Result::ACK;
        // return Result::REJECT; // when the message is broken
        // return Result::REQUEUE; // the message is fine but you want to postpone processing
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
    class: 'FooMessageProcessor'
    tags:
        - { name: 'enqueue.client.message_processor' }
```

Now you can start consuming messages:

```bash
$ ./app/console enqueue:consume --setup-broker
```

_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._


[back to index](../index.md)
