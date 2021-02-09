<p align="center"><a href="https://php-enqueue.github.io/" target="_blank"><img width="700" src="https://github.com/php-enqueue/logos/blob/master/Enqueue%20logo.png" alt="Enqueue logo" /></a></p>

<p align="center">
  <a href="https://gitter.im/php-enqueue/Lobby"><img src="https://badges.gitter.im/php-enqueue/Lobby.svg" alt="Enqueue Chat"></a>
  <a href="https://github.com/php-enqueue/enqueue-dev/actions?query=workflow%3ACI"><img src="https://img.shields.io/github/workflow/status/php-enqueue/enqueue-dev/CI" alt="Build Status"></a>
  <a href="https://packagist.org/packages/enqueue/enqueue/stats"><img src="https://poser.pugx.org/enqueue/enqueue/d/total.png?branch=master" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/enqueue/enqueue"><img src="https://poser.pugx.org/enqueue/enqueue/version.png" alt="Latest Stable Version"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
</p>

<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

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
    * [AMQP(s)](https://php-enqueue.github.io/transport/amqp/) based on [PHP AMQP extension](https://github.com/pdezwart/php-amqp)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/amqp-ext/CI)](https://github.com/php-enqueue/amqp-ext/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-ext/d/total.png)](https://packagist.org/packages/enqueue/amqp-ext/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-ext/version.png)](https://packagist.org/packages/enqueue/amqp-ext)
    * [AMQP](https://php-enqueue.github.io/transport/amqp_bunny/) based on [bunny](https://github.com/jakubkulhan/bunny)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/amqp-bunny/CI)](https://github.com/php-enqueue/amqp-bunny/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-bunny/d/total.png)](https://packagist.org/packages/enqueue/amqp-bunny/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-bunny/version.png)](https://packagist.org/packages/enqueue/amqp-bunny)
    * [AMQP(s)](https://php-enqueue.github.io/transport/amqp_lib/) based on [php-amqplib](https://github.com/php-amqplib/php-amqplib)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/amqp-lib/CI)](https://github.com/php-enqueue/amqp-lib/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/amqp-lib/d/total.png)](https://packagist.org/packages/enqueue/amqp-lib/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/amqp-lib/version.png)](https://packagist.org/packages/enqueue/amqp-lib)
    * [Beanstalk](https://php-enqueue.github.io/transport/pheanstalk/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/pheanstalk/CI)](https://github.com/php-enqueue/pheanstalk/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/pheanstalk/d/total.png)](https://packagist.org/packages/enqueue/pheanstalk/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/pheanstalk/version.png)](https://packagist.org/packages/enqueue/pheanstalk)
    * [STOMP](https://php-enqueue.github.io/transport/stomp/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/stomp/CI)](https://github.com/php-enqueue/stomp/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/stomp/d/total.png)](https://packagist.org/packages/enqueue/stomp/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/stomp/version.png)](https://packagist.org/packages/enqueue/stomp)
    * [Amazon SQS](https://php-enqueue.github.io/transport/sqs/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/sqs/CI)](https://github.com/php-enqueue/sqs/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/sqs/d/total.png)](https://packagist.org/packages/enqueue/sqs/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/sqs/version.png)](https://packagist.org/packages/enqueue/sqs)
    * [Amazon SNS](https://php-enqueue.github.io/transport/sns/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/sns/CI)](https://github.com/php-enqueue/sns/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/sns/d/total.png)](https://packagist.org/packages/enqueue/sns/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/sns/version.png)](https://packagist.org/packages/enqueue/sns)
    * [Amazon SNS\SQS](https://php-enqueue.github.io/transport/snsqs/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/snsqs/CI)](https://github.com/php-enqueue/snsqs/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/snsqs/d/total.png)](https://packagist.org/packages/enqueue/snsqs/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/snsqs/version.png)](https://packagist.org/packages/enqueue/snsqs)
    * [Google PubSub](https://php-enqueue.github.io/transport/gps/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/gps/CI)](https://github.com/php-enqueue/gps/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/gps/d/total.png)](https://packagist.org/packages/enqueue/gps/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/gps/version.png)](https://packagist.org/packages/enqueue/gps)
    * [Kafka](https://php-enqueue.github.io/transport/kafka/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/rdkafka/CI)](https://github.com/php-enqueue/rdkafka/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/rdkafka/d/total.png)](https://packagist.org/packages/enqueue/rdkafka/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/rdkafka/version.png)](https://packagist.org/packages/enqueue/rdkafka)
    * [Redis](https://php-enqueue.github.io/transport/redis/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/redis/CI)](https://github.com/php-enqueue/redis/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/redis/d/total.png)](https://packagist.org/packages/enqueue/redis/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/redis/version.png)](https://packagist.org/packages/enqueue/redis)
    * [Gearman](https://php-enqueue.github.io/transport/gearman/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/gearman/CI)](https://github.com/php-enqueue/gearman/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/gearman/d/total.png)](https://packagist.org/packages/enqueue/gearman/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/gearman/version.png)](https://packagist.org/packages/enqueue/gearman)
    * [Doctrine DBAL](https://php-enqueue.github.io/transport/dbal/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/dbal/CI)](https://github.com/php-enqueue/dbal/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/dbal/d/total.png)](https://packagist.org/packages/enqueue/dbal/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/dbal/version.png)](https://packagist.org/packages/enqueue/dbal)
    * [Filesystem](https://php-enqueue.github.io/transport/filesystem/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/fs/CI)](https://github.com/php-enqueue/fs/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/fs/d/total.png)](https://packagist.org/packages/enqueue/fs/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/fs/version.png)](https://packagist.org/packages/enqueue/fs)
    * [Mongodb](https://php-enqueue.github.io/transport/mongodb/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/mongodb/CI)](https://github.com/php-enqueue/mongodb/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/mongodb/d/total.png)](https://packagist.org/packages/enqueue/mongodb/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/mongodb/version.png)](https://packagist.org/packages/enqueue/mongodb)
    * [WAMP](https://php-enqueue.github.io/transport/wamp/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/wamp/CI)](https://github.com/php-enqueue/wamp/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/wamp/d/total.png)](https://packagist.org/packages/enqueue/wamp/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/wamp/version.png)](https://packagist.org/packages/enqueue/wamp)
    * [Null](https://php-enqueue.github.io/transport/null/)
[![Build Status](https://img.shields.io/github/workflow/status/php-enqueue/null/CI)](https://github.com/php-enqueue/null/actions?query=workflow%3ACI)
[![Total Downloads](https://poser.pugx.org/enqueue/null/d/total.png)](https://packagist.org/packages/enqueue/null/stats)
[![Latest Stable Version](https://poser.pugx.org/enqueue/null/version.png)](https://packagist.org/packages/enqueue/null)
    * [the others are coming](https://github.com/php-enqueue/enqueue-dev/issues/284)
* [Symfony bundle](https://php-enqueue.github.io/bundle/quick_tour/)
* [Magento1 extension](https://php-enqueue.github.io/magento/quick_tour/)
* [Magento2 module](https://php-enqueue.github.io/magento2/quick_tour/)
* [Laravel extension](https://php-enqueue.github.io/laravel/quick_tour/)
* [Yii2. Amqp driver](https://php-enqueue.github.io/yii/amqp_driver/)
* [Message bus](https://php-enqueue.github.io/quick_tour/#client) support.
* [RPC over MQ](https://php-enqueue.github.io/quick_tour/#remote-procedure-call-rpc) support.
* [Monitoring](https://php-enqueue.github.io/monitoring/)
* Temporary queues support.
* Well designed, decoupled and reusable components.
* Carefully tested (unit & functional).
* For more visit [quick tour](https://php-enqueue.github.io/quick_tour/).

## Resources

* [Site](https://enqueue.forma-pro.com/)
* [Quick tour](https://php-enqueue.github.io/quick_tour/)
* [Documentation](https://php-enqueue.github.io/)
* [Blog](https://php-enqueue.github.io/#blogs)
* [Chat\Questions](https://gitter.im/php-enqueue/Lobby)
* [Issue Tracker](https://github.com/php-enqueue/enqueue-dev/issues)

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development.
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience.
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).
