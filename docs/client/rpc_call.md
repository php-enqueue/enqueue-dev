<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Client. RPC call

The client's [quick tour](quick_tour.md) describes how to get the client object. 
Here we'll show you how to use Enqueue Client to perform a [RPC call](https://en.wikipedia.org/wiki/Remote_procedure_call).
You can do it by defining a command which returns something.

## The consumer side

On the consumer side we have to register a command processor which computes the result and send it back to the sender.
Pay attention that you have to add reply extension. It wont work without it.

Of course it is possible to implement rpc server side based on transport classes only. That would require a bit more work to do. 

```php
<?php

use Interop\Queue\Message;
use Interop\Queue\Context;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\SimpleClient\SimpleClient;

/** @var \Interop\Queue\Context $context */

// composer require enqueue/amqp-ext # or enqueue/amqp-bunny, enqueue/amqp-lib
$client = new SimpleClient('amqp:');

$client->bindCommand('square', function (Message $message, Context $context) use (&$requestMessage) {
    $number = (int) $message->getBody();
    
    return Result::reply($context->createMessage($number ^ 2));
});

$client->consume(new ChainExtension([new ReplyExtension()]));
```

[back to index](../index.md)

## The sender side

On the sender's side we need a client which send a command and wait for reply messages. 
 
```php
<?php
use Enqueue\SimpleClient\SimpleClient;

$client = new SimpleClient('amqp:');

echo $client->sendCommand('square', 5, true)->receive(5000 /* 5 sec */)->getBody();
```

You can perform several requests asynchronously with `sendCommand` and ask for replays later.
 
```php
<?php
use Enqueue\SimpleClient\SimpleClient;

$client = new SimpleClient('amqp:');

/** @var \Enqueue\Rpc\Promise[] $promises */
$promises = [];
$promises[] = $client->sendCommand('square', 5, true);
$promises[] = $client->sendCommand('square', 10, true);
$promises[] = $client->sendCommand('square', 7, true);
$promises[] = $client->sendCommand('square', 12, true);

$replyMessages = [];
while ($promises) {
    foreach ($promises as $index => $promise) {
        if ($replyMessage = $promise->receiveNoWait()) {
            $replyMessages[$index] = $replyMessage;
            
            unset($promises[$index]);
        }
    }
}
```