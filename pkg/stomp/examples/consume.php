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

use Enqueue\Stomp\StompConnectionFactory;
use Stomp\Exception\ErrorFrameException;

try {
    $factory = new StompConnectionFactory(getenv('RABITMQ_STOMP_DSN'));
    $context = $factory->createContext();

    $destination = $context->createQueue('destination');
    $destination->setDurable(true);
    $destination->setAutoDelete(false);

    $consumer = $context->createConsumer($destination);

    while (true) {
        if ($message = $consumer->receive()) {
            $consumer->acknowledge($message);

            var_dump($message->getBody());
            var_dump($message->getProperties());
            var_dump($message->getHeaders());
            echo '-------------------------------------'.PHP_EOL;
        }
    }
} catch (ErrorFrameException $e) {
    var_dump($e->getFrame());
}
