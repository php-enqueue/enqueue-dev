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

use Enqueue\Redis\RedisConnectionFactory;

$context = (new RedisConnectionFactory(getenv('PREDIS_DSN')))->createContext();

$queue = $context->createQueue('queue');

$message = $context->createMessage('Hello Bar!', ['key' => 'value'], ['key2' => 'value2']);

while (true) {
    $context->createProducer()->send($queue, $message);
    echo 'Sent message: '.$message->getBody().PHP_EOL;
    sleep(1);
}

echo 'Done'."\n";
