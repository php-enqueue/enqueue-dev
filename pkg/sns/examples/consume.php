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

$factory = new SnsConnectionFactory(getenv('SNS_DSN'));
$context = $factory->createContext();

$queue = $context->createQueue('enqueue');
$consumer = $context->createConsumer($queue);

while (true) {
    if ($m = $consumer->receive(20000)) {
        $consumer->acknowledge($m);
        echo 'Received message: '.$m->getBody().\PHP_EOL;
    }
}

echo 'Done'."\n";
