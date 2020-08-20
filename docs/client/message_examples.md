---
layout: default
parent: Client
title: Message examples
nav_order: 2
---
{% include support.md %}

# Client. Message examples

* [Scope](#scope)
* [Delay](#delay)
* [Expiration (TTL)](#expiration-ttl)
* [Priority](#priority)
* [Timestamp, Content type, Message id](#timestamp-content-type-message-id)

## Scope

There are two types possible scopes: `Message:SCOPE_MESSAGE_BUS` and `Message::SCOPE_APP`.
The first one instructs the client send messages (if driver supports) to the message bus so other apps can consume those messages.
The second in turns limits the message to the application that sent it. No other apps could receive it.

```php
<?php

use Enqueue\Client\Message;

$message = new Message();
$message->setScope(Message::SCOPE_MESSAGE_BUS);

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer->sendEvent('aTopic', $message);
```

## Delay

Message sent with a delay set is processed after the delay time exceed.
Some brokers may not support it from scratch.
In order to use delay feature with [RabbitMQ](https://www.rabbitmq.com/) you have to install a [delay plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange).

```php
<?php

use Enqueue\Client\Message;

$message = new Message();
$message->setDelay(60); // seconds

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer->sendEvent('aTopic', $message);
```

## Expiration (TTL)

The message may have an expiration or TTL (time to live).
The message is removed from the queue if the expiration exceeded but the message has not been consumed.
For example it make sense to send a forgot password email within first few minutes, nobody needs it in an hour.

```php
<?php

use Enqueue\Client\Message;

$message = new Message();
$message->setExpire(60); // seconds

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer->sendEvent('aTopic', $message);
```

## Priority

You can set a priority If you want a message to be processed quicker than other messages in the queue.
Client defines five priority constants:

* `MessagePriority::VERY_LOW`
* `MessagePriority::LOW`
* `MessagePriority::NORMAL` (**default**)
* `MessagePriority::HIGH`
* `MessagePriority::VERY_HIGH`

```php
<?php

use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;

$message = new Message();
$message->setPriority(MessagePriority::HIGH);

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer->sendEvent('aTopic', $message);
```

## Timestamp, Content type, Message id

Those are self describing things.
Usually they are set by Client so you don't have to worry about them.
If you do not like what Client set you can always set custom values:

```php
<?php

use Enqueue\Client\Message;

$message = new Message();
$message->setMessageId('aCustomMessageId');
$message->setTimestamp(time());
$message->setContentType('text/plain');

/** @var \Enqueue\Client\ProducerInterface $producer */
$producer->sendEvent('aTopic', $message);
```

[back to index](../index.md)
