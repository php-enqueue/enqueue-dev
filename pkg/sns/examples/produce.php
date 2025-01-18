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
    throw new LogicException('Composer autoload was not found');
}

use Enqueue\Sns\SnsConnectionFactory;

$factory = new SnsConnectionFactory([
    'key' => getenv('ENQUEUE_AWS__SQS__KEY'),
    'secret' => getenv('ENQUEUE_AWS__SQS__SECRET'),
    'region' => getenv('ENQUEUE_AWS__SQS__REGION'),
]);
$context = $factory->createContext();

$topic = $context->createTopic('test_enqueue');
$context->declareTopic($topic);

$message = $context->createMessage('a_body');
$message->setProperty('aProp', 'aPropVal');
$message->setHeader('aHeader', 'aHeaderVal');

$context->createProducer()->send($topic, $message);
