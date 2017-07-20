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

## Configure

First, you have to configure a transport layer and set one to be default.

```php
<?php

// config/queue.php

return [
    'connections' => [
        'interop' => [
            'driver' => 'interop',
            'connection_factory_class' => \Enqueue\Fs\FsConnectionFactory::class,
            
            // the factory specific options
            'dsn' => 'file://'.realpath(__DIR__.'/../storage').'/enqueue',
        ],
    ],
];
```

## Usage

Same as standard [Laravel Queues](https://laravel.com/docs/5.4/queues)

[back to index](../index.md)
