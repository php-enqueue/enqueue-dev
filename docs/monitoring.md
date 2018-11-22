<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Monitoring.

Enqueue provides a tool for monitoring message queues. 
With it, you can control how many messages were sent, how many processed successfuly or failed.
How many consumers are working, their up time, processed messages stats, memory usage and system load. 
The tool could be integrated with virtually any analytics and monitoring platform.
There are several integration:  
  * [InfluxDB](https://www.influxdata.com/) and [Grafana](https://grafana.com/)
  * [WAMP (Web Application Messaging Protocol)](https://wamp-proto.org/) 

We are working on a JS\WAMP based real-time UI tool, for more information please [contact us](opensource@forma-pro.com).

![Grafana Monitoring](images/grafana_monitoring.jpg)

[contact us](opensource@forma-pro.com) if need a Grafana template such as on the picture. 

* [Installation](#installation)
* [Track sent messages](#track-sent-messages)
* [Track consumed message](#track-consumed-message)
* [Track consumer metrics](#track-consumer-metrics)
* [Consumption extension](#consumption-extension)
* [Enqueue Client Extension](#enqueue-client-extension)
* [InfluxDB Storage](#influxdb-storage)
* [WAMP (Web Socket Messaging Protocol) Storage](#wamp-(web-socket-messaging-protocol)-storage)
* [Symfony App](#symfony-app)

## Installation

```bash
composer req enqueue/monitoring:0.9.x-dev
```

## Track sent messages 

```php
<?php
use Enqueue\Monitoring\SentMessageStats;
use Enqueue\Monitoring\GenericStatsStorageFactory;

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
$statsStorage->pushSentMessageStats(new SentMessageStats(
    (int) (microtime(true) * 1000), // timestamp
    'queue_name', // queue 
    'aMessageId',
    'aCorrelationId',
    [], // headers
    []  // properties
));
```

or, if you work with [Queue Interop](https://github.com/queue-interop/queue-interop) transport here's how you can track a message sent

```php
<?php
use Interop\Queue\Context;
use Enqueue\Monitoring\SentMessageStats;
use Enqueue\Monitoring\GenericStatsStorageFactory;

/** @var Context $context */

$queue = $context->createQueue('foo');
$message = $context->createMessage('body');

$context->createProducer()->send($queue, $message);

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
$statsStorage->pushSentMessageStats(new SentMessageStats(
    (int) (microtime(true) * 1000),
    $queue->getQueueName(), 
    $message->getMessageId(),
    $message->getCorrelationId(),
    $message->getHeaders()[],
    $message->getProperties()
));
```

## Track consumed message

```php
<?php
use Enqueue\Monitoring\ConsumedMessageStats;
use Enqueue\Monitoring\GenericStatsStorageFactory;

$receivedAt = (int) (microtime(true) * 1000);

// heavy processing here.

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
$statsStorage->pushConsumedMessageStats(new ConsumedMessageStats(
    'consumerId',
    (int) (microtime(true) * 1000), // now
    $receivedAt, 
    'aQueue',
    'aMessageId',
    'aCorrelationId',
    [], // headers
    [], // properties
    false, // redelivered or not
    ConsumedMessageStats::STATUS_ACK
));
```

or, if you work with [Queue Interop](https://github.com/queue-interop/queue-interop) transport here's how you can track a message sent

```php
<?php
use Interop\Queue\Context;
use Enqueue\Monitoring\ConsumedMessageStats;
use Enqueue\Monitoring\GenericStatsStorageFactory;

/** @var Context $context */

$queue = $context->createQueue('foo');

$consumer = $context->createConsumer($queue);

$consumerId = uniqid('consumer-id', true); // we suggest using UUID here
if ($message = $consumer->receiveNoWait()) {
    $receivedAt = (int) (microtime(true) * 1000);
    
    // heavy processing here.
    
    $consumer->acknowledge($message);
    
    $statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
    $statsStorage->pushConsumedMessageStats(new ConsumedMessageStats(
        $consumerId,
        (int) (microtime(true) * 1000), // now
        $receivedAt, 
        $queue->getQueueName(),
        $message->getMessageId(),
        $message->getCorrelationId(),
        $message->getHeaders(),
        $message->getProperties(),
        $message->isRedelivered(),
        ConsumedMessageStats::STATUS_ACK
    ));
}
```

## Track consumer metrics

Consumers are long running processes. It vital to know how many of them are running right now, how they perform, how much memory do they use and so.
This example shows how you can send such metrics. 
Call this code from time to time between processing messages.

```php
<?php
use Enqueue\Monitoring\ConsumerStats;
use Enqueue\Monitoring\GenericStatsStorageFactory;

$startedAt = (int) (microtime(true) * 1000);

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
$statsStorage->pushConsumerStats(new ConsumerStats(
    'consumerId',
    (int) (microtime(true) * 1000), // now
    $startedAt, 
    null, // finished at
    true, // is started? 
    false, // is finished?
    false, // is failed
    ['foo'], // consume from queues
    123, // received messages 
    120, // acknowledged messages
    1, // rejected messages
    1, // requeued messages
    memory_get_usage(true),
    sys_getloadavg()[0]
));
```

## Consumption extension

There is an extension `ConsumerMonitoringExtension` for Enqueue [QueueConsumer](quick_tour.md#consumption). 
It could collect consumed messages and consumer stats for you.

```php
<?php
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Monitoring\ConsumerMonitoringExtension;
use Enqueue\Monitoring\GenericStatsStorageFactory;
use Interop\Queue\Context;

/** @var Context $context */

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');

$queueConsumer = new QueueConsumer($context, new ChainExtension([
    new ConsumerMonitoringExtension($statsStorage)
]));

// bind

// consume
```

## Enqueue Client Extension

There is an extension ClientMonitoringExtension for Enqueue [Client](quick_tour.md#client) too. It could collect sent messages stats for you.

## InfluxDB Storage

Install additional packages:

```
composer req influxdb/influxdb-php:^1.14
```

```php
<?php
use Enqueue\Monitoring\GenericStatsStorageFactory;

$statsStorage = (new GenericStatsStorageFactory())->create('influxdb://127.0.0.1:8086?db=foo');
```

There are available options:

```
*   'host' => '127.0.0.1',
*   'port' => '8086',
*   'user' => '',
*   'password' => '',
*   'db' => 'enqueue',
*   'measurementSentMessages' => 'sent-messages',
*   'measurementConsumedMessages' => 'consumed-messages',
*   'measurementConsumers' => 'consumers',
```

## WAMP (Web Socket Messaging Protocol) Storage

Install additional packages:

```
composer req thruway/pawl-transport:^0.5.0 voryx/thruway:^0.5.3
```

```php
<?php
use Enqueue\Monitoring\GenericStatsStorageFactory;

$statsStorage = (new GenericStatsStorageFactory())->create('wamp://127.0.0.1:9090?topic=stats');
```

There are available options:

```
*   'host'                => '127.0.0.1',
*   'port'                => '9090',
*   'topic'               => 'stats',
*   'max_retries'         => 15,
*   'initial_retry_delay' => 1.5,
*   'max_retry_delay'     => 300,
*   'retry_delay_growth'  => 1.5,
```

## Symfony App

You have to register some services in order to incorporate monitoring facilities into your Symfony application. 

```yaml
# config/packages/enqueue.yaml

enqueue:
  default:
    transport: 'amqp://guest:guest@bar:5672/%2f'
    monitoring: 'influxdb://127.0.0.1:8086?db=foo'

  another:
    transport: 'amqp://guest:guest@foo:5672/%2f'
    monitoring: 'wamp://127.0.0.1:9090?topic=stats'
    client: ~
```

[back to index](index.md) 
