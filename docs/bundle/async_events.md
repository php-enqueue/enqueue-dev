---
layout: default
parent: "Symfony bundle"
title: Async events
nav_order: 6
---
{% include support.md %}

# Async events

The EnqueueBundle allows you to dispatch events asynchronously.
Behind the scene it replaces your listener with one that sends a message to MQ.
The message contains the event object.
The consumer, once it receives the message, restores the event and dispatches it to only async listeners.

Async listeners benefits:

* Reduces response time. Work is deferred to consumer processes.
* Better fault tolerance. Bugs in async listener do not affect user. Messages will wait till you fix bugs.
* Better scaling. Add more consumers to meet the load.

_**Note**: Prior to Symfony 3.0, events contain `eventDispatcher` and the default php serializer transformer is unable to serialize the object. A transformer should be registered for every async event. Read the [event transformer](#event-transformer)._

## Configuration

Symfony events are currently processed synchronously, enabling the async configuration for EnqueueBundle causes tagged listeners to defer action to a consumer asynchronously.
If you already [installed the bundle](quick_tour.md#install), then enable `async_events`.

```yaml
# app/config/config.yml

enqueue:
    default:
        async_events:
            enabled: true
            # if you'd like to send send messages onTerminate use spool_producer (it further reduces response time):
            # spool_producer: true
```

## Usage

To make your listener async you have add `async: true` attribute to the tag `kernel.event_listener`, like this:

```yaml
# app/config/config.yml

services:
    acme.foo_listener:
        class: 'AcmeBundle\Listener\FooListener'
        tags:
            - { name: 'kernel.event_listener', async: true, event: 'foo', method: 'onEvent' }
```

or to `kernel.event_subscriber`:

```yaml
# app/config/config.yml

services:
    test_async_subscriber:
        class: 'AcmeBundle\Listener\TestAsyncSubscriber'
        tags:
            - { name: 'kernel.event_subscriber', async: true }
```

That's basically it. The rest of the doc describes advanced features.

## Advanced Usage.

You can also add an async listener directly and register a custom message processor for it:

```yaml
# app/config/config.yml

services:
    acme.async_foo_listener:
        class: 'Enqueue\AsyncEventDispatcher\AsyncListener'
        public: false
        arguments: ['@enqueue.transport.default.context', '@enqueue.events.registry', 'a_queue_name']
        tags:
          - { name: 'kernel.event_listener', event: 'foo', method: 'onEvent' }
```


## Event transformer

The bundle uses [php serializer](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue-bundle/Events/PhpSerializerEventTransformer.php) transformer by default to pass events through MQ.
You can write a transformer for each event type by implementing the `Enqueue\AsyncEventDispatcher\EventTransformer` interface.
Consider the next example. It shows how to send an event that contains Doctrine entity as a subject

```php
<?php
namespace AcmeBundle\Listener;

// src/AcmeBundle/Listener/FooEventTransformer.php

use Enqueue\Client\Message;
use Enqueue\Consumption\Result;
use Interop\Queue\Message as QueueMessage;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\Event;
use Enqueue\AsyncEventDispatcher\EventTransformer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\EventDispatcher\GenericEvent;

class FooEventTransformer implements EventTransformer
{
    /** @var Registry @doctrine */
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     *
     * @param GenericEvent $event
     */
    public function toMessage($eventName, Event $event = null)
    {
        $entity = $event->getSubject();
        $entityClass = get_class($entity);

        $manager = $this->doctrine->getManagerForClass($entityClass);
        $meta = $manager->getClassMetadata($entityClass);

        $id = $meta->getIdentifierValues($entity);

        $message = new Message();
        $message->setBody([
            'entityClass' => $entityClass,
            'entityId' => $id,
            'arguments' => $event->getArguments()
        ]);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function toEvent($eventName, QueueMessage $message)
    {
        $data = JSON::decode($message->getBody());

        $entityClass = $data['entityClass'];

        $manager = $this->doctrine->getManagerForClass($entityClass);
        if (false == $entity = $manager->find($entityClass, $data['entityId'])) {
            return Result::reject('The entity could not be found.');
        }

        return new GenericEvent($entity, $data['arguments']);
    }
}
```

and register it:

```yaml
# app/config/config.yml

services:
    acme.foo_event_transformer:
        class: 'AcmeBundle\Listener\FooEventTransformer'
        arguments: ['@doctrine']
        tags:
            - {name: 'enqueue.event_transformer', eventName: 'foo' }
```

The `eventName` attribute accepts a regexp. You can do next `eventName: '/foo\..*?/'`.
It uses this transformer for all event with the name beginning with `foo.`

[back to index](index.md)
