# Client. Supported brokers

Here's the list of protocols and Client features supported by them 

| Protocol       | Priority | Delay    | Expiration | Setup broker | Message bus |
|:--------------:|:--------:|:--------:|:----------:|:------------:|:-----------:|
| AMQP           |   No     |    No    |    Yes     |     Yes      |     Yes     |        
| RabbitMQ AMQP  |   Yes    |    Yes*  |    Yes     |     Yes      |     Yes     |
| STOMP          |   No     |    No    |    Yes     |     No       |     Yes**   |
| RabbitMQ STOMP |   Yes    |    Yes*  |    Yes     |     Yes***   |     Yes**   |
| Filesystem     |   No     |    No    |    No      |     Yes      |     No      |
| Redis          |   No     |    No    |    No      |  Not needed  |     No      |
| Doctrine DBAL  |   Yes    |    Yes   |    No      |     Yes      |     No      |
| AWS SQS        |   No     |    Yes   |    No      |     Yes      |   Not impl  |

* \* Possible if a RabbitMQ delay plugin is installed.
* \*\* Possible if topics (exchanges) are configured on broker side manually.
* \*\*\* Possible if RabbitMQ Managment Plugin is installed.

[back to index](../index.md)
