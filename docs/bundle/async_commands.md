---
layout: default
parent: "Symfony bundle"
title: Async commands
nav_order: 7
---
<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

[back to index](../index.md)
