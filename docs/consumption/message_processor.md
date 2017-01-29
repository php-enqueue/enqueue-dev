# Message processor

The message processor is an object that actually process the message and must return a result status.
Here's example:

```php
<?php
use Enqueue\Psr\Processor;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context) 
    {
        $this->mailer->send('foo@example.com', $message->getBody());
        
        return self::ACK;
    }
}
```

Usually there is no need to catch exceptions. 
The message broker can detect consumer has failed and redeliver the message.
Sometimes you have to reject messages explicitly. 

```php
<?php
use Enqueue\Psr\Processor;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\Util\JSON;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context) 
    {
        $data = JSON::decode($message->getBody());
        if ($user  = $this->userRepository->find($data['userId'])) {
            return self::REJECT;
        }
        
        $this->mailer->send($user->getEmail(), $data['text']);
        
        return self::ACK;
    }
}
```

It is possible to find out whether the message failed previously or not. 
There is `isRedelivered` method for that. 
If it returns true than there was attempt to process message. 
   
```php
<?php
use Enqueue\Psr\Processor;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context) 
    {
        if ($message->isRedelivered()) {
            return self::REQUEUE;
        }
        
        $this->mailer->send('foo@example.com', $message->getBody());
        
        return self::ACK;
    }
}
```

The second argument is your context. You can use it to send messages to other queues\topics.
 
```php
<?php
use Enqueue\Psr\Processor;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context) 
    {
        $this->mailer->send('foo@example.com', $message->getBody());
        
        $queue = $context->createQueue('anotherQueue');
        $message = $context->createMessage('Message has been sent');
        $context->createProducer()->send($queue, $message);
        
        return self::ACK;
    }
}
```

The consumption component provide some useful extensions, for example there is an extension that makes RPC processing simplier.
 
```php
<?php
use Enqueue\Psr\Processor;
use Enqueue\Psr\Message;
use Enqueue\Psr\Context;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Result;

class SendMailProcessor implements Processor
{
    public function process(Message $message, Context $context) 
    {
        $this->mailer->send('foo@example.com', $message->getBody());
        
        $replyMessage = $context->createMessage('Message has been sent');
        
        return Result::reply($replyMessage);
    }
}

/** @var \Enqueue\Psr\Context $psrContext */

$queueConsumer = new QueueConsumer($psrContext, new ChainExtension([
    new ReplyExtension()
]));

$queueConsumer->bind('foo', new SendMailProcessor());

$queueConsumer->consume();
```

[back to index](../index.md)