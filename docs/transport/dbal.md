# Doctrine DBAL transport

The transport uses [Doctrine DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/) library and SQL like server as a broker. 
It creates a table there. Pushes and pops messages to\from that table. 
 
**Limitations** It works only in auto ack mode hence If consumer crashes the message is lost.  

* [Installation](#installation)
* [Init database](#init-database)
* [Create context](#create-context)
* [Send message to topic](#send-message-to-topic)
* [Send message to queue](#send-message-to-queue)
* [Consume message](#consume-message)

## Installation

```bash
$ composer require enqueue/dbal
```

## Create context

* With config (a connection is created internally):

```php
<?php
use Enqueue\Dbal\DbalConnectionFactory;

$factory = new DbalConnectionFactory('mysql://user:pass@localhost:3306/mqdev');

// connects to localhost
$factory = new DbalConnectionFactory('mysql:');

$psrContext = $factory->createContext();
```

* With existing connection:

```php
<?php
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Doctrine\Common\Persistence\ManagerRegistry;

/** @var ManagerRegistry $registry */

$factory = new ManagerRegistryConnectionFactory($registry, [
    'connection_name' => 'default',
]);

$psrContext = $factory->createContext();

// if you have enqueue/enqueue library installed you can use a function from there to create the context
$psrContext = \Enqueue\dsn_to_context('mysql:');
```

## Init database

At first time you have to create a table where your message will live. There is a handy methods for this `createDataBaseTable` on the context.
Please pay attention to that the database has to be created manually.

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */

$psrContext->createDataBaseTable();
```

## Send message to topic

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */

$fooTopic = $psrContext->createTopic('aTopic');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooTopic, $message);
```

## Send message to queue 

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$message = $psrContext->createMessage('Hello world!');

$psrContext->createProducer()->send($fooQueue, $message);
```

## Consume message:

```php
<?php
/** @var \Enqueue\Dbal\DbalContext $psrContext */

$fooQueue = $psrContext->createQueue('aQueue');
$consumer = $psrContext->createConsumer($fooQueue);

$message = $consumer->receive();

// process a message
```

[back to index](../index.md)