<p align="center"><img width="700" src="https://github.com/php-enqueue/logos/blob/master/Enqueue%20logo.png" alt="Enqueue logo" /></p>

<p align="center">
  <a href="https://gitter.im/php-enqueue/Lobby"><img src="https://badges.gitter.im/php-enqueue/Lobby.svg" alt="Enqueue Chat"></a>
  <a href="https://travis-ci.org/php-enqueue/enqueue-dev"><img src="https://travis-ci.org/php-enqueue/enqueue-dev.png?branch=master" alt="Build Status"></a>
  <a href="https://packagist.org/packages/enqueue/enqueue/stats"><img src="https://poser.pugx.org/enqueue/enqueue/d/total.png?branch=master" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/enqueue/enqueue"><img src="https://poser.pugx.org/enqueue/enqueue/version.png" alt="Latest Stable Version"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

## Introduction

**Enqueue** is production ready, battle-tested messaging solution for PHP. Provides a common way for programs to create, send, read messages. 

This is a main development repository. It provides a friendly environment for productive development and testing of all Enqueue related features&packages.

Features:

* [Feature rich](docs/quick_tour.md).

* Adopts [queue interoperable](https://github.com/queue-interop/queue-interop) interfaces (inspired by [Java JMS](https://docs.oracle.com/javaee/7/api/javax/jms/package-summary.html)).
* Battle-tested. Used in production.
* Supported  transports 
    * [AMQP(s)](docs/transport/amqp.md) based on [PHP AMQP extension](https://github.com/pdezwart/php-amqp)
[![Build Status](https://travis-ci.org/php-enqueue/amqp-ext.png?branch=master)](https://travis-ci.org/php-enqueue/amqp-ext)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-ext/d/total.png)](https://packagist.org/packages/enqueue/amqp-ext/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-ext/version.png)](https://packagist.org/packages/enqueue/amqp-ext)
    * [AMQP](docs/transport/amqp_bunny.md) based on [bunny](https://github.com/jakubkulhan/bunny)
[![Build Status](https://travis-ci.org/php-enqueue/amqp-bunny.png?branch=master)](https://travis-ci.org/php-enqueue/amqp-bunny)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-bunny/d/total.png)](https://packagist.org/packages/enqueue/amqp-bunny/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-bunny/version.png)](https://packagist.org/packages/enqueue/amqp-bunny)
    * [AMQP(s)](docs/transport/amqp_lib.md) based on [php-amqplib](https://github.com/php-amqplib/php-amqplib)
[![Build Status](https://travis-ci.org/php-enqueue/amqp-lib.png?branch=master)](https://travis-ci.org/php-enqueue/amqp-lib)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-lib/d/total.png)](https://packagist.org/packages/enqueue/amqp-lib/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-lib/version.png)](https://packagist.org/packages/enqueue/amqp-lib)
    * [Beanstalk](docs/transport/pheanstalk.md)
[![Build Status](https://travis-ci.org/php-enqueue/pheanstalk.png?branch=master)](https://travis-ci.org/php-enqueue/pheanstalk)
[![Total Downloads](https://poser.pugx.org/enqueue/pheanstalk/d/total.png)](https://packagist.org/packages/enqueue/pheanstalk/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/pheanstalk/version.png)](https://packagist.org/packages/enqueue/pheanstalk)
    * [STOMP](docs/transport/stomp.md)
[![Build Status](https://travis-ci.org/php-enqueue/stomp.png?branch=master)](https://travis-ci.org/php-enqueue/stomp)
[![Total Downloads](https://poser.pugx.org/enqueue/stomp/d/total.png)](https://packagist.org/packages/enqueue/stomp/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/stomp/version.png)](https://packagist.org/packages/enqueue/stomp)
    * [Amazon SQS](docs/transport/sqs.md)
[![Build Status](https://travis-ci.org/php-enqueue/sqs.png?branch=master)](https://travis-ci.org/php-enqueue/sqs)
[![Total Downloads](https://poser.pugx.org/enqueue/sqs/d/total.png)](https://packagist.org/packages/enqueue/sqs/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/sqs/version.png)](https://packagist.org/packages/enqueue/sqs)
    * [Google PubSub](docs/transport/gps.md)
[![Build Status](https://travis-ci.org/php-enqueue/gps.png?branch=master)](https://travis-ci.org/php-enqueue/gps)
[![Total Downloads](https://poser.pugx.org/enqueue/gps/d/total.png)](https://packagist.org/packages/enqueue/gps/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/gps/version.png)](https://packagist.org/packages/enqueue/gps)
    * [Kafka](docs/transport/kafka.md)
[![Build Status](https://travis-ci.org/php-enqueue/rdkafka.png?branch=master)](https://travis-ci.org/php-enqueue/rdkafka)
[![Total Downloads](https://poser.pugx.org/enqueue/rdkafka/d/total.png)](https://packagist.org/packages/enqueue/rdkafka/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/rdkafka/version.png)](https://packagist.org/packages/enqueue/rdkafka)
    * [Redis](docs/transport/redis.md)
[![Build Status](https://travis-ci.org/php-enqueue/redis.png?branch=master)](https://travis-ci.org/php-enqueue/redis)
[![Total Downloads](https://poser.pugx.org/enqueue/redis/d/total.png)](https://packagist.org/packages/enqueue/redis/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/redis/version.png)](https://packagist.org/packages/enqueue/redis)
    * [Gearman](docs/transport/gearman.md)
[![Build Status](https://travis-ci.org/php-enqueue/gearman.png?branch=master)](https://travis-ci.org/php-enqueue/gearman)
[![Total Downloads](https://poser.pugx.org/enqueue/gearman/d/total.png)](https://packagist.org/packages/enqueue/gearman/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/gearman/version.png)](https://packagist.org/packages/enqueue/gearman)
    * [Doctrine DBAL](docs/transport/dbal.md)
[![Build Status](https://travis-ci.org/php-enqueue/dbal.png?branch=master)](https://travis-ci.org/php-enqueue/dbal)
[![Total Downloads](https://poser.pugx.org/enqueue/dbal/d/total.png)](https://packagist.org/packages/enqueue/dbal/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/dbal/version.png)](https://packagist.org/packages/enqueue/dbal)
    * [Filesystem](docs/transport/filesystem.md)
[![Build Status](https://travis-ci.org/php-enqueue/fs.png?branch=master)](https://travis-ci.org/php-enqueue/fs)
[![Total Downloads](https://poser.pugx.org/enqueue/fs/d/total.png)](https://packagist.org/packages/enqueue/fs/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/fs/version.png)](https://packagist.org/packages/enqueue/fs)
    * [Mongodb](docs/transport/mongodb.md)
[![Build Status](https://travis-ci.org/php-enqueue/mongodb.png?branch=master)](https://travis-ci.org/php-enqueue/mongodb)
[![Total Downloads](https://poser.pugx.org/enqueue/mongodb/d/total.png)](https://packagist.org/packages/enqueue/mongodb/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/mongodb/version.png)](https://packagist.org/packages/enqueue/mongodb)
    * [WAMP](docs/transport/wamp.md)
[![Build Status](https://travis-ci.org/php-enqueue/wamp.png?branch=master)](https://travis-ci.org/php-enqueue/wamp)
[![Total Downloads](https://poser.pugx.org/enqueue/wamp/d/total.png)](https://packagist.org/packages/enqueue/wamp/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/wamp/version.png)](https://packagist.org/packages/enqueue/wamp)
    * [Null](docs/transport/null.md)
[![Build Status](https://travis-ci.org/php-enqueue/null.png?branch=master)](https://travis-ci.org/php-enqueue/null)
[![Total Downloads](https://poser.pugx.org/enqueue/null/d/total.png)](https://packagist.org/packages/enqueue/null/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/null/version.png)](https://packagist.org/packages/enqueue/null)
    * [the others are coming](https://github.com/php-enqueue/enqueue-dev/issues/284)
* [Symfony bundle](docs/bundle/quick_tour.md)
* [Magento1 extension](docs/magento/quick_tour.md)
* [Magento2 module](docs/magento2/quick_tour.md)
* [Laravel extension](docs/laravel/quick_tour.md)
* [Yii2. Amqp driver](docs/yii/amqp_driver.md)
* [Message bus](docs/quick_tour.md#client) support.
* [RPC over MQ](docs/quick_tour.md#remote-procedure-call-rpc) support.
* [Monitoring](docs/monitoring.md)
* Temporary queues support.
* Well designed, decoupled and reusable components.
* Carefully tested (unit & functional).
* For more visit [quick tour](docs/quick_tour.md).

## Resources

* [Site](https://enqueue.forma-pro.com/)
* [Quick tour](docs/quick_tour.md)
* [Documentation](docs/index.md)
* [Blog](docs/index.md#blogs)
* [Chat\Questions](https://gitter.im/php-enqueue/Lobby)
* [Issue Tracker](https://github.com/php-enqueue/enqueue-dev/issues)

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).
