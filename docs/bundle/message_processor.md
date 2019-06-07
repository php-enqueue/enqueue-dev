---
layout: default
parent: "Symfony bundle"
title: Message processor
nav_order: 5
---
{% include support.md %}

# Message processor

A processor is responsible for processing consumed messages.
Message processors and usage examples described in [consumption/message_processor](../consumption/message_processor.md)
Here we just show how to register a message processor service to enqueue.

* Transport:

  * [Register a transport processor](#register-a-transport-processor)

* Client:

  * [Register a topic subscriber processor](#register-a-topic-subscriber-processor)
  * [Register a command subscriber processor](#register-a-command-subscriber-processor)
  * [Register a custom processor](#register-a-custom-processor)

## Register a topic subscriber processor

There is a `TopicSubscriberInterface` interface (like [EventSubscriberInterface](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/EventDispatcher/EventSubscriberInterface.php)).
It is handy to subscribe on event messages.
Check interface description for more possible ways to configure it.
It allows to keep subscription and processing logic in one place.

```php
<?php
namespace App\Queue;

use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Processor;

class SayHelloProcessor implements Processor, TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return ['aTopic', 'anotherTopic'];
    }
}
```

Tag the service in the container with `enqueue.topic_subscriber` tag:

```yaml
# config/services.yml

services:
  App\Queue\SayHelloProcessor:
    tags:
        - { name: 'enqueue.topic_subscriber' }

        # registers to no default client
        - { name: 'enqueue.topic_subscriber', client: 'foo' }
```

## Register a command subscriber processor

There is a `CommandSubscriberInterface` interface.
It is handy to register a command processor.
Check interface description for more possible ways to configure it.
It allows to keep subscription and processing logic in one place.

```php
<?php
namespace App\Queue;

use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\Processor;

class SendEmailProcessor implements Processor, CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return 'aCommand';
    }
}
```

Tag the service in the container with `enqueue.command_subscriber` tag:

```yaml
# config/services.yml

services:
  App\Queue\SendEmailProcessor:
    tags:
        - { name: 'enqueue.command_subscriber' }

        # registers to no default client
        - { name: 'enqueue.command_subscriber', client: 'foo' }
```

There is a possibility to register a command processor which works exclusively on the queue (no other processors bound to it).
In this case you can send messages without setting any message properties at all.
It might be handy if you want to process messages that are sent by another application.

Here's a configuration example:

```php
<?php
use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\Processor;

class SendEmailProcessor implements Processor, CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return [
           'command' => 'aCommand',
           'queue' => 'the-queue-name',
           'prefix_queue' => false,
           'exclusive' => true,
       ];
    }
}
```

The service has to be tagged with `enqueue.command_subscriber` tag.

# Register a custom processor

You could register a processor that does not implement neither `CommandSubscriberInterface` not `TopicSubscriberInterface`.
There is a tag `enqueue.processor` for it. You must define either `topic` or `command` tag attribute.
It is possible to define a client you would like to register the processor to. By default, it is registered to default client (first configured or named `default` one ).

```yaml
# src/AppBundle/Resources/services.yml

services:
  AppBundle\Async\SayHelloProcessor:
    tags:
        # registers as topic processor
        - { name: 'enqueue.processor', topic: 'aTopic' }
        # registers as command processor
        - { name: 'enqueue.processor', command: 'aCommand' }

        # registers to no default client
        - { name: 'enqueue.processor', command: 'aCommand', client: 'foo' }
```

The tag has some additional options:

* queue
* prefix_queue
* processor
* exclusive

You could add your own attributes. They will be accessible through `Route::getOption` later.

# Register a transport processor

If you want to use a processor with `enqueue:transport:consume` it should be tagged `enqueue.transport.processor`.
It is possible to define a transport you would like to register the processor to. By default, it is registered to default transport (first configured or named `default` one ).

```yaml
# config/services.yml

services:
  App\Queue\SayHelloProcessor:
    tags:
        - { name: 'enqueue.transport.processor', processor: 'say_hello' }

        # registers to no default transport
        - { name: 'enqueue.processor', transport: 'foo' }
```

The tag has some additional options:

* processor

Now you can run a command and tell it to consume from a given queue and process messages with given processor:

```bash
$ ./bin/console enqueue:transport:consume say_hello foo_queue -vvv
```

[back to index](index.md)
