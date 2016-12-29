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

use Enqueue\AmqpExt\AmqpConnectionFactory;

$config = [
    'host' => getenv('SYMFONY__RABBITMQ__HOST'),
    'port' => getenv('SYMFONY__RABBITMQ__AMQP__PORT'),
    'login' => getenv('SYMFONY__RABBITMQ__USER'),
    'password' => getenv('SYMFONY__RABBITMQ__PASSWORD'),
    'vhost' => getenv('SYMFONY__RABBITMQ__VHOST'),
];

$factory = new AmqpConnectionFactory($config);
$context = $factory->createContext();

$topic = $context->createTopic('test.amqp.ext');
$topic->addFlag(AMQP_DURABLE);
$topic->setType(AMQP_EX_TYPE_FANOUT);
$topic->setArguments(['alternate-exchange' => 'foo']);

$context->deleteTopic($topic);
$context->declareTopic($topic);

$fooQueue = $context->createQueue('foo');
$fooQueue->addFlag(AMQP_DURABLE);

$context->deleteQueue($fooQueue);
$context->declareQueue($fooQueue);

$context->bind($topic, $fooQueue);

$barQueue = $context->createQueue('bar');
$barQueue->addFlag(AMQP_DURABLE);

$context->deleteQueue($barQueue);
$context->declareQueue($barQueue);

$context->bind($topic, $barQueue);

$message = $context->createMessage('Hello Bar!');

while (true) {
    $context->createProducer()->send($fooQueue, $message);
    $context->createProducer()->send($barQueue, $message);
}

echo 'Done'."\n";
