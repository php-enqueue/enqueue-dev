# Async commands

## Installation

```bash
$ composer require enqueue/async-command:^0.8
```

## Configuration

```yaml
# config/packages/enqueue_async_commands.yaml

enqueue:
   async_commands: true
```

## Usage

```php
<?php

use Enqueue\Client\ProducerInterface;
use Enqueue\AsyncCommand\Commands;
use Enqueue\AsyncCommand\RunCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @var $container ContainerInterface */

/** @var ProducerInterface $producer */
$producer = $container->get(ProducerInterface::class);

$producer->sendCommand(Commands::RUN_COMMAND, new RunCommand('debug:container'));
```

optionally you can get a command execution result:

```php
<?php

use Enqueue\Client\ProducerInterface;
use Enqueue\AsyncCommand\RunCommandResult;
use Enqueue\AsyncCommand\Commands;
use Enqueue\AsyncCommand\RunCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @var $container ContainerInterface */

/** @var ProducerInterface $producer */
$producer = $container->get(ProducerInterface::class);

$promise = $producer->sendCommand(Commands::RUN_COMMAND, new RunCommand('debug:container'), true);

// do other stuff.

if ($replyMessage = $promise->receive(5000)) { 
    $result = RunCommandResult::jsonUnserialize($replyMessage->getBody());
    
    echo $result->getOutput();
}
```

[back to index](../index.md)
