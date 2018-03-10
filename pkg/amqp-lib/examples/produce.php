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
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;

$config = [
    'host' => getenv('RABBITMQ_HOST'),
    'port' => getenv('RABBITMQ_AMQP__PORT'),
    'user' => getenv('RABBITMQ_USER'),
    'pass' => getenv('RABBITMQ_PASSWORD'),
    'vhost' => getenv('RABBITMQ_VHOST'),
];

$factory = new AmqpConnectionFactory($config);
$context = $factory->createContext();

$topic = $context->createTopic('test.amqp.ext');
$topic->addFlag(AmqpTopic::FLAG_DURABLE);
$topic->setType(AmqpTopic::TYPE_FANOUT);
//$topic->setArguments(['alternate-exchange' => 'foo']);

$context->deleteTopic($topic);
$context->declareTopic($topic);

$fooQueue = $context->createQueue('foo');
$fooQueue->addFlag(AmqpQueue::FLAG_DURABLE);

$context->deleteQueue($fooQueue);
$context->declareQueue($fooQueue);

$context->bind(new AmqpBind($topic, $fooQueue));

$barQueue = $context->createQueue('bar');
$barQueue->addFlag(AmqpQueue::FLAG_DURABLE);

$context->deleteQueue($barQueue);
$context->declareQueue($barQueue);

$context->bind(new AmqpBind($topic, $barQueue));

$message = $context->createMessage('Hello Bar!');

while (true) {
    $context->createProducer()->send($fooQueue, $message);
    $context->createProducer()->send($barQueue, $message);
}

echo 'Done'."\n";
