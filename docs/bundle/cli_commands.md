---
layout: default
parent: "Symfony bundle"
title: CLI commands
nav_order: 3
---
{% include support.md %}

# Cli commands

The EnqueueBundle provides several commands.
The most useful one `enqueue:consume` connects to the broker and process the messages.
Other commands could be useful during debugging (like `enqueue:topics`) or deployment (like `enqueue:setup-broker`).

* [enqueue:consume](#enqueueconsume)
* [enqueue:produce](#enqueueproduce)
* [enqueue:setup-broker](#enqueuesetup-broker)
* [enqueue:routes](#enqueueroutes)
* [enqueue:transport:consume](#enqueuetransportconsume)

## enqueue:consume

```
./bin/console enqueue:consume --help
Usage:
  enqueue:consume [options] [--] [<client-queue-names>]...
  enq:c

Arguments:
  client-queue-names                     Queues to consume messages from

Options:
      --message-limit=MESSAGE-LIMIT      Consume n messages and exit
      --time-limit=TIME-LIMIT            Consume messages during this time
      --memory-limit=MEMORY-LIMIT        Consume messages until process reaches this memory limit in MB
      --niceness=NICENESS                Set process niceness
      --setup-broker                     Creates queues, topics, exchanges, binding etc on broker side.
      --receive-timeout=RECEIVE-TIMEOUT  The time in milliseconds queue consumer waits for a message.
      --logger[=LOGGER]                  A logger to be used. Could be "default", "null", "stdout". [default: "default"]
      --skip[=SKIP]                      Queues to skip consumption of messages from (multiple values allowed)
  -c, --client[=CLIENT]                  The client to consume messages from. [default: "default"]
  -h, --help                             Display this help message
  -q, --quiet                            Do not output any message
  -V, --version                          Display this application version
      --ansi                             Force ANSI output
      --no-ansi                          Disable ANSI output
  -n, --no-interaction                   Do not ask any interactive question
  -e, --env=ENV                          The Environment name. [default: "test"]
      --no-debug                         Switches off debug mode.
  -v|vv|vvv, --verbose                   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  A client's worker that processes messages. By default it connects to default queue. It select an appropriate message processor based on a message headers
```

## enqueue:produce

```
./bin/console enqueue:produce --help
Usage:
  enqueue:produce [options] [--] <message>

Arguments:
  message                  A message

Options:
  -c, --client[=CLIENT]    The client to send messages to. [default: "default"]
      --topic[=TOPIC]      The topic to send a message to
      --command[=COMMAND]  The command to send a message to
  -h, --help               Display this help message
  -q, --quiet              Do not output any message
  -V, --version            Display this application version
      --ansi               Force ANSI output
      --no-ansi            Disable ANSI output
  -n, --no-interaction     Do not ask any interactive question
  -e, --env=ENV            The Environment name. [default: "test"]
      --no-debug           Switches off debug mode.
  -v|vv|vvv, --verbose     Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Sends an event to the topic

```

## enqueue:setup-broker

```
./bin/console enqueue:setup-broker --help
Usage:
  enqueue:setup-broker [options]
  enq:sb

Options:
  -c, --client[=CLIENT]  The client to consume messages from. [default: "default"]
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -e, --env=ENV          The Environment name. [default: "test"]
      --no-debug         Switches off debug mode.
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Setup broker. Configure the broker, creates queues, topics and so on.
```

## enqueue:routes

```
./bin/console enqueue:routes --help
Usage:
  enqueue:routes [options]
  debug:enqueue:routes

Options:
      --show-route-options  Adds ability to hide options.
  -c, --client[=CLIENT]     The client to consume messages from. [default: "default"]
  -h, --help                Display this help message
  -q, --quiet               Do not output any message
  -V, --version             Display this application version
      --ansi                Force ANSI output
      --no-ansi             Disable ANSI output
  -n, --no-interaction      Do not ask any interactive question
  -e, --env=ENV             The Environment name. [default: "test"]
      --no-debug            Switches off debug mode.
  -v|vv|vvv, --verbose      Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  A command lists all registered routes.
```

## enqueue:transport:consume

```
./bin/console enqueue:transport:consume --help
Usage:
  enqueue:transport:consume [options] [--] <processor> [<queues>]...

Arguments:
  processor                              A message processor.
  queues                                 A queue to consume from

Options:
      --message-limit=MESSAGE-LIMIT      Consume n messages and exit
      --time-limit=TIME-LIMIT            Consume messages during this time
      --memory-limit=MEMORY-LIMIT        Consume messages until process reaches this memory limit in MB
      --niceness=NICENESS                Set process niceness
      --receive-timeout=RECEIVE-TIMEOUT  The time in milliseconds queue consumer waits for a message.
      --logger[=LOGGER]                  A logger to be used. Could be "default", "null", "stdout". [default: "default"]
  -t, --transport[=TRANSPORT]            The transport to consume messages from. [default: "default"]
  -h, --help                             Display this help message
  -q, --quiet                            Do not output any message
  -V, --version                          Display this application version
      --ansi                             Force ANSI output
      --no-ansi                          Disable ANSI output
  -n, --no-interaction                   Do not ask any interactive question
  -e, --env=ENV                          The Environment name. [default: "test"]
      --no-debug                         Switches off debug mode.
  -v|vv|vvv, --verbose                   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  A worker that consumes message from a broker. To use this broker you have to explicitly set a queue to consume from and a message processor service
```

[back to index](index.md)
