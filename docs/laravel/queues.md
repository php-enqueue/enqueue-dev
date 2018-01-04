# Laravel Queue. Quick tour.

The [LaravelQueue](https://github.com/php-enqueue/laravel-queue) package allows to use [queue-interop](https://github.com/queue-interop/queue-interop) compatible transports [the Laravel way](https://laravel.com/docs/5.4/queues).
I suppose you already [installed and configured](quick_tour.md) the package so let's look what you have to do to make queue work.  

## Configure

You have to add a connector to `config/queues.php` file. The driver must be `interop`.

```php
<?php

// config/queue.php

return [
    // uncomment to set it as default
    // 'default' => env('QUEUE_DRIVER', 'interop'),
    
    'connections' => [
        'interop' => [
            'driver' => 'interop',
            'connection_factory_class' => \Enqueue\Fs\FsConnectionFactory::class,
            
            // the factory specific options
            'dsn' => 'file://'.storage_path('enqueue'),
        ],
    ],
];
```

## Usage

Same as standard [Laravel Queues](https://laravel.com/docs/5.4/queues)

Send message example:

```php
<?php

$job = (new \App\Jobs\EnqueueTest())->onConnection('interop');

dispatch($job);
```

Consume messages:

```bash
$ php artisan queue:work interop
```

## Amqp interop

While interop connector can send\consume messages from any queue interop compatible transports. 
But it does not support some AMQP specific features, such as queue declaration and delays. 
To cover those cases the package provides a AmqpQueue. It can work with any amqp interop [compatible trnasport](https://github.com/queue-interop/queue-interop#compatible-projects-1), for example `enqueue/amqp-bunny`. 
Here's how it could be configured:

```php
<?php

// config/queue.php

return [
    // uncomment to set it as default
    // 'default' => env('QUEUE_DRIVER', 'interop'),
    
    'connections' => [
        'interop' => [
            'driver' => 'amqp_interop',
            'connection_factory_class' => \Enqueue\AmqpBunny\AmqpConnectionFactory::class,
            
            // connects to localhost
            'dsn' => 'amqp:',
            
            // could be "rabbitmq_dlx", "rabbitmq_delay_plugin", instance of DelayStrategy interface or null 
            // 'delay_strategy' => 'rabbitmq_dlx' 
        ],
    ],
];
```

[back to index](../index.md)
