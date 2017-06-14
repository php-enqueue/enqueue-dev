# Client. RPC call

The client's [quick tour](quick_tour.md) describes how to get the client object. 
Here we'll use `Enqueue\SimpleClient\SimpleClient` though it is not required.
You can get all that stuff from manually built client or get objects from a container (Symfony).

The simple client could be created like this:

## The client side

There is a handy class RpcClient shipped with the client component. 
It allows you to easily perform [RPC calls](https://en.wikipedia.org/wiki/Remote_procedure_call).
It send a message and wait for a reply.
 
```php
<?php
use Enqueue\Client\RpcClient;
use Enqueue\SimpleClient\SimpleClient;

$client = new SimpleClient('amqp://');

$rpcClient = new RpcClient($client->getProducer(), $context);

$replyMessage = $rpcClient->call('greeting_topic', 'Hi Thomas!', 5);
```

You can perform several requests asynchronously with `callAsync` and request replays later.
 
```php
<?php
use Enqueue\Client\RpcClient;
use Enqueue\SimpleClient\SimpleClient;

$client = new SimpleClient('amqp://');

$rpcClient = new RpcClient($client->getProducer(), $context);

$promises = [];
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);
$promises[] = $rpcClient->callAsync('greeting_topic', 'Hi Thomas!', 5);

$replyMessages = [];
foreach ($promises as $promise) {
    $replyMessages[] = $promise->receive();
}
```

## The server side

On the server side you may register a processor which returns a result object with a reply message set.
Of course it is possible to implement rpc server side based on transport classes only. That would require a bit more work to do. 

```php
<?php

use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\SimpleClient\SimpleClient;

/** @var \Enqueue\Psr\PsrContext $context */

$client = new SimpleClient('amqp://');

$client->bind('greeting_topic', 'greeting_processor', function (PsrMessage $message, PsrContext $context) use (&$requestMessage) {
    echo $message->getBody();
    
    return Result::reply($context->createMessage('Hi there! I am John.'));
});

$client->consume(new ChainExtension([new ReplyExtension()]));
```

[back to index](../index.md)
