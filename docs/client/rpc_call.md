# Client. RPC call


## The client side

There is a handy class RpcClient shipped with the client component. 
It allows you to easily send a message and wait for a reply.
 
```php
<?php
use Enqueue\Client\SimpleClient;
use Enqueue\Client\RpcClient;

/** @var \Enqueue\Psr\PsrContext $context */


$client = new SimpleClient($context);
$rpcClient = new RpcClient($client->getProducer(), $context);

$replyMessage = $rpcClient->call('greeting_topic', 'Hi Thomas!', 5);
```

You can perform several requests asynchronously with `callAsync` and request replays later.
 
```php
<?php
use Enqueue\Client\SimpleClient;
use Enqueue\Client\RpcClient;

/** @var \Enqueue\Psr\PsrContext $context */


$client = new SimpleClient($context);
$rpcClient = new RpcClient($client->getProducer(), $context);

$promises = [];
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);

$replyMessages = [];
foreach ($promises as $promise) {
    $replyMessages[] = $promise->getMessage();
}
```

## The server side

On the server side you may register a processor which returns a result object with a reply message set.
Of course it is possible to implement rpc server side based on transport classes only. That would require a bit more work to do. 

```php
<?php

use Enqueue\Client\SimpleClient;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ReplyExtension;

/** @var \Enqueue\Psr\PsrContext $context */

$client = new SimpleClient($this->context);
$client->bind('greeting_topic', 'greeting_processor', function (PsrMessage $message, PsrContext $context) use (&$requestMessage) {
    echo $message->getBody();
    
    return Result::reply($context->createMessage('Hi there! I am John.'));
});

$client->consume(new ChainExtension([new ReplyExtension()]));
```

[back to index](../index.md)
