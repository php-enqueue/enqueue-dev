# Change Log

## [0.7.3](https://github.com/php-enqueue/enqueue-dev/tree/0.7.3) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.2...0.7.3)

## [0.7.2](https://github.com/php-enqueue/enqueue-dev/tree/0.7.2) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.1...0.7.2)

- AmqpConsumer::receiveBasicGet, only one message per timeout consumed [\#159](https://github.com/php-enqueue/enqueue-dev/issues/159)
- Symfony 2.8 compatability issue [\#158](https://github.com/php-enqueue/enqueue-dev/issues/158)

- \[consumption\] adjust receive and idle timeouts [\#165](https://github.com/php-enqueue/enqueue-dev/pull/165) ([makasim](https://github.com/makasim))
- Remove maxDepth option on profiler dump. [\#164](https://github.com/php-enqueue/enqueue-dev/pull/164) ([jenkoian](https://github.com/jenkoian))

## [0.7.1](https://github.com/php-enqueue/enqueue-dev/tree/0.7.1) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.0...0.7.1)

- Symfony bundle doesn't work when sending commands [\#160](https://github.com/php-enqueue/enqueue-dev/issues/160)
- \[amqp-ext\] Server connection error [\#157](https://github.com/php-enqueue/enqueue-dev/issues/157)

- Client fix command routing [\#163](https://github.com/php-enqueue/enqueue-dev/pull/163) ([makasim](https://github.com/makasim))

## [0.7.0](https://github.com/php-enqueue/enqueue-dev/tree/0.7.0) (2017-08-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.2...0.7.0)

- \[producer\] do not throw exception if feature not implemented and null… [\#154](https://github.com/php-enqueue/enqueue-dev/pull/154) ([makasim](https://github.com/makasim))
- Amqp bunny [\#153](https://github.com/php-enqueue/enqueue-dev/pull/153) ([makasim](https://github.com/makasim))

- \\[producer\\] do not throw exception if feature not implemented and null… [\#154](https://github.com/php-enqueue/enqueue-dev/pull/154) ([makasim](https://github.com/makasim))

- \[amqp\] Move client related code to Enqueue\Client\Amqp namespace. [\#143](https://github.com/php-enqueue/enqueue-dev/issues/143)
- \[amqp\] What should we do if consumer has already subscribed but smn is trying to change consumer tag? [\#142](https://github.com/php-enqueue/enqueue-dev/issues/142)
- Find a way to retry flaky tests  [\#140](https://github.com/php-enqueue/enqueue-dev/issues/140)
- \[client\] use default topic as  router topic. [\#135](https://github.com/php-enqueue/enqueue-dev/issues/135)

- continue if exclusive is set to false [\#156](https://github.com/php-enqueue/enqueue-dev/pull/156) ([toooni](https://github.com/toooni))
- \[doc\] add elastica populate bundle [\#155](https://github.com/php-enqueue/enqueue-dev/pull/155) ([makasim](https://github.com/makasim))
- \[amqp\] Delay Strategy [\#152](https://github.com/php-enqueue/enqueue-dev/pull/152) ([ASKozienko](https://github.com/ASKozienko))
- \[client\] Use default as router topic. [\#151](https://github.com/php-enqueue/enqueue-dev/pull/151) ([makasim](https://github.com/makasim))
- Amqp Tutorial [\#150](https://github.com/php-enqueue/enqueue-dev/pull/150) ([ASKozienko](https://github.com/ASKozienko))
- Delay, ttl, priority, in producer [\#149](https://github.com/php-enqueue/enqueue-dev/pull/149) ([makasim](https://github.com/makasim))
- \[Amqp\] Qos [\#148](https://github.com/php-enqueue/enqueue-dev/pull/148) ([ASKozienko](https://github.com/ASKozienko))
- amqp interop client [\#144](https://github.com/php-enqueue/enqueue-dev/pull/144) ([ASKozienko](https://github.com/ASKozienko))
- \[composer\] Add extensions to platform config. [\#139](https://github.com/php-enqueue/enqueue-dev/pull/139) ([makasim](https://github.com/makasim))
- Amqp Interop [\#138](https://github.com/php-enqueue/enqueue-dev/pull/138) ([ASKozienko](https://github.com/ASKozienko))

## [0.6.2](https://github.com/php-enqueue/enqueue-dev/tree/0.6.2) (2017-07-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.1...0.6.2)

- Laravel queue package [\#137](https://github.com/php-enqueue/enqueue-dev/pull/137) ([makasim](https://github.com/makasim))
- Add AmqpLib support [\#136](https://github.com/php-enqueue/enqueue-dev/pull/136) ([fibula](https://github.com/fibula))

## [0.6.1](https://github.com/php-enqueue/enqueue-dev/tree/0.6.1) (2017-07-17)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.0...0.6.1)

- RdKafka Transport [\#134](https://github.com/php-enqueue/enqueue-dev/pull/134) ([ASKozienko](https://github.com/ASKozienko))

## [0.6.0](https://github.com/php-enqueue/enqueue-dev/tree/0.6.0) (2017-07-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.3...0.6.0)

- Migrate to queue interop [\#130](https://github.com/php-enqueue/enqueue-dev/pull/130) ([makasim](https://github.com/makasim))

- Remove previously deprecated code. [\#131](https://github.com/php-enqueue/enqueue-dev/pull/131) ([makasim](https://github.com/makasim))

## [0.5.3](https://github.com/php-enqueue/enqueue-dev/tree/0.5.3) (2017-07-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.2...0.5.3)

- \[bundle\] Extend EventDispatcher instead of container aware one. [\#129](https://github.com/php-enqueue/enqueue-dev/pull/129) ([makasim](https://github.com/makasim))

- \[Symfony\] Symfony 3.3 / 4.x compatibility for ProxyEventDispatcher [\#109](https://github.com/php-enqueue/enqueue-dev/issues/109)

## [0.5.2](https://github.com/php-enqueue/enqueue-dev/tree/0.5.2) (2017-07-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.1...0.5.2)

- \[symfony\] Extract DriverFactoryInterface from TransportFactoryInterface. [\#126](https://github.com/php-enqueue/enqueue-dev/pull/126) ([makasim](https://github.com/makasim))

- \[client\] Send exclusive commands to their queues directly, by passing… [\#127](https://github.com/php-enqueue/enqueue-dev/pull/127) ([makasim](https://github.com/makasim))

## [0.5.1](https://github.com/php-enqueue/enqueue-dev/tree/0.5.1) (2017-06-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.0...0.5.1)

- \[doc\] add a doc for client message scopes  [\#56](https://github.com/php-enqueue/enqueue-dev/issues/56)

- \[client\] Command, Event segregation. [\#105](https://github.com/php-enqueue/enqueue-dev/issues/105)

- Add Gearman transport. [\#125](https://github.com/php-enqueue/enqueue-dev/pull/125) ([makasim](https://github.com/makasim))

## [0.5.0](https://github.com/php-enqueue/enqueue-dev/tree/0.5.0) (2017-06-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.20...0.5.0)

- \[WIP\]\[beanstalk\] Add transport for beanstalkd [\#123](https://github.com/php-enqueue/enqueue-dev/pull/123) ([makasim](https://github.com/makasim))

- DBAL Transport: polling\_interval not taken into account [\#121](https://github.com/php-enqueue/enqueue-dev/issues/121)

- \[client\] Merge experimental ProducerV2 methods to Producer interface.  [\#124](https://github.com/php-enqueue/enqueue-dev/pull/124) ([makasim](https://github.com/makasim))
- fix dbal polling interval configuration option [\#122](https://github.com/php-enqueue/enqueue-dev/pull/122) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.20](https://github.com/php-enqueue/enqueue-dev/tree/0.4.20) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.19...0.4.20)

## [0.4.19](https://github.com/php-enqueue/enqueue-dev/tree/0.4.19) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.18...0.4.19)

## [0.4.18](https://github.com/php-enqueue/enqueue-dev/tree/0.4.18) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.17...0.4.18)

- \[client\] Add ability to define a command as exclusive [\#120](https://github.com/php-enqueue/enqueue-dev/pull/120) ([makasim](https://github.com/makasim))

## [0.4.17](https://github.com/php-enqueue/enqueue-dev/tree/0.4.17) (2017-06-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.16...0.4.17)

- \[amqp\] Fixes high CPU consumption when basic get is used [\#117](https://github.com/php-enqueue/enqueue-dev/pull/117) ([makasim](https://github.com/makasim))

- \[RabbitMQ\] High resource usage in AmqpConsumer::receiveBasicGet\(\) [\#116](https://github.com/php-enqueue/enqueue-dev/issues/116)

- \[simple-client\] Allow processor instance bind. [\#119](https://github.com/php-enqueue/enqueue-dev/pull/119) ([makasim](https://github.com/makasim))
- \[amqp\] Add 'receive\_method' to amqp transport factory. [\#118](https://github.com/php-enqueue/enqueue-dev/pull/118) ([makasim](https://github.com/makasim))

## [0.4.16](https://github.com/php-enqueue/enqueue-dev/tree/0.4.16) (2017-06-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.15...0.4.16)

- ProducerV2 For SimpleClient [\#115](https://github.com/php-enqueue/enqueue-dev/pull/115) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.15](https://github.com/php-enqueue/enqueue-dev/tree/0.4.15) (2017-06-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.14...0.4.15)

- Symfony async events. Support event subscribers. [\#94](https://github.com/php-enqueue/enqueue-dev/issues/94)

- RPC Deletes Reply Queue After Receive Message [\#114](https://github.com/php-enqueue/enqueue-dev/pull/114) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.14](https://github.com/php-enqueue/enqueue-dev/tree/0.4.14) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.13...0.4.14)

- \[RFC\]\[client\] Add ability to send events or commands. [\#113](https://github.com/php-enqueue/enqueue-dev/pull/113) ([makasim](https://github.com/makasim))

## [0.4.13](https://github.com/php-enqueue/enqueue-dev/tree/0.4.13) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.12...0.4.13)

- \[amqp\] Consumer always gets the queue the consume callback was called on. [\#110](https://github.com/php-enqueue/enqueue-dev/issues/110)
- \[amqp\] Add ability to choose what receive method to use: basic\_get or basic\_consume. [\#112](https://github.com/php-enqueue/enqueue-dev/pull/112) ([makasim](https://github.com/makasim))

## [0.4.12](https://github.com/php-enqueue/enqueue-dev/tree/0.4.12) (2017-06-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.11...0.4.12)

- \[amqp\] Add pre\_fetch\_count, pre\_fetch\_size options. [\#108](https://github.com/php-enqueue/enqueue-dev/pull/108) ([makasim](https://github.com/makasim))

- \[amqp\]\[hotfix\] Switch to AMQP' basic.get till the issue with basic.consume is solved. [\#111](https://github.com/php-enqueue/enqueue-dev/pull/111) ([makasim](https://github.com/makasim))

## [0.4.11](https://github.com/php-enqueue/enqueue-dev/tree/0.4.11) (2017-05-30)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.10...0.4.11)

- \[amqp\] Get message count [\#64](https://github.com/php-enqueue/enqueue-dev/issues/64)

- \[bundle\] Fix "Incompatible use of dynamic environment variables "ENQUEUE\_DSN" found in parameters." [\#107](https://github.com/php-enqueue/enqueue-dev/pull/107) ([makasim](https://github.com/makasim))

## [0.4.10](https://github.com/php-enqueue/enqueue-dev/tree/0.4.10) (2017-05-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.9...0.4.10)

- Calling AmqpContext::declareQueue\(\) now returns an integer holding the queue message count [\#66](https://github.com/php-enqueue/enqueue-dev/pull/66) ([J7mbo](https://github.com/J7mbo))

- \[RabbitMQ\] support for wildcard topics \("topic exchange"\) [\#65](https://github.com/php-enqueue/enqueue-dev/issues/65)

- \[dbal\] Add DSN support. [\#104](https://github.com/php-enqueue/enqueue-dev/pull/104) ([makasim](https://github.com/makasim))

## [0.4.9](https://github.com/php-enqueue/enqueue-dev/tree/0.4.9) (2017-05-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.8...0.4.9)

- \[client\]\[dx\] Message constructor must accept body, properties and headers.` [\#88](https://github.com/php-enqueue/enqueue-dev/issues/88)
- Add message spec test case [\#102](https://github.com/php-enqueue/enqueue-dev/pull/102) ([makasim](https://github.com/makasim))

- filesystem dsn must have one more /  [\#99](https://github.com/php-enqueue/enqueue-dev/issues/99)

- Code duplication inside messages [\#96](https://github.com/php-enqueue/enqueue-dev/issues/96)

- \[transport\] Fs transport dsn must contain one extra "/" [\#103](https://github.com/php-enqueue/enqueue-dev/pull/103) ([makasim](https://github.com/makasim))

## [0.4.8](https://github.com/php-enqueue/enqueue-dev/tree/0.4.8) (2017-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.6...0.4.8)

- \[client\] Fixes edge cases in client's routing logic. [\#101](https://github.com/php-enqueue/enqueue-dev/pull/101) ([makasim](https://github.com/makasim))
- \[bundle\] Auto register reply extension. [\#100](https://github.com/php-enqueue/enqueue-dev/pull/100) ([makasim](https://github.com/makasim))

- Do pkg release if there are changes in it. [\#98](https://github.com/php-enqueue/enqueue-dev/pull/98) ([makasim](https://github.com/makasim))

## [0.4.6](https://github.com/php-enqueue/enqueue-dev/tree/0.4.6) (2017-05-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.5...0.4.6)

## [0.4.5](https://github.com/php-enqueue/enqueue-dev/tree/0.4.5) (2017-05-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.4...0.4.5)

- Symfony. Async event subscriber. [\#95](https://github.com/php-enqueue/enqueue-dev/pull/95) ([makasim](https://github.com/makasim))

## [0.4.4](https://github.com/php-enqueue/enqueue-dev/tree/0.4.4) (2017-05-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.3...0.4.4)

- Symfony. Async event dispatching  [\#86](https://github.com/php-enqueue/enqueue-dev/pull/86) ([makasim](https://github.com/makasim))

## [0.4.3](https://github.com/php-enqueue/enqueue-dev/tree/0.4.3) (2017-05-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.2...0.4.3)

- \[client\] SpoolProducer [\#93](https://github.com/php-enqueue/enqueue-dev/pull/93) ([makasim](https://github.com/makasim))

- \[Performance, DX\] Add a message pool [\#91](https://github.com/php-enqueue/enqueue-dev/issues/91)
- \[bundle\] Show only part of the message body. Add a button show the whole message body. [\#90](https://github.com/php-enqueue/enqueue-dev/issues/90)

- Add some handy functions. Improve READMEs [\#92](https://github.com/php-enqueue/enqueue-dev/pull/92) ([makasim](https://github.com/makasim))
- Run phpstan and php-cs-fixer on travis  [\#85](https://github.com/php-enqueue/enqueue-dev/pull/85) ([makasim](https://github.com/makasim))

## [0.4.2](https://github.com/php-enqueue/enqueue-dev/tree/0.4.2) (2017-05-15)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.1...0.4.2)

- Add dsn\_to\_connection\_factory and dsn\_to\_context functions. [\#84](https://github.com/php-enqueue/enqueue-dev/pull/84) ([makasim](https://github.com/makasim))
- \[bundle\] Set null transport as default. Prevent errors on bundle install. [\#77](https://github.com/php-enqueue/enqueue-dev/pull/77) ([makasim](https://github.com/makasim))

- Add ability to set transport DSN directly to default transport factory. [\#81](https://github.com/php-enqueue/enqueue-dev/pull/81) ([makasim](https://github.com/makasim))

## [0.4.1](https://github.com/php-enqueue/enqueue-dev/tree/0.4.1) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.0...0.4.1)

## [0.4.0](https://github.com/php-enqueue/enqueue-dev/tree/0.4.0) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.8...0.4.0)

- \[fs\] add DSN support [\#82](https://github.com/php-enqueue/enqueue-dev/pull/82) ([makasim](https://github.com/makasim))
- \[amqp\] Configure by string DSN. [\#80](https://github.com/php-enqueue/enqueue-dev/pull/80) ([makasim](https://github.com/makasim))

- \[Extensions\] extensions priority [\#79](https://github.com/php-enqueue/enqueue-dev/issues/79)

- \[fs\] Filesystem transport must create a storage dir if it does not exists. [\#78](https://github.com/php-enqueue/enqueue-dev/pull/78) ([makasim](https://github.com/makasim))
- \[magento\] Add basic docs for enqueue magento extension. [\#76](https://github.com/php-enqueue/enqueue-dev/pull/76) ([makasim](https://github.com/makasim))

## [0.3.8](https://github.com/php-enqueue/enqueue-dev/tree/0.3.8) (2017-05-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.7...0.3.8)

- Add support for production extensions [\#70](https://github.com/php-enqueue/enqueue-dev/issues/70)

- Multi Transport Simple Client [\#75](https://github.com/php-enqueue/enqueue-dev/pull/75) ([ASKozienko](https://github.com/ASKozienko))
- Client Extensions [\#72](https://github.com/php-enqueue/enqueue-dev/pull/72) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.7](https://github.com/php-enqueue/enqueue-dev/tree/0.3.7) (2017-05-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.6...0.3.7)

- \[rpc\] RpcClient must check existence of createTemporaryQueue. It is not part of transport interface [\#49](https://github.com/php-enqueue/enqueue-dev/issues/49)

- JobQueue/Job shouldn't be required when Doctrine schema update [\#67](https://github.com/php-enqueue/enqueue-dev/issues/67)
- JobQueue/Job shouldn't be required when Doctrine schema update [\#71](https://github.com/php-enqueue/enqueue-dev/pull/71) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.6](https://github.com/php-enqueue/enqueue-dev/tree/0.3.6) (2017-04-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.5...0.3.6)

- Amazon SQS Transport [\#60](https://github.com/php-enqueue/enqueue-dev/pull/60) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.5](https://github.com/php-enqueue/enqueue-dev/tree/0.3.5) (2017-04-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.4...0.3.5)

- \[client\] Queue subscriber interface. [\#53](https://github.com/php-enqueue/enqueue-dev/issues/53)
- Additional drivers [\#32](https://github.com/php-enqueue/enqueue-dev/issues/32)
- \[consumption\] Add support of QueueSubscriberInterface to transport consume command. [\#63](https://github.com/php-enqueue/enqueue-dev/pull/63) ([makasim](https://github.com/makasim))
- \[client\] Add ability to hardcode queue name. It is used as is and not adjusted or modified in any way [\#61](https://github.com/php-enqueue/enqueue-dev/pull/61) ([makasim](https://github.com/makasim))

- Multiple consumer handling one message [\#62](https://github.com/php-enqueue/enqueue-dev/issues/62)

## [0.3.4](https://github.com/php-enqueue/enqueue-dev/tree/0.3.4) (2017-04-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.3...0.3.4)

- DBAL Transport [\#54](https://github.com/php-enqueue/enqueue-dev/pull/54) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.3](https://github.com/php-enqueue/enqueue-dev/tree/0.3.3) (2017-04-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.2...0.3.3)

- \[client\] Redis driver [\#59](https://github.com/php-enqueue/enqueue-dev/pull/59) ([makasim](https://github.com/makasim))
- Redis transport. [\#55](https://github.com/php-enqueue/enqueue-dev/pull/55) ([makasim](https://github.com/makasim))

- Move some dependencies to dev section [\#57](https://github.com/php-enqueue/enqueue-dev/issues/57)

## [0.3.2](https://github.com/php-enqueue/enqueue-dev/tree/0.3.2) (2017-04-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.1...0.3.2)

- share simple client context [\#52](https://github.com/php-enqueue/enqueue-dev/pull/52) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.1](https://github.com/php-enqueue/enqueue-dev/tree/0.3.1) (2017-04-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.0...0.3.1)

- \[client\] Rename MessageProducer to Producer. To be similar what Psr has [\#42](https://github.com/php-enqueue/enqueue-dev/issues/42)

- \[transport\] Add Psr prefix to transport interfaces.  [\#44](https://github.com/php-enqueue/enqueue-dev/issues/44)

- \[client\] Add RpcClient on client level. [\#50](https://github.com/php-enqueue/enqueue-dev/pull/50) ([makasim](https://github.com/makasim))

## [0.3.0](https://github.com/php-enqueue/enqueue-dev/tree/0.3.0) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.12...0.3.0)

- Remove deprecated stuff [\#48](https://github.com/php-enqueue/enqueue-dev/pull/48) ([makasim](https://github.com/makasim))

## [0.2.12](https://github.com/php-enqueue/enqueue-dev/tree/0.2.12) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.11...0.2.12)

- \[consumption\] Need an extension point after the message is processed but before the ack\reject actually is done. [\#43](https://github.com/php-enqueue/enqueue-dev/issues/43)

- \[client\] Rename MessageProducer classes to Producer [\#47](https://github.com/php-enqueue/enqueue-dev/pull/47) ([makasim](https://github.com/makasim))
- \[consumption\] Add onResult extension point. [\#46](https://github.com/php-enqueue/enqueue-dev/pull/46) ([makasim](https://github.com/makasim))
- \[transport\] Add Psr prefix to transport interfaces. Deprecates old ones. [\#45](https://github.com/php-enqueue/enqueue-dev/pull/45) ([makasim](https://github.com/makasim))

## [0.2.11](https://github.com/php-enqueue/enqueue-dev/tree/0.2.11) (2017-04-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.10...0.2.11)

- \[client\] Add ability to define scope of send message. [\#40](https://github.com/php-enqueue/enqueue-dev/pull/40) ([makasim](https://github.com/makasim))

## [0.2.10](https://github.com/php-enqueue/enqueue-dev/tree/0.2.10) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.9...0.2.10)

## [0.2.9](https://github.com/php-enqueue/enqueue-dev/tree/0.2.9) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.8...0.2.9)

- \[bundle\] Fix extensions priority ordering. Must be from high to low. [\#38](https://github.com/php-enqueue/enqueue-dev/pull/38) ([makasim](https://github.com/makasim))

## [0.2.8](https://github.com/php-enqueue/enqueue-dev/tree/0.2.8) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.7...0.2.8)

- Do not print "Idle" when consumer is run with debug level \(-vvv\) [\#35](https://github.com/php-enqueue/enqueue-dev/issues/35)
- \[amqp\] Move RabbitMQ specific logic from AmqpDriver to RabbitMQAmqpDriver. [\#20](https://github.com/php-enqueue/enqueue-dev/issues/20)
- \[filesystem\] Consumer::receive method impr. Add file\_size check to the loop [\#15](https://github.com/php-enqueue/enqueue-dev/issues/15)

- \[client\] DelayRedeliveredMessagesExtension must do nothing if the result\status has been already set [\#36](https://github.com/php-enqueue/enqueue-dev/issues/36)

- Invalid typehint for Enqueue\Client\Message::setBody [\#31](https://github.com/php-enqueue/enqueue-dev/issues/31)

- Improvements and fixes [\#37](https://github.com/php-enqueue/enqueue-dev/pull/37) ([makasim](https://github.com/makasim))
- fix fsdriver router topic name [\#34](https://github.com/php-enqueue/enqueue-dev/pull/34) ([bendavies](https://github.com/bendavies))
- run php-cs-fixer [\#33](https://github.com/php-enqueue/enqueue-dev/pull/33) ([bendavies](https://github.com/bendavies))

## [0.2.7](https://github.com/php-enqueue/enqueue-dev/tree/0.2.7) (2017-03-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.6...0.2.7)

- \[client\] Allow send objects that implements \JsonSerializable interface. [\#30](https://github.com/php-enqueue/enqueue-dev/pull/30) ([makasim](https://github.com/makasim))

## [0.2.6](https://github.com/php-enqueue/enqueue-dev/tree/0.2.6) (2017-03-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.5...0.2.6)

- \[bundle\]\[doc\] desctibe message processor's tag options. [\#23](https://github.com/php-enqueue/enqueue-dev/issues/23)

- Fix Simple Client [\#29](https://github.com/php-enqueue/enqueue-dev/pull/29) ([ASKozienko](https://github.com/ASKozienko))
- Update quick\_tour.md add Bundle to AppKernel [\#26](https://github.com/php-enqueue/enqueue-dev/pull/26) ([jverdeyen](https://github.com/jverdeyen))
- \[doc\] Add docs about message processors. [\#24](https://github.com/php-enqueue/enqueue-dev/pull/24) ([makasim](https://github.com/makasim))
- Fix unclear sentences in docs [\#21](https://github.com/php-enqueue/enqueue-dev/pull/21) ([cirnatdan](https://github.com/cirnatdan))

## [0.2.5](https://github.com/php-enqueue/enqueue-dev/tree/0.2.5) (2017-01-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.4...0.2.5)

- \[amqp\]\[bug\] Consumer received message targeted for another consumer of this same channel [\#13](https://github.com/php-enqueue/enqueue-dev/issues/13)
- \[amqp\] Put in buffer not our message. Continue consumption.  [\#22](https://github.com/php-enqueue/enqueue-dev/pull/22) ([makasim](https://github.com/makasim))

- \[travis\] Test against different Symfony versions, at least 2.8, 3.0, 3.1 [\#17](https://github.com/php-enqueue/enqueue-dev/issues/17)
- \[docker\] Build images for all containers that built from Dockerfiles.  [\#16](https://github.com/php-enqueue/enqueue-dev/issues/16)

- \[travis\] Run test with different Symfony versions. 2.8, 3.0 [\#19](https://github.com/php-enqueue/enqueue-dev/pull/19) ([makasim](https://github.com/makasim))
- \[fs\] Add missing enqueue/psr-queue package to composer.json. [\#18](https://github.com/php-enqueue/enqueue-dev/pull/18) ([makasim](https://github.com/makasim))

## [0.2.4](https://github.com/php-enqueue/enqueue-dev/tree/0.2.4) (2017-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.3...0.2.4)

- Filesystem transport [\#12](https://github.com/php-enqueue/enqueue-dev/pull/12) ([makasim](https://github.com/makasim))

- \[consumption\]\[bug\] Receive timeout is in milliseconds. Set it to 5000.… [\#14](https://github.com/php-enqueue/enqueue-dev/pull/14) ([makasim](https://github.com/makasim))
- \[consumption\] Do not print "Switch to queue xxx" if queue the same. [\#11](https://github.com/php-enqueue/enqueue-dev/pull/11) ([makasim](https://github.com/makasim))

## [0.2.3](https://github.com/php-enqueue/enqueue-dev/tree/0.2.3) (2017-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.2...0.2.3)

- Auto generate changelog  [\#10](https://github.com/php-enqueue/enqueue-dev/pull/10) ([makasim](https://github.com/makasim))
- \[travis\] Cache docker images on travis. [\#9](https://github.com/php-enqueue/enqueue-dev/pull/9) ([makasim](https://github.com/makasim))
- \[enhancement\]\[amqp-ext\] Add purge queue method to amqp context. [\#8](https://github.com/php-enqueue/enqueue-dev/pull/8) ([makasim](https://github.com/makasim))
- \[bug\]\[amqp-ext\] Receive timeout parameter is miliseconds [\#7](https://github.com/php-enqueue/enqueue-dev/pull/7) ([makasim](https://github.com/makasim))

## [0.2.2](https://github.com/php-enqueue/enqueue-dev/tree/0.2.2) (2017-01-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.1...0.2.2)

- Amqp lazy connection  [\#4](https://github.com/php-enqueue/enqueue-dev/issues/4)

- \[amqp\] introduce lazy context. [\#6](https://github.com/php-enqueue/enqueue-dev/pull/6) ([makasim](https://github.com/makasim))

## [0.2.1](https://github.com/php-enqueue/enqueue-dev/tree/0.2.1) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.0...0.2.1)

## [0.2.0](https://github.com/php-enqueue/enqueue-dev/tree/0.2.0) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.1.0...0.2.0)

- Upd php cs fixer [\#3](https://github.com/php-enqueue/enqueue-dev/pull/3) ([makasim](https://github.com/makasim))
- \[psr\] Introduce MessageProcessor interface \(moved from consumption\). [\#2](https://github.com/php-enqueue/enqueue-dev/pull/2) ([makasim](https://github.com/makasim))
- \[bundle\] Add ability to disable signal extension. [\#1](https://github.com/php-enqueue/enqueue-dev/pull/1) ([makasim](https://github.com/makasim))

## [0.1.0](https://github.com/php-enqueue/enqueue-dev/tree/0.1.0) (2016-12-29)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*