<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Client. Supported brokers

Here's the list of transports supported by Enqueue Client:

| Transport           | Package                                                    |  DSN                            |
|:-------------------:|:----------------------------------------------------------:|:-------------------------------:|
| AMQP, RabbitMQ      | [enqueue/amqp-bunny](../transport/amqp_bunny.md)           | amqp: amqp+bunny:               |
| AMQP, RabbitMQ      | [enqueue/amqp-lib](../transport/amqp_lib.md)               | amqp: amqp+lib:                 |
| AMQP, RabbitMQ      | [enqueue/amqp-ext](../transport/amqp.md)                   | amqp: amqp+ext:                 |
| Doctrine DBAL       | [enqueue/dbal](../transport/dbal.md)                       | mysql: pgsql: pdo_pgsql etc     |
| Filesystem          | [enqueue/fs](../transport/fs.md)                           | file:///foo/bar                 |
| Google PubSub       | [enqueue/gps](../transport/gps.md)                         | gps:                            |
| Redis               | [enqueue/redis](../transport/redis.md)                     | redis:                          |
| Amazon SQS          | [enqueue/sqs](../transport/sqs.md)                         | sqs:                            |
| STOMP, RabbitMQ     | [enqueue/stomp](../transport/stomp.md)                     | stomp:                          |
| Kafka               | [enqueue/rdkafka](../transport/kafka.md)                   | kafka:                          |
| Null                | [enqueue/null](../transport/null.md)                       | null:                           |

Here's the list of protocols and Client features supported by them 

| Protocol       | Priority | Delay    | Expiration | Setup broker | Message bus |
|:--------------:|:--------:|:--------:|:----------:|:------------:|:-----------:|
| AMQP           |   No     |    No    |    Yes     |     Yes      |     Yes     |        
| RabbitMQ AMQP  |   Yes    |    Yes   |    Yes     |     Yes      |     Yes     |
| STOMP          |   No     |    No    |    Yes     |     No       |     Yes**   |
| RabbitMQ STOMP |   Yes    |    Yes   |    Yes     |     Yes***   |     Yes**   |
| Filesystem     |   No     |    No    |    No      |     Yes      |     No      |
| Redis          |   No     |    No    |    No      |  Not needed  |     No      |
| Doctrine DBAL  |   Yes    |    Yes   |    No      |     Yes      |     No      |
| Amazon SQS     |   No     |    Yes   |    No      |     Yes      |   Not impl  |
| Kafka          |   No     |    No    |    No      |     Yes      |     No      |
| Google PubSub  | Not impl | Not impl |  Not impl  |     Yes      |   Not impl  |

* \*\* Possible if topics (exchanges) are configured on broker side manually.
* \*\*\* Possible if RabbitMQ Management Plugin is installed.

[back to index](../index.md)
