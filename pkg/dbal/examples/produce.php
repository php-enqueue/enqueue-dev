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

$config = array(
    'connection' => [
        'dbname' => getenv('SYMFONY__DB__NAME'),
        'user' => getenv('SYMFONY__DB__USER'),
        'password' => getenv('SYMFONY__DB__PASSWORD'),
        'host' => getenv('SYMFONY__DB__HOST'),
        'port' => getenv('SYMFONY__DB__PORT'),
        'driver' => getenv('SYMFONY__DB__DRIVER'),
    ],
);

$factory = new DbalConnectionFactory($config);
$context = $factory->createContext();
$context->createDataBaseTable();

$destination = $context->createTopic('destination');

$message = $context->createMessage('Hello Bar!');

while (true) {
    $context->createProducer()->send($destination, $message);
    echo 'Sent message: ' . $message->getBody() . PHP_EOL;
    sleep(1);
}

echo 'Done'."\n";
