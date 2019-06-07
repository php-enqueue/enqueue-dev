---
layout: default
parent: Laravel
title: Quick tour
nav_order: 1
---
{% include support.md %}

# Laravel Queue. Quick tour.

The [enqueue/laravel-queue](https://github.com/php-enqueue/laravel-queue) is message queue bridge for Enqueue. You can use all transports built on top of [queue-interop](https://github.com/queue-interop/queue-interop) including [all supported](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport) by Enqueue.

The package allows you to use queue interop transport the [laravel way](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/laravel/queues.md) as well as integrates the [enqueue simple client](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/laravel/quick_tour.md#enqueue-simple-client).

**NOTE:** The part of this code was originally proposed as a PR to [laravel/framework#20148](https://github.com/laravel/framework/pull/20148). It was closed without much explanations, so I decided to open source it as a stand alone package.

## Install

You have to install `enqueue/laravel-queue` packages and one of the [supported transports](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport).

```bash
$ composer require enqueue/laravel-queue enqueue/fs
```

## Register service provider

```php
<?php

// config/app.php

return [
    'providers' => [
        Enqueue\LaravelQueue\EnqueueServiceProvider::class,
    ],
];
```

## Laravel queues

At this stage you are already able to use [laravel queues](queues.md).

## Enqueue Simple client

If you want to use [enqueue/simple-client](https://github.com/php-enqueue/simple-client) in your Laravel application you have perform additional steps .
You have to install the client library, in addition to what you've already installed:

```bash
$ composer require enqueue/simple-client
```

Create `config/enqueue.php` file and put a client configuration there:
Here's an example of what it might look like:

```php
<?php

// config/enqueue.php

return [
    'client' => [
        'transport' => [
            'default' => 'file://'.realpath(__DIR__.'/../storage/enqueue')
        ],
        'client' => [
              'router_topic'             => 'default',
              'router_queue'             => 'default',
              'default_queue'  => 'default',
        ],
    ],
];
```

Register processor:

```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\Message;
use Interop\Queue\Processor;

$app->resolving(SimpleClient::class, function (SimpleClient $client, $app) {
    $client->bindTopic('enqueue_test', function(Message $message) {
        // do stuff here

        return Processor::ACK;
    });

    return $client;
});

```

Send message:

```php
<?php
use Enqueue\SimpleClient\SimpleClient;

/** @var SimpleClient $client */
$client = \App::make(SimpleClient::class);

$client->sendEvent('enqueue_test', 'The message');
```

Consume messages:

```bash
$ php artisan enqueue:consume -vvv --setup-broker
```

[back to index](../index.md)
