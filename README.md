# Message Queue. Development Repository

[![Gitter](https://badges.gitter.im/php-enqueue/Lobby.svg)](https://gitter.im/php-enqueue/Lobby)
[![Build Status](https://travis-ci.org/php-enqueue/enqueue-dev.png?branch=master)](https://travis-ci.org/php-enqueue/enqueue-dev)

This is where all development happens. The repository provides a friendly environment for productive development and testing of all enqueue related packages.

Features:

* [JMS](https://docs.oracle.com/javaee/7/api/javax/jms/package-summary.html) like transport [abstraction](https://github.com/php-enqueue/psr-queue).
* [Feature rich](docs/quick_tour.md).
* Supports  transports:
    - [AMQP](docs/transport/amqp.md) (RabbitMQ, ActiveMQ and others), 
    - [STOMP](docs/transport/stomp.md)
    - [Amazon SQS](docs/transport/sqs.md)
    - [Redis](docs/transport/redis.md)
    - [Doctrine DBAL](docs/transport/dbal.md)
    - [Filesystem](docs/transport/filesystem.md)
    - [Null](docs/transport/null.md).
* Generic purpose abstraction level (the transport level).

    ```php
    <?php
    use function Enqueue\dsn_to_context;
    use function Enqueue\send_queue;
    use function Enqueue\consume;
    use Enqueue\Psr\PsrMessage;
    use Enqueue\Consumption\Result;

    // composer require enqueue/enqueue enqueue/amqp-ext
        
    $c = dsn_to_context('amqp:://');
  
    send_queue($c, 'a_queue', 'Hello there');
       
    consume($c, 'a_queue', function(PsrMessage $message) {
       $body = $message->getBody();
      
       // to stop consumption: throw new \Enqueue\Consumption\Exception\ConsumptionInterruptedException;  
      
       return Result::ACK;
    });
    ```

* Easy to use abstraction level (the client level).

    ```php
    <?php
    use Enqueue\SimpleClient\SimpleClient;
    use Enqueue\Psr\PsrMessage;
    use Enqueue\Consumption\Result;
    
    // composer require enqueue/simple-client enqueue/fs
    
    $client = new SimpleClient('file://');
    $client->bind('a_topic', 'a_processor', function(PsrMessage $message) {
        $body = $message->getBody();
        
        // to stop consumption: throw new \Enqueue\Consumption\Exception\ConsumptionInterruptedException;
        
        return Result::ACK;
    });
    
    $client->setupBroker();
    
    $client->send('a_topic', 'Hello there');
    
    $client->consume();
    ```

* [Symfony bundle](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/bundle/quick_tour.md)
* [Magento1 extension](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/magento/quick_tour.md)
* [Message bus](http://www.enterpriseintegrationpatterns.com/patterns/messaging/MessageBus.html) support.
* [RPC over MQ](https://www.rabbitmq.com/tutorials/tutorial-one-php.html) support.
* Temporary queues support.
* Well designed components decoupled and reusable.
* Carefully tested including unit and functional tests.
* For more visit [quick tour](docs/quick_tour.md).

## Resources

* [Quick tour](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/quick_tour.md)
* [Documentation](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/index.md)
* [Questions](https://gitter.im/php-enqueue/Lobby)
* [Issue Tracker](https://github.com/php-enqueue/enqueue-dev/issues)

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).
