# Message processor

Message processors and usage examples described in [consumption/message_processor](../consumption/message_processor.md)
Here we just show how to register a message processor service to enqueue. Let's say we have app bundle and a message processor there

* [Container tag](#container-tag)
* [Topic subscriber](#topic-subscriber)
* [Command subscriber](#command-subscriber)

# Container tag

```yaml
# src/AppBundle/Resources/services.yml

services:
  app.async.say_hello_processor:
    class: 'AppBundle\Async\SayHelloProcessor'
    tags:
        - { name: 'enqueue.client.processor', topicName: 'aTopic' }
        
```

The tag has some additional options:

* topicName [Req]: Tells what topic to consume messages from.
* queueName: By default message processor does not require an extra queue on broker side. It reuse a default one. Setting the option you can define a custom queue to be used.
* processorName: By default the service id is used as message processor name. Using the option you can define a custom name.

# Topic subscriber

There is a `TopicSubscriberInterface` interface (like [EventSubscriberInterface](https://github.com/symfony/symfony/blob/master/src/Symfony/Component/EventDispatcher/EventSubscriberInterface.php)). 
It is handy to subscribe on event messages. It allows to keep subscription login and process logic closer to each other. 

```php
<?php
namespace AppBundle\Async;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\PsrProcessor;

class SayHelloProcessor implements PsrProcessor, TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return ['aTopic', 'anotherTopic'];
    }
}
```

On the topic subscriber you can also define queue and processor name:

```php
<?php
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\PsrProcessor;

class SayHelloProcessor implements PsrProcessor, TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
            'aTopic' => ['queueName' => 'fooQueue', 'processorName' => 'foo'], 
            'anotherTopic' => ['queueName' => 'barQueue', 'processorName' => 'bar'], 
        ];
    }
}
```

In the container you can just add the tag `enqueue.client.message_processor` and omit any other options:

```yaml
# src/AppBundle/Resources/services.yml

services:
  app.async.say_hello_processor:
    class: 'AppBundle\Async\SayHelloProcessor'
    tags:
        - { name: 'enqueue.client.processor'}

```

# Command subscriber

There is a `CommandSubscriberInterface` interface which allows to register a command handlers. 
If you send a message using ProducerV2::sendCommand('aCommandName') method it will come to this processor.

```php
<?php
namespace AppBundle\Async;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Psr\PsrProcessor;

class SayHelloProcessor implements PsrProcessor, CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return 'aCommandName';
    }
}
```

On the command subscriber you can also define additional settings such as queue and processor name:

```php
<?php
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Psr\PsrProcessor;

class SayHelloProcessor implements PsrProcessor, CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return ['queueName' => 'fooQueue', 'processorName' => 'aCommandName'];
    }
}
```

There is a possibility to register a command processor which works exclusively on the queue (no other processors bound to it).
In this case you can send messages without setting any message properties at all. Here's an example of such a processor:

In the container you can just add the tag `enqueue.client.message_processor` and omit any other options:

```php
<?php
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Psr\PsrProcessor;

class SayHelloProcessor implements PsrProcessor, CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return [
           'processorName' => 'the-exclusive-command-name',
           'queueName' => 'the-queue-name',
           'queueNameHardcoded' => true,
           'exclusive' => true,
       ];
    }
}
```

The same as a topic subscriber you have to tag a processor service (no need to add any options there):


```yaml
# src/AppBundle/Resources/services.yml

services:
  app.async.say_hello_processor:
    class: 'AppBundle\Async\SayHelloProcessor'
    tags:
        - { name: 'enqueue.client.processor'}

```

[back to index](../index.md)
