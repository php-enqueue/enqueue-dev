# Message Queue. Development Repository

[![Gitter](https://badges.gitter.im/php-enqueue/Lobby.svg)](https://gitter.im/php-enqueue/Lobby)
[![Build Status](https://travis-ci.org/php-enqueue/enqueue-dev.png?branch=master)](https://travis-ci.org/php-enqueue/enqueue-dev)

This is where all development happens. The repository provides a friendly environment for productive development and testing of all enqueue related packages.

Features:

* [JMS](https://docs.oracle.com/javaee/7/api/javax/jms/package-summary.html) like transport [abstraction](https://github.com/php-enqueue/psr-queue).
* Feature rich.
* Supports [AMQP](docs/amqp_transport.md) (RabbitMQ, ActiveMQ and others), [STOMP](docs/stomp_transport.md) (RabbitMQ, ActiveMQ and others), [Redis](docs/redis_transport.md), Doctrine DBAL, [Filesystem](docs/filesystem_transport.md), [Null](docs/null_transport.md) transports.
* Generic purpose abstraction level (the transport level).
* "Opinionated" easy to use abstraction level (the client level).
* [Message bus](http://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html) support.
* [RPC over MQ](https://www.rabbitmq.com/tutorials/tutorial-one-php.html) support.
* Temporary queues support.
* Well designed components (decoupled, reusable,).
* Tested with unit and functional tests.
* For more visit [quick tour](docs/quick_tour.md).

## Resources

* [Documentation](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/index.md)
* [Questions](https://gitter.im/php-enqueue/Lobby)
* [Issue Tracker](https://github.com/php-enqueue/enqueue-dev/issues)

## License

It is released under the [MIT License](LICENSE).