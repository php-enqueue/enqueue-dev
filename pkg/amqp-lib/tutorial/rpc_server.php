<?php

require_once __DIR__.'/vendor/autoload.php';

use Enqueue\AmqpLib\AmqpConnectionFactory;

$config = [
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'pass' => 'guest',
    'receive_method' => 'basic_consume',
];

function fib($n)
{
    if (0 == $n) {
        return 0;
    }

    if (1 == $n) {
        return 1;
    }

    return fib($n - 1) + fib($n - 2);
}

$connection = new AmqpConnectionFactory($config);
$context = $connection->createContext();
$context->setQos(0, 1, false);

$rpc_queue = $context->createQueue('rpc_queue');
$context->declareQueue($rpc_queue);

$consumer = $context->createConsumer($rpc_queue);

echo " [x] Awaiting RPC requests\n";

while (true) {
    if ($req = $consumer->receive()) {
        $n = (int) ($req->getBody());
        echo ' [.] fib(', $n, ")\n";

        $msg = $context->createMessage((string) fib($n));
        $msg->setCorrelationId($req->getCorrelationId());

        $reply_queue = $context->createQueue($req->getReplyTo());
        $context->createProducer()->send($reply_queue, $msg);

        $consumer->acknowledge($req);
    }
}

$context->close();
