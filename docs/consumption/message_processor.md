---
layout: default
parent: Consumption
title: Message processors
---
{% include support.md %}

# Message processor

* [Basics](#basics)
* [Reply result](#reply-result)
* [On exceptions](#on-exceptions)
* [Examples](#examples)


## Basics

The message processor is an object that actually process the message and must return a result status.
Here's example:

```php
<?php
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
        $this->mailer->send('foo@example.com', $message->getBody());

        return self::ACK;
    }
}
```

By returning `self::ACK` a processor tells a broker that the message has been processed correctly.

There are other statuses:

* `self::ACK` - Use this constant when the message is processed successfully and the message could be removed from the queue.
* `self::REJECT` - Use this constant when the message is not valid or could not be processed. The message is removed from the queue.
* `self::REQUEUE` - Use this constant when the message is not valid or could not be processed right now but we can try again later

Look at the next example that shows the message validation before sending a mail. If the message is not valid a processor rejects it.

```php
<?php
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Enqueue\Util\JSON;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
        $data = JSON::decode($message->getBody());
        if ($user  = $this->userRepository->find($data['userId'])) {
            return self::REJECT;
        }

        $this->mailer->send($user->getEmail(), $data['text']);

        return self::ACK;
    }
}
```

It is possible to find out whether the message failed previously or not.
There is `isRedelivered` method for that.
If it returns true than there was attempt to process message.

```php
<?php
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
        if ($message->isRedelivered()) {
            return self::REQUEUE;
        }

        $this->mailer->send('foo@example.com', $message->getBody());

        return self::ACK;
    }
}
```

The second argument is your context. You can use it to send messages to other queues\topics.

```php
<?php
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
        $this->mailer->send('foo@example.com', $message->getBody());

        $queue = $context->createQueue('anotherQueue');
        $message = $context->createMessage('Message has been sent');
        $context->createProducer()->send($queue, $message);

        return self::ACK;
    }
}
```

## Reply result

The consumption component provide some useful extensions, for example there is an extension that makes RPC processing simpler.
The producer might wait for a reply from a consumer and in order to send it a processor has to return a reply result.
Don't forget to add `ReplyExtension`.

```php
<?php
use Interop\Queue\Processor;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context)
    {
        $this->mailer->send('foo@example.com', $message->getBody());

        $replyMessage = $context->createMessage('Message has been sent');

        return Result::reply($replyMessage);
    }
}

/** @var \Interop\Queue\Context $context */

$queueConsumer = new QueueConsumer($context, new ChainExtension([
    new ReplyExtension()
]));

$queueConsumer->bind('foo', new SendMailProcessor());

$queueConsumer->consume();
```


## On exceptions

It is advised to not catch exceptions and [fail fast](https://en.wikipedia.org/wiki/Fail-fast).
Also consider using [supervisord](supervisord.org) or similar process manager to restart exited consumers.

Despite advising to fail there are some cases where you might want to catch exceptions.

* A message validator throws an exception on invalid message. It is better to catch it and return `REJECT`.
* Some transports ([Doctrine DBAL](../transport/dbal.md), [Filesystem](../transport/filesystem.md), [Redis](../transport/redis.md)) does notice an error,
and therefor won't be able to redeliver the message. The message is completely lost. You might want to catch an exception to properly redelivery\requeue the message.

# Examples

Feel free to contribute your own.

* [LiipImagineBundle. ResolveCacheProcessor](https://github.com/liip/LiipImagineBundle/blob/713e36f5df353d7c5345daed5a2eefc23c103849/Async/ResolveCacheProcessor.php#L1)
* [EnqueueElasticaBundle. ElasticaPopulateProcessor](https://github.com/php-enqueue/enqueue-elastica-bundle/blob/7c05c55b1667f9cae98325257ba24fc101f87f97/Async/ElasticaPopulateProcessor.php#L1)
* [formapro/pvm. HandleAsyncTransitionProcessor](https://github.com/formapro/pvm/blob/d5e989a77eb1540a93e69abacc446b3d7937292d/src/Enqueue/HandleAsyncTransitionProcessor.php#L1)
* [php-quartz. EnqueueRemoteTransportProcessor](https://github.com/php-quartz/quartz-dev/blob/91690aa535b0322510b4555dab59d6ae9d7044e5/pkg/bridge/Enqueue/EnqueueRemoteTransportProcessor.php#L1)
* [php-comrade. CreateJobProcessor](https://github.com/php-comrade/comrade-dev/blob/43c0662b74340aae318bceb15d8564670325dcee/apps/jm/src/Queue/CreateJobProcessor.php#L1)
* [prooph/psb-enqueue-producer. EnqueueMessageProcessor](https://github.com/prooph/psb-enqueue-producer/blob/c80914a4092b42b2d0a7ba698b216e0af23bab42/src/EnqueueMessageProcessor.php#L1)


[back to index](../index.md)
