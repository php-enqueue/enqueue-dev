---
layout: default
nav_exclude: true
---
<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# How to change consume command logger

By default `bin/console enqueue:consume` (or `bin/console enqueue:transport:consume`) command prints messages to output.
The amount of info could be controlled by verbosity option (-v, -vv, -vvv).

In order to change the default logger used by a command you have to register a `LoggerExtension` just before the default one.
The extension asks you for a logger service, so just pass the one you want to use.
Here's how you can do it.

```yaml
// config/services.yaml

services:
    app_logger_extension:
        class: 'Enqueue\Consumption\Extension\LoggerExtension'
        public: false
        arguments: ['@logger']
        tags:
            - { name: 'enqueue.consumption.extension', priority: 255 }

```

The logger extension with the highest priority will set its logger.

[back to index](../../index.md)



