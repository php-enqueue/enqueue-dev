---
layout: default
parent: Yii
title: AMQP Interop driver
nav_order: 1
---
{% include support.md %}

# Yii2Queue. AMQP Interop driver

_**Note:** This a copy of [AMQP Interop doc](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/driver-amqp-interop.md) from the yiisoft/yii2-queue [repository](https://github.com/yiisoft/yii2-queue)._


The driver works with RabbitMQ queues.

In order for it to work you should add any [amqp interop](https://github.com/queue-interop/queue-interop#amqp-interop) compatible transport to your project, for example `enqueue/amqp-lib` package.

Advantages:

* It would work with any amqp interop compatible transports, such as

    * [enqueue/amqp-ext](https://github.com/php-enqueue/amqp-ext) based on [PHP amqp extension](https://github.com/pdezwart/php-amqp)
    * [enqueue/amqp-lib](https://github.com/php-enqueue/amqp-lib) based on [php-amqplib/php-amqplib](https://github.com/php-amqplib/php-amqplib)
    * [enqueue/amqp-bunny](https://github.com/php-enqueue/amqp-bunny) based on [bunny](https://github.com/jakubkulhan/bunny)

* Supports priorities
* Supports delays
* Supports ttr
* Supports attempts
* Contains new options like: vhost, connection_timeout, qos_prefetch_count and so on.
* Supports Secure (SSL) AMQP connections.
* An ability to set DSN like: amqp:, amqps: or amqp://user:pass@localhost:1000/vhost

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
            'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,

            // or
            'dsn' => 'amqp://guest:guest@localhost:5672/%2F',

            // or, same as above
            'dsn' => 'amqp:',
        ],
    ],
];
```

Console
-------

Console is used to listen and process queued tasks.

```sh
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. This method is most efficient when command is properly
daemonized via supervisor.

`listen` command has options:

- `--verbose`, `-v`: print executing statuses into console.
- `--isolate`: verbose mode of a job execute. If enabled, execute result of each job will be printed.
- `--color`: highlighting for verbose mode.

[back to index](../index.md)
