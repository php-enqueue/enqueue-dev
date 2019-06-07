---
layout: default
title: Key concepts
nav_order: 1
---

# Key concepts

If you are new to queuing system, there are some key concepts to understand to make the most of this lib.

The library consist of several components. The components could be used independently or as integral part.

## Components

### Transport

The transport is the underlying vendor-specific library that provides the queuing features: a way for programs to create, send, read messages.
Based on [queue interop](https://github.com/queue-interop/queue-interop) interfaces. Use transport directly if you need full control or access to vendor specific features.

The most famous transports are [RabbitMQ](transport/amqp_lib.md), [Amazon SQS](transport/sqs.md), [Redis](transport/redis.md), [Filesystem](transport/filesystem.md).

- *connection factory* creates a connection to the vendor service with vendor-specific config.
- *context* provides the Producer, the Consumer and helps create Messages. It is the most commonly used object and an implementation of [abstract factory](https://en.wikipedia.org/wiki/Abstract_factory_pattern) pattern.
- *destination* is a concept of a destination to which messages can be sent. Choose queue or topic. Destination represents broker state so expect to see same names at broker side.
- *queue* is a named destination to which messages can be sent to. Messages accumulate on queues until they are retrieved by programs (called consumers) that service those queues.
- *topic* implements [publish and subscribe](https://en.wikipedia.org/wiki/Publish%E2%80%93subscribe_pattern) semantics. When you publish a message it goes to all the subscribers that are interested - so zero to many subscribers will receive a copy of the message. Some brokers do not support Pub\Sub.
- *message* describes data sent to (or received from) a destination. It has a body, headers and properties.
- *producer* sends a message to the destination. The producer implements vendor-specific logic and is in charge of converting messages between Enqueue and vendor-specific message format.
- *consumer* fetches a message from a destination. The consumer implements vendor-specific logic and is in charge of converting messages between vendor-specific message format and Enqueue.
- *subscription consumer* provides a way to consume messages from several destinations simultaneously. Some brokers do not support this feature.
- *processor* is an optional concept useful for sharing message processing logic. Vendor independent. Implements your business logic.

Additional terms we might refer to:
- *receive and delete delivery*: the queue deletes the message when it's fetched by consumer. If processing fails, then the message is lost and won't be processed again. This is called _at most once_ processing.
- *peek and lock delivery*: the queue locks for a short amount of time a message when it's fetched by consumer, making it invisible to other consumers, in order to prevent duplicate processing and message lost. If there is no acknowledgment before the lock times out, failure is assumed and then the message is made visible again in the queue for another try. This is called _at least once_ processing.
- *an explicit acknowledgement*: the queue locks a message when it's fetched by consumer, making it invisible to other consumers, in order to prevent duplicate processing and message lost. If there is no explicit acknowledgment received before the connection is closed, failure is assumed and then the message is made visible again in the queue for another try. This is called _at least once_ processing.
- *message delivery delay*: messages are sent to the queue but won't be visible right away to consumers to fetch them. You may need it to plan an action at a specific time.
- *message expiration*: messages could be dropped of a queue within some period of time without processing. You may need it to not process stale messages. Some transports do not support the feature.
- *message priority*: message could be sent with higher priority, therefor being consumed faster. It violates first in first out concept and should be used with precautions. Some transports do not support the feature.
- *first in first out*: messages are processed in the same order than they have entered the queue.

Lifecycle

A queuing system is divided in two main parts: producing and consuming.
The [transport section of the Quick Start](quick_tour.md#transport) shows some code example for both parts.

Producing part
1. The application creates a Context with a Connection factory
2. The Context helps the application to create a Message
3. The application gets a Producer from the Context
4. The application uses the Producer to send the Message to the queue

Consuming part
1. The application gets a Consumer from the Context
2. The Consumer receives Messages from the queue
3. The Consumer uses a Processor to process a Message
4. The Processor returns a status (like `Interop\Queue\Processor::ACK`) to the Consumer
5. The Consumer requeues or removes the Message from the queue depending on the Processor returned status

### Consumption

The consumption component is based on top of transport.
The most important class is [QueueConsumer](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/QueueConsumer.php).
Could be used with any queue interop compatible transport.
It provides extension points which could be ad-hoc into processing flow. You can register [existing extensions](consumption/extensions.md) or write a custom one.

### Client

Enqueue Client is designed for as simple as possible developer experience.
It provides high-level, very opinionated API.
It manages all transport differences internally and even emulate missing features (like publish-subscribe).
Please note: Client has own logic for naming transport destinations. Expect a different transport queue\topic name from the Client topic, command name. The prefix behavior could be disabled.

- *Topic:* Send a message to the topic when you want to notify several subscribers that something has happened. There is no way to get subscriber results. Uses the router internally to deliver messages.
- *Command:* guarantees that there is exactly one command processor\subscriber. Optionally, you can get a result. If there is no command subscriber an exception is thrown.
- *Router:* copy a message sent to the topic and duplicate it for every subscriber and send.
- *Driver* contains vendor specific logic.
- *Producer* is responsible for sending messages to the topic or command. It has nothing to do with transport's producer.
- *Message* contains data to be sent. Please note that on consumer side you have to deal with transport message.
- *Consumption:* rely on consumption component.

## How to use Enqueue?

There are different ways to use Enqueue: both reduce the boiler plate code you have to write to start using the Enqueue feature.
- as a [Client](client/quick_tour.md): relies on a [DSN](client/supported_brokers.md) to connect
- as a [Symfony Bundle](bundle/index.md): recommended if you are using the Symfony framework

[back to index](index.md)
