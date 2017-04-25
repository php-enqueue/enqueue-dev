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

use Enqueue\Sqs\SqsConnectionFactory;

$config = [
    'key' => getenv('AWS__SQS__KEY'),
    'secret' => getenv('AWS__SQS__SECRET'),
    'region' => getenv('AWS__SQS__REGION'),
];

$factory = new SqsConnectionFactory($config);
$context = $factory->createContext();

$queue = $context->createQueue('enqueue');
$message = $context->createMessage('Hello Bar!');

$context->declareQueue($queue);

while (true) {
    $context->createProducer()->send($queue, $message);
    echo 'Sent message: ' . $message->getBody() . PHP_EOL;
    sleep(1);
}

echo 'Done'."\n";
