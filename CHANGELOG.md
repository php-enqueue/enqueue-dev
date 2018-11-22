# Change Log

## [0.8.41](https://github.com/php-enqueue/enqueue-dev/tree/0.8.41) (2018-11-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.40...0.8.41)

- \[dbal\] consumption improvements. [\#605](https://github.com/php-enqueue/enqueue-dev/issues/605)
- Make new RouterProcessor backward compatible.  [\#598](https://github.com/php-enqueue/enqueue-dev/issues/598)
- profiler and data collector should support multiple clients.  [\#594](https://github.com/php-enqueue/enqueue-dev/issues/594)
- \[bundle\]\[client\] Add ability to configure multiple clients.  [\#592](https://github.com/php-enqueue/enqueue-dev/issues/592)
- \[gearman\]\[travis\] Build and cache gearman extension.  [\#511](https://github.com/php-enqueue/enqueue-dev/issues/511)
- \[consumption\] Do not overwrite signal handlers set before SignalExtension [\#318](https://github.com/php-enqueue/enqueue-dev/issues/318)
- \[Symfony\] add support to transfer tokenStorage \(user info\) to the worker [\#69](https://github.com/php-enqueue/enqueue-dev/issues/69)

- Fix AMQP tests  [\#614](https://github.com/php-enqueue/enqueue-dev/issues/614)
- Enqueue/FS does not use latest Parse DSN class [\#610](https://github.com/php-enqueue/enqueue-dev/issues/610)
- Async commands queue setup error [\#608](https://github.com/php-enqueue/enqueue-dev/issues/608)
- \[FS\] Maximum function nesting level of '256' reached [\#327](https://github.com/php-enqueue/enqueue-dev/issues/327)

- \[dbal\] consumer should still work if table is truncated [\#638](https://github.com/php-enqueue/enqueue-dev/issues/638)
- \[redis\] LogicException on set priority [\#635](https://github.com/php-enqueue/enqueue-dev/issues/635)
- Elastica populate with AWS SQS [\#629](https://github.com/php-enqueue/enqueue-dev/issues/629)
- SQS and fallback subscription consumer [\#625](https://github.com/php-enqueue/enqueue-dev/issues/625)
- \[dsn\] Add multi hosts parsing.  [\#624](https://github.com/php-enqueue/enqueue-dev/issues/624)
- \[Symfony\] Try to check private service existence from container [\#621](https://github.com/php-enqueue/enqueue-dev/issues/621)
- Configuration with Amazon SQS and Symfony [\#619](https://github.com/php-enqueue/enqueue-dev/issues/619)
- \[redis\] Do not force phpredis [\#551](https://github.com/php-enqueue/enqueue-dev/issues/551)

- Fixed headline [\#631](https://github.com/php-enqueue/enqueue-dev/pull/631) ([OskarStark](https://github.com/OskarStark))
- Compatibility with 0.9x [\#615](https://github.com/php-enqueue/enqueue-dev/pull/615) ([ASKozienko](https://github.com/ASKozienko))
- Fix Tests 0.8x [\#609](https://github.com/php-enqueue/enqueue-dev/pull/609) ([ASKozienko](https://github.com/ASKozienko))
- Add support for the 'ciphers' ssl option [\#607](https://github.com/php-enqueue/enqueue-dev/pull/607) ([eperazzo](https://github.com/eperazzo))
- Allow JobStorage to reset the EntityManager [\#586](https://github.com/php-enqueue/enqueue-dev/pull/586) ([damijank](https://github.com/damijank))
- Fix delay not working on SQS [\#584](https://github.com/php-enqueue/enqueue-dev/pull/584) ([mbeccati](https://github.com/mbeccati))

## [0.8.40](https://github.com/php-enqueue/enqueue-dev/tree/0.8.40) (2018-10-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.39...0.8.40)

- \[redis\] support for delay [\#553](https://github.com/php-enqueue/enqueue-dev/issues/553)

- \[rdkafka\] Backport changes to topic subscription [\#575](https://github.com/php-enqueue/enqueue-dev/pull/575) ([Steveb-p](https://github.com/Steveb-p))

## [0.8.39](https://github.com/php-enqueue/enqueue-dev/tree/0.8.39) (2018-10-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.38...0.8.39)

- Consuming with Simple Client and Kafka [\#557](https://github.com/php-enqueue/enqueue-dev/issues/557)

- Merge pull request \#552 from versh23/stomp-public [\#568](https://github.com/php-enqueue/enqueue-dev/pull/568) ([versh23](https://github.com/versh23))

## [0.8.38](https://github.com/php-enqueue/enqueue-dev/tree/0.8.38) (2018-10-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.37...0.8.38)

- Support rabbitmq-cli-consumer [\#546](https://github.com/php-enqueue/enqueue-dev/issues/546)
- Add ability to choose transport\context to be used in consume command  [\#312](https://github.com/php-enqueue/enqueue-dev/issues/312)

- \[Symfony\] sendCommand / sendEvent for delayed message have different behaviour [\#523](https://github.com/php-enqueue/enqueue-dev/issues/523)
- \[bundle\] The bundle  does not work correctly with env parameters set as tag attr. [\#28](https://github.com/php-enqueue/enqueue-dev/issues/28)

- Stomp heartbeat [\#549](https://github.com/php-enqueue/enqueue-dev/issues/549)
- \[Elastica\]Slow processing [\#537](https://github.com/php-enqueue/enqueue-dev/issues/537)
- \[consumption\] Some improvements [\#323](https://github.com/php-enqueue/enqueue-dev/issues/323)

- Fixing kafka default configuration [\#562](https://github.com/php-enqueue/enqueue-dev/pull/562) ([adumas37](https://github.com/adumas37))
- enableSubscriptionConsumer setter [\#541](https://github.com/php-enqueue/enqueue-dev/pull/541) ([ArnaudTarroux](https://github.com/ArnaudTarroux))

## [0.8.37](https://github.com/php-enqueue/enqueue-dev/tree/0.8.37) (2018-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.36...0.8.37)

- Message body serialization other than JSON [\#316](https://github.com/php-enqueue/enqueue-dev/issues/316)

- \[Symfony\]\[Flex\]\[enqueue/fs\] Invalid ENQUEUE\_DSN value after recipe execution [\#520](https://github.com/php-enqueue/enqueue-dev/issues/520)

- Command not processed on first registration [\#529](https://github.com/php-enqueue/enqueue-dev/issues/529)
- \[Redis\] default timeout setting makes connection impossible [\#525](https://github.com/php-enqueue/enqueue-dev/issues/525)
- use multiple queue [\#514](https://github.com/php-enqueue/enqueue-dev/issues/514)
- Populating and interrupting [\#469](https://github.com/php-enqueue/enqueue-dev/issues/469)

## [0.8.36](https://github.com/php-enqueue/enqueue-dev/tree/0.8.36) (2018-08-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.35...0.8.36)

- DefaultTransportFactory should resolve DSN at runtime, if given as ENV.  [\#394](https://github.com/php-enqueue/enqueue-dev/issues/394)

- Dbal Performance degrades when more than 100k rows [\#465](https://github.com/php-enqueue/enqueue-dev/issues/465)
- \[dbal\] message delay does not work properly  [\#418](https://github.com/php-enqueue/enqueue-dev/issues/418)

- \[amqp-lib\] The connection timed out [\#487](https://github.com/php-enqueue/enqueue-dev/issues/487)
- \[dbal\] unable to requeue with delay [\#474](https://github.com/php-enqueue/enqueue-dev/issues/474)
- Purge / Purgable [\#466](https://github.com/php-enqueue/enqueue-dev/issues/466)
- Kafka consumer subscribe/assign problems [\#454](https://github.com/php-enqueue/enqueue-dev/issues/454)
- Exchange messages between applications [\#448](https://github.com/php-enqueue/enqueue-dev/issues/448)
- object not found error [\#420](https://github.com/php-enqueue/enqueue-dev/issues/420)
- \[symfony bundle\] The env "resolve:ENQUEUE\_DSN" var is not defined [\#375](https://github.com/php-enqueue/enqueue-dev/issues/375)

- Remove bool typehint for php \< 7 supports [\#513](https://github.com/php-enqueue/enqueue-dev/pull/513) ([ArnaudTarroux](https://github.com/ArnaudTarroux))

## [0.8.35](https://github.com/php-enqueue/enqueue-dev/tree/0.8.35) (2018-08-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.34...0.8.35)

## [0.8.34](https://github.com/php-enqueue/enqueue-dev/tree/0.8.34) (2018-08-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.33...0.8.34)

- \[sqs\] Messages should not allow empty bodies [\#435](https://github.com/php-enqueue/enqueue-dev/issues/435)
- Use the auto-tagging feature for e.g. processors [\#405](https://github.com/php-enqueue/enqueue-dev/issues/405)

- \[simple-client\] `sqs:` DSN not working [\#483](https://github.com/php-enqueue/enqueue-dev/issues/483)

- Adding a signal handler to the consumer [\#485](https://github.com/php-enqueue/enqueue-dev/issues/485)
- Problem with SQS DSN string with + in secret [\#481](https://github.com/php-enqueue/enqueue-dev/issues/481)
- Monitoring interface [\#476](https://github.com/php-enqueue/enqueue-dev/issues/476)

## [0.8.33](https://github.com/php-enqueue/enqueue-dev/tree/0.8.33) (2018-07-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.32...0.8.33)

- \[consumption\] process niceness extension [\#449](https://github.com/php-enqueue/enqueue-dev/issues/449)
- \[Symfony\] AsyncListener does not use TraceableProducer [\#392](https://github.com/php-enqueue/enqueue-dev/issues/392)

- Support MQTT [\#477](https://github.com/php-enqueue/enqueue-dev/issues/477)
- Bugs in RabbitMqDelayPluginDelayStrategy [\#455](https://github.com/php-enqueue/enqueue-dev/issues/455)
- \[sqs\] Support using a pre-configured SqsClient [\#443](https://github.com/php-enqueue/enqueue-dev/issues/443)
- IronMQ \(iron.io\) provider ? [\#415](https://github.com/php-enqueue/enqueue-dev/issues/415)

## [0.8.32](https://github.com/php-enqueue/enqueue-dev/tree/0.8.32) (2018-07-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.31...0.8.32)

- \[Bundle\] auto-tag services [\#409](https://github.com/php-enqueue/enqueue-dev/issues/409)

- Add documentation the processor services need to be public [\#406](https://github.com/php-enqueue/enqueue-dev/issues/406)

- Is it possible to read messages in batch? [\#472](https://github.com/php-enqueue/enqueue-dev/issues/472)
- Batch publishing [\#463](https://github.com/php-enqueue/enqueue-dev/issues/463)
- populating, missing messages and supervisor [\#460](https://github.com/php-enqueue/enqueue-dev/issues/460)
- Processor was not found. processorName: "enqueue.client.router\_processor" [\#451](https://github.com/php-enqueue/enqueue-dev/issues/451)
- \[Bundle\] Enqueue\Symfony\Client\ContainerAwareProcessorRegistry expects processors to be public [\#410](https://github.com/php-enqueue/enqueue-dev/issues/410)

## [0.8.31](https://github.com/php-enqueue/enqueue-dev/tree/0.8.31) (2018-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.30...0.8.31)

- Gracefull shutdown? [\#440](https://github.com/php-enqueue/enqueue-dev/issues/440)

## [0.8.30](https://github.com/php-enqueue/enqueue-dev/tree/0.8.30) (2018-05-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.29...0.8.30)

## [0.8.29](https://github.com/php-enqueue/enqueue-dev/tree/0.8.29) (2018-05-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.28...0.8.29)

## [0.8.28](https://github.com/php-enqueue/enqueue-dev/tree/0.8.28) (2018-05-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.27...0.8.28)

- Should `enqueue/enqueue` also be added to "require" in composer.json for DBAL package? [\#433](https://github.com/php-enqueue/enqueue-dev/issues/433)

- RouterProcessor "acknowledges" commands and events without a registered processor [\#423](https://github.com/php-enqueue/enqueue-dev/issues/423)
- \[Symfony\]\[Documentation\] Migrate from JMSJobQueueBundle [\#421](https://github.com/php-enqueue/enqueue-dev/issues/421)

## [0.8.27](https://github.com/php-enqueue/enqueue-dev/tree/0.8.27) (2018-05-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.26...0.8.27)

- How can I use the Symfony Bundle with Kafka? [\#428](https://github.com/php-enqueue/enqueue-dev/issues/428)

## [0.8.26](https://github.com/php-enqueue/enqueue-dev/tree/0.8.26) (2018-04-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.25...0.8.26)

## [0.8.25](https://github.com/php-enqueue/enqueue-dev/tree/0.8.25) (2018-04-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.24...0.8.25)

- gearmand queue library can't not use for php7 [\#270](https://github.com/php-enqueue/enqueue-dev/issues/270)

- Why no packagist support [\#424](https://github.com/php-enqueue/enqueue-dev/issues/424)
- \[DbalDriver\] does not convert Message::$expire to DbalMessage::$timeToLive [\#391](https://github.com/php-enqueue/enqueue-dev/issues/391)

## [0.8.24](https://github.com/php-enqueue/enqueue-dev/tree/0.8.24) (2018-03-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.23...0.8.24)

- \[fs\] Escape special symbols  [\#390](https://github.com/php-enqueue/enqueue-dev/issues/390)

- Laravel Usage [\#408](https://github.com/php-enqueue/enqueue-dev/issues/408)
- \[JobRunner\] Uncaught exceptions leave jobs in "running" state [\#385](https://github.com/php-enqueue/enqueue-dev/issues/385)
- \[Feature Request\] Closure message body. [\#366](https://github.com/php-enqueue/enqueue-dev/issues/366)

## [0.8.23](https://github.com/php-enqueue/enqueue-dev/tree/0.8.23) (2018-03-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.22...0.8.23)

## [0.8.22](https://github.com/php-enqueue/enqueue-dev/tree/0.8.22) (2018-03-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.21...0.8.22)

- Runtime exception "\_\_construct\(\)" references interface "Enqueue\Client\ProducerInterface" but no such service exists [\#376](https://github.com/php-enqueue/enqueue-dev/issues/376)

- \[Simple Client\] The simple client requires amqp-ext even if you use another brokers [\#386](https://github.com/php-enqueue/enqueue-dev/issues/386)
- \[symfony bundle\] EnqueueExtension. Transport factory with such name already added. Name stomp [\#383](https://github.com/php-enqueue/enqueue-dev/issues/383)
- Problem registering SQS transport using DSN string [\#380](https://github.com/php-enqueue/enqueue-dev/issues/380)

- Close Connection [\#384](https://github.com/php-enqueue/enqueue-dev/issues/384)
- Outdated bundle documentation [\#381](https://github.com/php-enqueue/enqueue-dev/issues/381)
- \[RFC\] Throttle/debounce [\#378](https://github.com/php-enqueue/enqueue-dev/issues/378)

## [0.8.21](https://github.com/php-enqueue/enqueue-dev/tree/0.8.21) (2018-02-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.20...0.8.21)

- Delayed-message doesn't work on my project ! [\#373](https://github.com/php-enqueue/enqueue-dev/issues/373)
- \[Symfony\] Command name misses in profiler [\#355](https://github.com/php-enqueue/enqueue-dev/issues/355)

## [0.8.20](https://github.com/php-enqueue/enqueue-dev/tree/0.8.20) (2018-02-15)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.19...0.8.20)

- Pass options to predis client when using redis transport [\#367](https://github.com/php-enqueue/enqueue-dev/issues/367)
- Authentication Support for Redis [\#349](https://github.com/php-enqueue/enqueue-dev/issues/349)
- Does redis factory supports sentinel or cluster? [\#341](https://github.com/php-enqueue/enqueue-dev/issues/341)

## [0.8.19](https://github.com/php-enqueue/enqueue-dev/tree/0.8.19) (2018-02-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.18...0.8.19)

- \[Docs\] Describe difference between command and event messages [\#351](https://github.com/php-enqueue/enqueue-dev/issues/351)

- Minor grammatical changes to documentation [\#363](https://github.com/php-enqueue/enqueue-dev/issues/363)
- \[DbalConsumer\] Issue with id type [\#360](https://github.com/php-enqueue/enqueue-dev/issues/360)

## [0.8.18](https://github.com/php-enqueue/enqueue-dev/tree/0.8.18) (2018-02-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.17...0.8.18)

- \[SQS\] Allow custom aws endpoint configuration [\#352](https://github.com/php-enqueue/enqueue-dev/issues/352)

- Transport is not enabled: amqp: [\#356](https://github.com/php-enqueue/enqueue-dev/issues/356)

- \[SQS\] Unable to connect to FIFO queue [\#342](https://github.com/php-enqueue/enqueue-dev/issues/342)
- \[dbal\] Consumer never fetches messages ordered by published time [\#340](https://github.com/php-enqueue/enqueue-dev/issues/340)

## [0.8.17](https://github.com/php-enqueue/enqueue-dev/tree/0.8.17) (2018-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.16...0.8.17)

- QueueConsumer should be final  [\#311](https://github.com/php-enqueue/enqueue-dev/issues/311)

- Unrecognized option "amqp" under "enqueue.transport" [\#333](https://github.com/php-enqueue/enqueue-dev/issues/333)

## [0.8.16](https://github.com/php-enqueue/enqueue-dev/tree/0.8.16) (2018-01-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.15...0.8.16)

## [0.8.15](https://github.com/php-enqueue/enqueue-dev/tree/0.8.15) (2018-01-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.14...0.8.15)

- SQS via DNS region missing [\#321](https://github.com/php-enqueue/enqueue-dev/issues/321)

## [0.8.14](https://github.com/php-enqueue/enqueue-dev/tree/0.8.14) (2018-01-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.13...0.8.14)

## [0.8.13](https://github.com/php-enqueue/enqueue-dev/tree/0.8.13) (2018-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.12...0.8.13)

- AMQPIOWaitException upon docker container shutdown [\#300](https://github.com/php-enqueue/enqueue-dev/issues/300)

## [0.8.12](https://github.com/php-enqueue/enqueue-dev/tree/0.8.12) (2018-01-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.11...0.8.12)

- \[Elastica\] convert the Doctrine listeners to async too [\#244](https://github.com/php-enqueue/enqueue-dev/issues/244)

- \[amqp-ext\] Unrecognized options "login, password, delay\_plugin\_installed" under "enqueue.transport.rabbitmq\_amqp [\#309](https://github.com/php-enqueue/enqueue-dev/issues/309)
- Symfony Bundle: amqp bunny doesn't stop the execution of the CLI command [\#303](https://github.com/php-enqueue/enqueue-dev/issues/303)

## [0.8.11](https://github.com/php-enqueue/enqueue-dev/tree/0.8.11) (2017-12-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.10...0.8.11)

- \[job-queue\] Change type hint from Closure to callable [\#286](https://github.com/php-enqueue/enqueue-dev/issues/286)

- Consumer Requeue -\> DBAL NotNullConstraintViolationException [\#290](https://github.com/php-enqueue/enqueue-dev/issues/290)
- Set custom logger [\#287](https://github.com/php-enqueue/enqueue-dev/issues/287)
- \[Elastica\] persistence.driver = orm is optional [\#245](https://github.com/php-enqueue/enqueue-dev/issues/245)

- \[composer\] Add support details to composer.json [\#288](https://github.com/php-enqueue/enqueue-dev/issues/288)

## [0.8.10](https://github.com/php-enqueue/enqueue-dev/tree/0.8.10) (2017-12-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.9...0.8.10)

- \[dbal\] Store id \(uuid\) as binary data. [\#279](https://github.com/php-enqueue/enqueue-dev/issues/279)
- \[doc\] Add a doc with job queue doctrine migration example [\#278](https://github.com/php-enqueue/enqueue-dev/issues/278)
- Add mongodb support. [\#251](https://github.com/php-enqueue/enqueue-dev/issues/251)
- Add zeromq support [\#208](https://github.com/php-enqueue/enqueue-dev/issues/208)
- \[amqp-lib\] It should be possible to create queue without a name,  [\#145](https://github.com/php-enqueue/enqueue-dev/issues/145)
- \[doc\] Add the doc for client extensions [\#73](https://github.com/php-enqueue/enqueue-dev/issues/73)

- \[enqueue/dbal\] Logic for "The platform does not support UUIDs natively" is incorrect [\#276](https://github.com/php-enqueue/enqueue-dev/issues/276)

## [0.8.9](https://github.com/php-enqueue/enqueue-dev/tree/0.8.9) (2017-11-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.8...0.8.9)

- \[rdkafka\] Introduce KeySerializer  [\#255](https://github.com/php-enqueue/enqueue-dev/issues/255)
- \[amqp-lib\]\[RabbitMQ\] Publisher Confirms [\#206](https://github.com/php-enqueue/enqueue-dev/issues/206)
- \[client\]\[amqp\] Idea. Add support of dead queues [\#39](https://github.com/php-enqueue/enqueue-dev/issues/39)

- \[amqp-ext\] Problem with consume messages [\#274](https://github.com/php-enqueue/enqueue-dev/issues/274)

## [0.8.8](https://github.com/php-enqueue/enqueue-dev/tree/0.8.8) (2017-11-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.7...0.8.8)

- onIdle is not triggered [\#260](https://github.com/php-enqueue/enqueue-dev/issues/260)
- On exception Context is not set  [\#259](https://github.com/php-enqueue/enqueue-dev/issues/259)

## [0.8.7](https://github.com/php-enqueue/enqueue-dev/tree/0.8.7) (2017-11-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.6...0.8.7)

- SetRouterPropertiesExtension does not work with SQS [\#261](https://github.com/php-enqueue/enqueue-dev/issues/261)

## [0.8.6](https://github.com/php-enqueue/enqueue-dev/tree/0.8.6) (2017-11-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.5...0.8.6)

- \[Elastica Bundle\] tag 0.8 [\#253](https://github.com/php-enqueue/enqueue-dev/issues/253)

## [0.8.5](https://github.com/php-enqueue/enqueue-dev/tree/0.8.5) (2017-11-02)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.4...0.8.5)

## [0.8.4](https://github.com/php-enqueue/enqueue-dev/tree/0.8.4) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.3...0.8.4)

## [0.8.3](https://github.com/php-enqueue/enqueue-dev/tree/0.8.3) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.2...0.8.3)

- \[Symfony\]\[Minor\] profiler view when no messages collected during the request [\#243](https://github.com/php-enqueue/enqueue-dev/issues/243)

## [0.8.2](https://github.com/php-enqueue/enqueue-dev/tree/0.8.2) (2017-10-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.1...0.8.2)

- \[amqp\] add ssl support  [\#147](https://github.com/php-enqueue/enqueue-dev/issues/147)

## [0.8.1](https://github.com/php-enqueue/enqueue-dev/tree/0.8.1) (2017-10-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.0...0.8.1)

- Allow kafka tests to fail.  [\#232](https://github.com/php-enqueue/enqueue-dev/issues/232)

- GPSTransportFactory registration is missing from EnqueueBundle [\#235](https://github.com/php-enqueue/enqueue-dev/issues/235)

## [0.8.0](https://github.com/php-enqueue/enqueue-dev/tree/0.8.0) (2017-10-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.19...0.8.0)

- \[amqp-lib\] The context should allow to get the lib's channel.  [\#146](https://github.com/php-enqueue/enqueue-dev/issues/146)
- \[amqp\] One single transport factory for all supported amqp implementa… [\#233](https://github.com/php-enqueue/enqueue-dev/pull/233) ([makasim](https://github.com/makasim))
- \[BC break\]\[amqp\] Introduce connection config. Make it same across all transports. [\#228](https://github.com/php-enqueue/enqueue-dev/pull/228) ([makasim](https://github.com/makasim))

- \[amqp-bunny\] High CPU usage while using basic.consume.  [\#226](https://github.com/php-enqueue/enqueue-dev/issues/226)
- Amqp basic consume should restore default timeout inside consume callback.  [\#225](https://github.com/php-enqueue/enqueue-dev/issues/225)
- AmqpProducer::send method must throw only interop exception. [\#224](https://github.com/php-enqueue/enqueue-dev/issues/224)
- \[consumption\]\[amqp\] move beforeReceive call at the end of the cycle f… [\#234](https://github.com/php-enqueue/enqueue-dev/pull/234) ([makasim](https://github.com/makasim))
- \\[BC break\\]\\[amqp\\] Introduce connection config. Make it same across all transports. [\#228](https://github.com/php-enqueue/enqueue-dev/pull/228) ([makasim](https://github.com/makasim))

## [0.7.19](https://github.com/php-enqueue/enqueue-dev/tree/0.7.19) (2017-10-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.18...0.7.19)

- Amqp basic consume fixes [\#223](https://github.com/php-enqueue/enqueue-dev/pull/223) ([makasim](https://github.com/makasim))
- \[BC break\]\[amqp\] Use same qos options across all all AMQP transports [\#221](https://github.com/php-enqueue/enqueue-dev/pull/221) ([makasim](https://github.com/makasim))
- \[BC break\] Amqp add basic consume support [\#217](https://github.com/php-enqueue/enqueue-dev/pull/217) ([makasim](https://github.com/makasim))

- Amqp basic consume fixes [\#223](https://github.com/php-enqueue/enqueue-dev/pull/223) ([makasim](https://github.com/makasim))

## [0.7.18](https://github.com/php-enqueue/enqueue-dev/tree/0.7.18) (2017-10-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.17...0.7.18)

- \[consumption\]\[client\] Add --skip option to consume commands. [\#216](https://github.com/php-enqueue/enqueue-dev/issues/216)
- \[json\] jsonSerialize could throw an a exception.  [\#132](https://github.com/php-enqueue/enqueue-dev/issues/132)

## [0.7.17](https://github.com/php-enqueue/enqueue-dev/tree/0.7.17) (2017-10-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.16...0.7.17)

- \[Symfony\] Error using profiler with symfony 2.8 [\#211](https://github.com/php-enqueue/enqueue-dev/issues/211)
- \[fs\] ErrorException: The Symfony\Component\Filesystem\LockHandler class is deprecated since version 3.4 [\#166](https://github.com/php-enqueue/enqueue-dev/issues/166)

## [0.7.16](https://github.com/php-enqueue/enqueue-dev/tree/0.7.16) (2017-09-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.15...0.7.16)

- \[BC Break\]\[dsn\] replace xxx:// to xxx: [\#205](https://github.com/php-enqueue/enqueue-dev/pull/205) ([makasim](https://github.com/makasim))

## [0.7.15](https://github.com/php-enqueue/enqueue-dev/tree/0.7.15) (2017-09-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.14...0.7.15)

- \[FS\]\[RFC\] Change to FIFO queue [\#171](https://github.com/php-enqueue/enqueue-dev/issues/171)
- Transports must support configuration via DSN string [\#87](https://github.com/php-enqueue/enqueue-dev/issues/87)
- Add support of async message processing to transport interfaces. Like Java JMS. [\#27](https://github.com/php-enqueue/enqueue-dev/issues/27)
- \[dbal\]\[bc break\] Performance improvements and new features. [\#199](https://github.com/php-enqueue/enqueue-dev/pull/199) ([makasim](https://github.com/makasim))

- \[FS\] Cannot decode json message [\#202](https://github.com/php-enqueue/enqueue-dev/issues/202)
- \[fs\] fix bugs introduced in \#181. [\#203](https://github.com/php-enqueue/enqueue-dev/pull/203) ([makasim](https://github.com/makasim))

- \[FS\] Cannot decode json message [\#201](https://github.com/php-enqueue/enqueue-dev/issues/201)
- \[FS\] Cannot decode json message [\#200](https://github.com/php-enqueue/enqueue-dev/issues/200)

## [0.7.14](https://github.com/php-enqueue/enqueue-dev/tree/0.7.14) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.13...0.7.14)

## [0.7.13](https://github.com/php-enqueue/enqueue-dev/tree/0.7.13) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.12...0.7.13)

- Topic subscriber doesn't work with 2 separate apps [\#196](https://github.com/php-enqueue/enqueue-dev/issues/196)

## [0.7.12](https://github.com/php-enqueue/enqueue-dev/tree/0.7.12) (2017-09-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.11...0.7.12)

## [0.7.11](https://github.com/php-enqueue/enqueue-dev/tree/0.7.11) (2017-09-11)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.10...0.7.11)

- Redis consumer has very high resource usage [\#191](https://github.com/php-enqueue/enqueue-dev/issues/191)

## [0.7.10](https://github.com/php-enqueue/enqueue-dev/tree/0.7.10) (2017-08-31)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.9...0.7.10)

- Serialization beyond JSON [\#187](https://github.com/php-enqueue/enqueue-dev/issues/187)

- Bug on AsyncDoctrineOrmProvider::setContext\(\) [\#186](https://github.com/php-enqueue/enqueue-dev/issues/186)

## [0.7.9](https://github.com/php-enqueue/enqueue-dev/tree/0.7.9) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.8...0.7.9)

- Update to phpstan 0.8 [\#141](https://github.com/php-enqueue/enqueue-dev/issues/141)
- \[client\] Add a reason while setting reject in DelayRedeliveredMessageExtension [\#41](https://github.com/php-enqueue/enqueue-dev/issues/41)

- \[Doctrine\] add support to convert Doctrine events to Enqueue messages [\#68](https://github.com/php-enqueue/enqueue-dev/issues/68)

## [0.7.8](https://github.com/php-enqueue/enqueue-dev/tree/0.7.8) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.7...0.7.8)

- fix sqs tests when run by not a member of the project. [\#179](https://github.com/php-enqueue/enqueue-dev/issues/179)
- \[bundle\] It is not possible to use client's producer in a cli event, for example on exception [\#177](https://github.com/php-enqueue/enqueue-dev/issues/177)
- Error on PurgeFosElasticPopulateQueueListener::\_\_construct\(\) [\#174](https://github.com/php-enqueue/enqueue-dev/issues/174)
- \[bundle\] Possible issue when something configured wronly [\#172](https://github.com/php-enqueue/enqueue-dev/issues/172)
- \[FS\] Frame not being read correctly [\#170](https://github.com/php-enqueue/enqueue-dev/issues/170)

## [0.7.7](https://github.com/php-enqueue/enqueue-dev/tree/0.7.7) (2017-08-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.6...0.7.7)

- Add support for Google Cloud Pub/Sub [\#83](https://github.com/php-enqueue/enqueue-dev/issues/83)

## [0.7.6](https://github.com/php-enqueue/enqueue-dev/tree/0.7.6) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.5...0.7.6)

## [0.7.5](https://github.com/php-enqueue/enqueue-dev/tree/0.7.5) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.4...0.7.5)

## [0.7.4](https://github.com/php-enqueue/enqueue-dev/tree/0.7.4) (2017-08-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.3...0.7.4)

## [0.7.3](https://github.com/php-enqueue/enqueue-dev/tree/0.7.3) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.2...0.7.3)

## [0.7.2](https://github.com/php-enqueue/enqueue-dev/tree/0.7.2) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.1...0.7.2)

- AmqpConsumer::receiveBasicGet, only one message per timeout consumed [\#159](https://github.com/php-enqueue/enqueue-dev/issues/159)
- Symfony 2.8 compatability issue [\#158](https://github.com/php-enqueue/enqueue-dev/issues/158)

## [0.7.1](https://github.com/php-enqueue/enqueue-dev/tree/0.7.1) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.0...0.7.1)

- Symfony bundle doesn't work when sending commands [\#160](https://github.com/php-enqueue/enqueue-dev/issues/160)
- \[amqp-ext\] Server connection error [\#157](https://github.com/php-enqueue/enqueue-dev/issues/157)

## [0.7.0](https://github.com/php-enqueue/enqueue-dev/tree/0.7.0) (2017-08-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.2...0.7.0)

- \[amqp\] Move client related code to Enqueue\Client\Amqp namespace. [\#143](https://github.com/php-enqueue/enqueue-dev/issues/143)
- \[amqp\] What should we do if consumer has already subscribed but smn is trying to change consumer tag? [\#142](https://github.com/php-enqueue/enqueue-dev/issues/142)
- Find a way to retry flaky tests  [\#140](https://github.com/php-enqueue/enqueue-dev/issues/140)
- \[client\] use default topic as  router topic. [\#135](https://github.com/php-enqueue/enqueue-dev/issues/135)

## [0.6.2](https://github.com/php-enqueue/enqueue-dev/tree/0.6.2) (2017-07-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.1...0.6.2)

## [0.6.1](https://github.com/php-enqueue/enqueue-dev/tree/0.6.1) (2017-07-17)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.0...0.6.1)

## [0.6.0](https://github.com/php-enqueue/enqueue-dev/tree/0.6.0) (2017-07-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.3...0.6.0)

## [0.5.3](https://github.com/php-enqueue/enqueue-dev/tree/0.5.3) (2017-07-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.2...0.5.3)

- \[Symfony\] Symfony 3.3 / 4.x compatibility for ProxyEventDispatcher [\#109](https://github.com/php-enqueue/enqueue-dev/issues/109)

## [0.5.2](https://github.com/php-enqueue/enqueue-dev/tree/0.5.2) (2017-07-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.1...0.5.2)

## [0.5.1](https://github.com/php-enqueue/enqueue-dev/tree/0.5.1) (2017-06-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.0...0.5.1)

- \[doc\] add a doc for client message scopes  [\#56](https://github.com/php-enqueue/enqueue-dev/issues/56)

- \[client\] Command, Event segregation. [\#105](https://github.com/php-enqueue/enqueue-dev/issues/105)

## [0.5.0](https://github.com/php-enqueue/enqueue-dev/tree/0.5.0) (2017-06-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.20...0.5.0)

- DBAL Transport: polling\_interval not taken into account [\#121](https://github.com/php-enqueue/enqueue-dev/issues/121)

## [0.4.20](https://github.com/php-enqueue/enqueue-dev/tree/0.4.20) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.19...0.4.20)

## [0.4.19](https://github.com/php-enqueue/enqueue-dev/tree/0.4.19) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.18...0.4.19)

## [0.4.18](https://github.com/php-enqueue/enqueue-dev/tree/0.4.18) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.17...0.4.18)

## [0.4.17](https://github.com/php-enqueue/enqueue-dev/tree/0.4.17) (2017-06-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.16...0.4.17)

- \[RabbitMQ\] High resource usage in AmqpConsumer::receiveBasicGet\(\) [\#116](https://github.com/php-enqueue/enqueue-dev/issues/116)

## [0.4.16](https://github.com/php-enqueue/enqueue-dev/tree/0.4.16) (2017-06-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.15...0.4.16)

## [0.4.15](https://github.com/php-enqueue/enqueue-dev/tree/0.4.15) (2017-06-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.14...0.4.15)

- Symfony async events. Support event subscribers. [\#94](https://github.com/php-enqueue/enqueue-dev/issues/94)

## [0.4.14](https://github.com/php-enqueue/enqueue-dev/tree/0.4.14) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.13...0.4.14)

## [0.4.13](https://github.com/php-enqueue/enqueue-dev/tree/0.4.13) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.12...0.4.13)

- \[amqp\] Consumer always gets the queue the consume callback was called on. [\#110](https://github.com/php-enqueue/enqueue-dev/issues/110)

## [0.4.12](https://github.com/php-enqueue/enqueue-dev/tree/0.4.12) (2017-06-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.11...0.4.12)

## [0.4.11](https://github.com/php-enqueue/enqueue-dev/tree/0.4.11) (2017-05-30)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.10...0.4.11)

- \[amqp\] Get message count [\#64](https://github.com/php-enqueue/enqueue-dev/issues/64)

## [0.4.10](https://github.com/php-enqueue/enqueue-dev/tree/0.4.10) (2017-05-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.9...0.4.10)

- \[RabbitMQ\] support for wildcard topics \("topic exchange"\) [\#65](https://github.com/php-enqueue/enqueue-dev/issues/65)

## [0.4.9](https://github.com/php-enqueue/enqueue-dev/tree/0.4.9) (2017-05-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.8...0.4.9)

- \[client\]\[dx\] Message constructor must accept body, properties and headers.` [\#88](https://github.com/php-enqueue/enqueue-dev/issues/88)

- filesystem dsn must have one more /  [\#99](https://github.com/php-enqueue/enqueue-dev/issues/99)

- Code duplication inside messages [\#96](https://github.com/php-enqueue/enqueue-dev/issues/96)

## [0.4.8](https://github.com/php-enqueue/enqueue-dev/tree/0.4.8) (2017-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.6...0.4.8)

## [0.4.6](https://github.com/php-enqueue/enqueue-dev/tree/0.4.6) (2017-05-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.5...0.4.6)

## [0.4.5](https://github.com/php-enqueue/enqueue-dev/tree/0.4.5) (2017-05-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.4...0.4.5)

## [0.4.4](https://github.com/php-enqueue/enqueue-dev/tree/0.4.4) (2017-05-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.3...0.4.4)

## [0.4.3](https://github.com/php-enqueue/enqueue-dev/tree/0.4.3) (2017-05-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.2...0.4.3)

- \[Performance, DX\] Add a message pool [\#91](https://github.com/php-enqueue/enqueue-dev/issues/91)
- \[bundle\] Show only part of the message body. Add a button show the whole message body. [\#90](https://github.com/php-enqueue/enqueue-dev/issues/90)

## [0.4.2](https://github.com/php-enqueue/enqueue-dev/tree/0.4.2) (2017-05-15)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.1...0.4.2)

## [0.4.1](https://github.com/php-enqueue/enqueue-dev/tree/0.4.1) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.0...0.4.1)

## [0.4.0](https://github.com/php-enqueue/enqueue-dev/tree/0.4.0) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.8...0.4.0)

- \[Extensions\] extensions priority [\#79](https://github.com/php-enqueue/enqueue-dev/issues/79)

## [0.3.8](https://github.com/php-enqueue/enqueue-dev/tree/0.3.8) (2017-05-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.7...0.3.8)

- Add support for production extensions [\#70](https://github.com/php-enqueue/enqueue-dev/issues/70)

## [0.3.7](https://github.com/php-enqueue/enqueue-dev/tree/0.3.7) (2017-05-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.6...0.3.7)

- \[rpc\] RpcClient must check existence of createTemporaryQueue. It is not part of transport interface [\#49](https://github.com/php-enqueue/enqueue-dev/issues/49)

- JobQueue/Job shouldn't be required when Doctrine schema update [\#67](https://github.com/php-enqueue/enqueue-dev/issues/67)

## [0.3.6](https://github.com/php-enqueue/enqueue-dev/tree/0.3.6) (2017-04-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.5...0.3.6)

## [0.3.5](https://github.com/php-enqueue/enqueue-dev/tree/0.3.5) (2017-04-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.4...0.3.5)

- \[client\] Queue subscriber interface. [\#53](https://github.com/php-enqueue/enqueue-dev/issues/53)
- Additional drivers [\#32](https://github.com/php-enqueue/enqueue-dev/issues/32)

- Multiple consumer handling one message [\#62](https://github.com/php-enqueue/enqueue-dev/issues/62)

## [0.3.4](https://github.com/php-enqueue/enqueue-dev/tree/0.3.4) (2017-04-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.3...0.3.4)

## [0.3.3](https://github.com/php-enqueue/enqueue-dev/tree/0.3.3) (2017-04-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.2...0.3.3)

- Move some dependencies to dev section [\#57](https://github.com/php-enqueue/enqueue-dev/issues/57)

## [0.3.2](https://github.com/php-enqueue/enqueue-dev/tree/0.3.2) (2017-04-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.1...0.3.2)

## [0.3.1](https://github.com/php-enqueue/enqueue-dev/tree/0.3.1) (2017-04-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.0...0.3.1)

- \[client\] Rename MessageProducer to Producer. To be similar what Psr has [\#42](https://github.com/php-enqueue/enqueue-dev/issues/42)

- \[transport\] Add Psr prefix to transport interfaces.  [\#44](https://github.com/php-enqueue/enqueue-dev/issues/44)

## [0.3.0](https://github.com/php-enqueue/enqueue-dev/tree/0.3.0) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.12...0.3.0)

## [0.2.12](https://github.com/php-enqueue/enqueue-dev/tree/0.2.12) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.11...0.2.12)

- \[consumption\] Need an extension point after the message is processed but before the ack\reject actually is done. [\#43](https://github.com/php-enqueue/enqueue-dev/issues/43)

## [0.2.11](https://github.com/php-enqueue/enqueue-dev/tree/0.2.11) (2017-04-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.10...0.2.11)

## [0.2.10](https://github.com/php-enqueue/enqueue-dev/tree/0.2.10) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.9...0.2.10)

## [0.2.9](https://github.com/php-enqueue/enqueue-dev/tree/0.2.9) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.8...0.2.9)

## [0.2.8](https://github.com/php-enqueue/enqueue-dev/tree/0.2.8) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.7...0.2.8)

- Do not print "Idle" when consumer is run with debug level \(-vvv\) [\#35](https://github.com/php-enqueue/enqueue-dev/issues/35)
- \[amqp\] Move RabbitMQ specific logic from AmqpDriver to RabbitMQAmqpDriver. [\#20](https://github.com/php-enqueue/enqueue-dev/issues/20)
- \[filesystem\] Consumer::receive method impr. Add file\_size check to the loop [\#15](https://github.com/php-enqueue/enqueue-dev/issues/15)

- \[client\] DelayRedeliveredMessagesExtension must do nothing if the result\status has been already set [\#36](https://github.com/php-enqueue/enqueue-dev/issues/36)

- Invalid typehint for Enqueue\Client\Message::setBody [\#31](https://github.com/php-enqueue/enqueue-dev/issues/31)

## [0.2.7](https://github.com/php-enqueue/enqueue-dev/tree/0.2.7) (2017-03-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.6...0.2.7)

## [0.2.6](https://github.com/php-enqueue/enqueue-dev/tree/0.2.6) (2017-03-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.5...0.2.6)

- \[bundle\]\[doc\] desctibe message processor's tag options. [\#23](https://github.com/php-enqueue/enqueue-dev/issues/23)

## [0.2.5](https://github.com/php-enqueue/enqueue-dev/tree/0.2.5) (2017-01-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.4...0.2.5)

- \[amqp\]\[bug\] Consumer received message targeted for another consumer of this same channel [\#13](https://github.com/php-enqueue/enqueue-dev/issues/13)

- \[travis\] Test against different Symfony versions, at least 2.8, 3.0, 3.1 [\#17](https://github.com/php-enqueue/enqueue-dev/issues/17)
- \[docker\] Build images for all containers that built from Dockerfiles.  [\#16](https://github.com/php-enqueue/enqueue-dev/issues/16)

## [0.2.4](https://github.com/php-enqueue/enqueue-dev/tree/0.2.4) (2017-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.3...0.2.4)

## [0.2.3](https://github.com/php-enqueue/enqueue-dev/tree/0.2.3) (2017-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.2...0.2.3)

## [0.2.2](https://github.com/php-enqueue/enqueue-dev/tree/0.2.2) (2017-01-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.1...0.2.2)

- Amqp lazy connection  [\#4](https://github.com/php-enqueue/enqueue-dev/issues/4)

## [0.2.1](https://github.com/php-enqueue/enqueue-dev/tree/0.2.1) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.0...0.2.1)

## [0.2.0](https://github.com/php-enqueue/enqueue-dev/tree/0.2.0) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.1.0...0.2.0)

## [0.1.0](https://github.com/php-enqueue/enqueue-dev/tree/0.1.0) (2016-12-29)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*