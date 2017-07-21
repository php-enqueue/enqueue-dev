# Laravel Queue. Quick tour.

The [LaravelQueue](https://github.com/php-enqueue/laravel-queue) allows to use [queue-interop](https://github.com/queue-interop/queue-interop) compatible transports as [Laravel Queue](https://laravel.com/docs/5.4/queues).

## Install

You have to install `enqueue/laravel-queue` packages and one of the [supported transports](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport).

```bash
$ composer require enqueue/larvel-queue enqueue/fs
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
              'default_processor_queue'  => 'default',
        ],
    ],
];
```

Register processor:

```php
<?php
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

$app->resolving(SimpleClient::class, function (SimpleClient $client, $app) {
    $client->bind('enqueue_test', 'a_processor', function(PsrMessage $message) {
        // do stuff here

        return PsrProcessor::ACK;
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
