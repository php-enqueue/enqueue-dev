<?php

$autoload = null;
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        $autoload = $file;

        break;
    }
}

if ($autoload) {
    require_once $autoload;
} else {
    throw new \LogicException('Composer autoload was not found');
}

use Enqueue\AmqpLib\AmqpConnectionFactory;

$config = [
    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
    'user' => getenv('SYMFONY__RABBITMQ__USER'),
    'pass' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
    'receive_method' => 'basic_consume',
];

$factory = new AmqpConnectionFactory($config);
$context = $factory->createContext();

$queue = $context->createQueue('foo');
$fooConsumer = $context->createConsumer($queue);

$queue = $context->createQueue('bar');
$barConsumer = $context->createConsumer($queue);

$consumers = [$fooConsumer, $barConsumer];

$consumer = $consumers[rand(0, 1)];

while (true) {
    if ($m = $consumer->receive(100)) {
        echo $m->getBody(), PHP_EOL;
        $consumer->acknowledge($m);
    }

    $consumer = $consumers[rand(0, 1)];
}

echo 'Done'."\n";
