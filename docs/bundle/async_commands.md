---
layout: default
parent: "Symfony bundle"
title: Async commands
nav_order: 7
---
{% include support.md %}

# Async commands

## Installation

```bash
$ composer require enqueue/async-command:0.9.x-dev
```

## Configuration

```yaml
# config/packages/enqueue_async_commands.yaml

enqueue:
    default:
        async_commands:
            enabled: true
            timeout: 60
            command_name: ~
            queue_name: ~
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

$cmd = new RunCommand('debug:container', ['--tag=form.type']);
$producer->sendCommand(Commands::RUN_COMMAND, $cmd);
```

optionally you can get a command execution result:

```php
<?php

use Enqueue\Client\ProducerInterface;
use Enqueue\AsyncCommand\CommandResult;
use Enqueue\AsyncCommand\Commands;
use Enqueue\AsyncCommand\RunCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @var $container ContainerInterface */

/** @var ProducerInterface $producer */
$producer = $container->get(ProducerInterface::class);

$promise = $producer->sendCommand(Commands::RUN_COMMAND, new RunCommand('debug:container'), true);

// do other stuff.

if ($replyMessage = $promise->receive(5000)) {
    $result = CommandResult::jsonUnserialize($replyMessage->getBody());

    echo $result->getOutput();
}
```

[back to index](index.md)
