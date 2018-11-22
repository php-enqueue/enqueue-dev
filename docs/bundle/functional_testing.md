<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

Imagine you have a service that internally sends a message and you have to find out was the message sent or not.
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
    
    public function setUp()
    {
        $this->client = static::createClient();        
    }
    
    public function testMessageSentToFooTopic()
    {
        $service = $this->client->getContainer()->get('a_service');
        
        // the method calls inside $producer->send('fooTopic', 'messageBody');
        $service->do();
        
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

[back to index](../index.md)