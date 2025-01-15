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

use Enqueue\Dbal\DbalConnectionFactory;

$config = [
    'connection' => [
        'url' => getenv('DOCTRINE_DSN'),
        'driver' => 'pdo_mysql',
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
        echo 'Received message: '.$m->getBody().\PHP_EOL;
    }
}

echo 'Done'."\n";
