---
layout: default
nav_exclude: true
---
<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Laravel Queue. Quick tour.

The [LaravelQueue](https://github.com/php-enqueue/laravel-queue) package allows to use [queue-interop](https://github.com/queue-interop/queue-interop) compatible transports [the Laravel way](https://laravel.com/docs/5.4/queues).
I suppose you already [installed and configured](quick_tour.md) the package so let's look what you have to do to make queue work.

## Configure

You have to add a connector to `config/queues.php` file. The driver must be `interop`.

```php
<?php

// config/queue.php

return [
    'default' => 'interop',
    'connections' => [
        'interop' => [
            'driver' => 'interop',
            'dsn' => 'amqp+rabbitmq://guest:guest@localhost:5672/%2f',
        ],
    ],
];
```

Here's a [full list](../transport) of supported transports.

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

```php
<?php

// config/queue.php

return [
    // uncomment to set it as default
    // 'default' => env('QUEUE_DRIVER', 'interop'),

    'connections' => [
        'interop' => [
            'driver' => 'interop',

            // connects to localhost
            'dsn' => 'amqp:', //

            // could be "rabbitmq_dlx", "rabbitmq_delay_plugin", instance of DelayStrategy interface or null
            // 'delay_strategy' => 'rabbitmq_dlx'
        ],
    ],
];
```

[back to index](../index.md)
