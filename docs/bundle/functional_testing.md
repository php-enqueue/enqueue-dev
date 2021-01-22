---
layout: default
parent: "Symfony bundle"
title: Functional testing
nav_order: 12
---
{% include support.md %}

# Functional testing

In this chapter we give some advices on how to test message queue related logic.

* [NULL transport](#null-transport)
* [Traceable message producer](#traceable-message-producer)

## NULL transport

While testing the application you don't usually need to send real message to real broker.
Or even have a dependency on a MQ broker.
Here's the purpose of the NULL transport.
It simple do nothing when you ask it to send a message.
Pretty useful in tests.
Here's how you can configure it.

```yaml
# app/config/config_test.yml

enqueue:
    default:
        transport: 'null:'
        client: ~
```

## Traceable message producer

Imagine you have a service `my_service` with a method `someMethod()` that internally sends a message and you have to find out was the message sent or not.
There is a solution for that. You have to enable traceable message producer in test environment.

```yaml
# app/config/config_test.yml

enqueue:
    default:
        client:
            traceable_producer: true
```

If you did so, you can use its methods `getTraces`, `getTopicTraces` or `clearTraces`. Here's an example:

```php
<?php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Enqueue\Client\TraceableProducer;

class FooTest extends WebTestCase
{
    /** @var  \Symfony\Bundle\FrameworkBundle\Client */
    private $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testMessageSentToFooTopic()
    {
        // Use your own business logic here:
        $service = $this->client->getContainer()->get('my_service');

        // someMethod() is part of your business logic and is calling somewhere $producer->send('fooTopic', 'messageBody');
        $service->someMethod();

        $traces = $this->getProducer()->getTopicTraces('fooTopic');

        $this->assertCount(1, $traces);
        $this->assertEquals('messageBody', $traces[0]['message']);
    }

    /**
     * @return TraceableProducer
     */
    private function getProducer()
    {
        return $this->client->getContainer()->get(TraceableProducer::class);
    }
}
```

[back to index](index.md)
