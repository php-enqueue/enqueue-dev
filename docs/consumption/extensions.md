---
layout: default
nav_exclude: true
---
<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Consumption extensions.

You can learn how to register extensions in [quick tour](../quick_tour.md#consumption).
There's dedicated [chapter](../bundle/consumption_extension.md) for how to add extension in Symfony app.

## [LoggerExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/LoggerExtension.php)

It sets logger to queue consumer context. All log messages will go to it.

## [DoctrineClearIdentityMapExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue-bundle/Consumption/Extension/DoctrineClearIdentityMapExtension.php)

It clears Doctrine's identity map after a message is processed. It reduce memory usage.

## [DoctrinePingConnectionExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue-bundle/Consumption/Extension/DoctrinePingConnectionExtension.php)

It test a database connection and if it is lost it does reconnect. Fixes "MySQL has gone away" errors.

## [ReplyExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/ReplyExtension.php)

It comes with RPC code and simplifies reply logic.
It takes care of sending a reply message to reply queue.

## [SetupBrokerExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Client/ConsumptionExtension/SetupBrokerExtension.php)

It responsible for configuring everything at a broker side. queues, topics, bindings and so on.
The extension is added at runtime when `--setup-broker` option is used.

## [LimitConsumedMessagesExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/LimitConsumedMessagesExtension.php)

The extension counts processed message and once a limit is reached it interrupts consumption.
The extension is added at runtime when `--message-limit=10` option is used.

## [LimitConsumerMemoryExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/LimitConsumerMemoryExtension.php)

The extension interrupts consumption once a memory limit is reached.
The extension is added at runtime when `--memory-limit=512` option is used.
The value is Mb.

## [LimitConsumptionTimeExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/LimitConsumptionTimeExtension.php)

The extension interrupts consumption once time limit is reached.
The extension is added at runtime when `--time-limit="now + 2 minutes"` option is used.

## [SignalExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Consumption/Extension/SignalExtension.php)

The extension catch process signals and gracefully stops consumption. Works only on NIX platforms.

## [DelayRedeliveredMessageExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/pkg/enqueue/Client/ConsumptionExtension/DelayRedeliveredMessageExtension.php)

The extension checks whether the received message is redelivered (There was attempt to process message but it failed).
If so the extension reject the origin message and creates a copy message with a delay.

## [ConsumerMonitoringExtension](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/monitoring.md#consumption-extension)

There is an extension ConsumerMonitoringExtension for Enqueue QueueConsumer. It could collect consumed messages and consumer stats for you and send them to Grafana, InfluxDB or Datadog.

[back to index](../index.md)
