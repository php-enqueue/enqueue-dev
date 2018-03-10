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

use Enqueue\Dbal\DbalConnectionFactory;

$config = [
    'connection' => [
        'dbname' => getenv('DOCTRINE_DB_NAME'),
        'user' => getenv('DOCTRINE_USER'),
        'password' => getenv('DOCTRINE_PASSWORD'),
        'host' => getenv('DOCTRINE_HOST'),
        'port' => getenv('DOCTRINE_PORT'),
        'driver' => getenv('DOCTRINE_DRIVER'),
    ],
];

$factory = new DbalConnectionFactory($config);
$context = $factory->createContext();
$context->createDataBaseTable();

$destination = $context->createTopic('destination');

$consumer = $context->createConsumer($destination);

while (true) {
    if ($m = $consumer->receive(1000)) {
        $consumer->acknowledge($m);
        echo 'Received message: '.$m->getBody().PHP_EOL;
    }
}

echo 'Done'."\n";
