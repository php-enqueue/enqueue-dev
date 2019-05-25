# Key concepts

If you are new to queuing system, there are some key concepts to understand to make the most of this lib.

## Messages and queues

The queue is where messages are sent to for later processing. Once in the queue, they wait to be fetched, processed and removed from the queue. 
A queue may provide the following features:
- *receive and delete delivery*: the queue deletes the message when it's fetched. If processing fails, then the message is lost and won't be processed again. This is called _at most once_ processing.
- *peek and lock delivery*: the queue locks for a short amount of time a message when it's fetched, making it invisible to other processes, in order to prevent duplicate processing. If there is no acknowledgment before the lock times out, failure is assumed and then the message is made visible again in the queue for another try. This is called _at least once_ processing.
- *delayed or scheduled messages*: messages are sent to the queue but won't be visible right away to processes to fetch them. You may need it to plan an action at a specific time.
- *first in first out*: messages are processed in the same order than they have entered the queue.

## Topic and subscription

These are are advanced features:
- a message goes to a queue and is sent to multiple recipients
- a message is routing to different queues depending on some rules

## Transport

The transport is the underlying vendor-specific system providing the queue features: most famous ones are RabbitMQ, Amazon SQS, ...
Enqueue uses https://github.com/queue-interop/queue-interop PHP interfaces to provide a common way for programs to create, send, read messages in queues without worrying about vendor-specific internals.

## Destination

This is where a message goes, like a queue for a basic scenario but it may also be a topic or a command (understand a Symfony Console command).

## Connection factory, Driver and Context

The Connection factory creates a connection to the vendor service with vendor-specific config and theses are wrapped into a Context.
The Context will then provides the Producer, the Consumer and helps creates Messages.

The Driver handles the routing of a Message.

## Producer & Consumer

The Producer sends the Message to the queue and the Consumer fetches Message from the queue.

Both the Producer and the Consumer implement vendor-specific logic and are in charge to convert messages between Enqueue common standard and vendor-specific message format.

## Processor

It handles the processing of the Message once received. It implements your business logic.

## Lifecycle

A queuing system is divided in two main parts: producing and consuming.

The [transport section of the Quick Start](quick_tour.md#transport) shows some code example for both parts.

### Producing part
1. The application creates a Context with a Connection factory
2. The Context helps the application to create a Message
3. The application gets a Producer from the Context
4. The application uses the Producer to send the Message to the queue

### Consuming part
1. The application gets a Consumer from the Context
2. The Consumer receives Messages from the queue
3. The Consumer uses a Processor to process a Message
4. The Processor returns a status (like `Interop\Queue\Processor::ACK`) to the Consumer
5. The Consumer requeue or remove the Message from the queue depending on the Processor returned status

## How to use Enqueue?

There are different ways to use Enqueue: both reduce the boiler plate code you have to write to start using the Enqueue feature.
- as a [Client](client/quick_tour.md): relies on a [DSN](client/supported_brokers.md) to connect
- as a [Symfony Bundle](bundle/index.md): recommended if you are using the Symfony framework
