---
layout: default
parent: "Symfony bundle"
title: Multiple queues
nav_order: 13
---
{% include support.md %}

# Multiple queues

One of the most powerful features of this bundle is the ability to run multiple queues with the same transport/client. 

## Different queues for topic routing and processing

In the default configuration both the topic routing and the job processing tasks are written to the `default` queue. If you want to separate those two types of tasks you'll have to change the [configuration](config_reference.md) in `config/packages/enqueue.yaml` like so:

```yaml
enqueue:
    default:
        transport:            # Required
        client:
            traceable_producer:   true
            prefix:               enqueue
            separator:            .
            app_name:             app
            router_topic:         default
            router_queue:         router # default is: default
            router_processor:     null
            redelivered_delay_time: 0
            default_queue:        default
```

## Multiple topic queues

Configuration for the different queues happens on the [processor](message_processor.md)/subscriber level - the queue is either defined in `config/services.yml` like so:

```yaml
# src/AppBundle/Resources/services.yml

services:
  AppBundle\Async\SayHelloProcessor:
    tags:
        # registers as topic processor
        - { name: 'enqueue.processor', topic: 'hello', queue: 'say' }
```

or in the processor itself like so:

```php
<?php
use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\Processor;

class SayHelloProcessor implements Processor, TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
           'topic' => 'hello',
           'queue' => 'say',
       ];
    }
}
```

Both ways will enable you to run a queue called `say` exclusively with the [cli command](cli_commands.md)  `bin/console enqueue:consume say` or run all tasks except those in the `say` queue with `bin/console enqueue:consume --skip=say`

Beware that messages for this topic will end up in the router queue first - so if you don't have a process running that consumes the router queue, your messages will never _really_ reach the `say` queue. In that case you'll still have to run `bin/console enqueue:consume default` - or, if you have changed the name of your router queue: `bin/console enqueue:consume router`

[back to index](index.md)
