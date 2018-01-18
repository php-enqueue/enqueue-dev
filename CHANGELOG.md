# Change Log

## [0.8.17](https://github.com/php-enqueue/enqueue-dev/tree/0.8.17) (2018-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.16...0.8.17)

- QueueConsumer should be final  [\#311](https://github.com/php-enqueue/enqueue-dev/issues/311)
- \[consumption\] Make QueueConsumer final [\#336](https://github.com/php-enqueue/enqueue-dev/pull/336) ([makasim](https://github.com/makasim))
- \[bundle\]\[dx\] Add a message that suggest installing a pkg to use the transport. [\#335](https://github.com/php-enqueue/enqueue-dev/pull/335) ([makasim](https://github.com/makasim))
- \[0.9\]\[BC break\]\[dbal\] Store UUIDs as binary data. Improves performance [\#280](https://github.com/php-enqueue/enqueue-dev/pull/280) ([makasim](https://github.com/makasim))

- Unrecognized option "amqp" under "enqueue.transport" [\#333](https://github.com/php-enqueue/enqueue-dev/issues/333)

- \[consumption\] Prepare QueueConsumer for changes in 0.9 [\#337](https://github.com/php-enqueue/enqueue-dev/pull/337) ([makasim](https://github.com/makasim))

## [0.8.16](https://github.com/php-enqueue/enqueue-dev/tree/0.8.16) (2018-01-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.15...0.8.16)

- \[Sqs\] Allow array-based DSN configuration [\#315](https://github.com/php-enqueue/enqueue-dev/pull/315) ([beryllium](https://github.com/beryllium))

## [0.8.15](https://github.com/php-enqueue/enqueue-dev/tree/0.8.15) (2018-01-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.14...0.8.15)

- \[consumption\] Correct message in LoggerExtension [\#322](https://github.com/php-enqueue/enqueue-dev/pull/322) ([makasim](https://github.com/makasim))

- \[amqp\] fix signal handler if consume called from consume [\#328](https://github.com/php-enqueue/enqueue-dev/pull/328) ([makasim](https://github.com/makasim))

- SQS via DNS region missing [\#321](https://github.com/php-enqueue/enqueue-dev/issues/321)

- Update config\_reference.md [\#326](https://github.com/php-enqueue/enqueue-dev/pull/326) ([errogaht](https://github.com/errogaht))
- Update message\_producer.md [\#325](https://github.com/php-enqueue/enqueue-dev/pull/325) ([errogaht](https://github.com/errogaht))
- Update consumption\_extension.md [\#324](https://github.com/php-enqueue/enqueue-dev/pull/324) ([errogaht](https://github.com/errogaht))

## [0.8.14](https://github.com/php-enqueue/enqueue-dev/tree/0.8.14) (2018-01-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.13...0.8.14)

## [0.8.13](https://github.com/php-enqueue/enqueue-dev/tree/0.8.13) (2018-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.12...0.8.13)

- \[amqp\] Fix socket and signal issue. [\#317](https://github.com/php-enqueue/enqueue-dev/pull/317) ([makasim](https://github.com/makasim))
- \[kafka\] add ability to set offset. [\#314](https://github.com/php-enqueue/enqueue-dev/pull/314) ([makasim](https://github.com/makasim))

- AMQPIOWaitException upon docker container shutdown [\#300](https://github.com/php-enqueue/enqueue-dev/issues/300)

## [0.8.12](https://github.com/php-enqueue/enqueue-dev/tree/0.8.12) (2018-01-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.11...0.8.12)

- \[Elastica\] convert the Doctrine listeners to async too [\#244](https://github.com/php-enqueue/enqueue-dev/issues/244)
- Using Laravel helper to resolve filepath [\#302](https://github.com/php-enqueue/enqueue-dev/pull/302) ([robinvdvleuten](https://github.com/robinvdvleuten))
- Job queue create tables [\#293](https://github.com/php-enqueue/enqueue-dev/pull/293) ([makasim](https://github.com/makasim))

- \[amqp-ext\] Unrecognized options "login, password, delay\_plugin\_installed" under "enqueue.transport.rabbitmq\_amqp [\#309](https://github.com/php-enqueue/enqueue-dev/issues/309)
- Symfony Bundle: amqp bunny doesn't stop the execution of the CLI command [\#303](https://github.com/php-enqueue/enqueue-dev/issues/303)
- \[rdkafka\] Don't do unnecessary subscribe\unsubscribe on every receive call [\#313](https://github.com/php-enqueue/enqueue-dev/pull/313) ([makasim](https://github.com/makasim))
- \[consumption\] Fix signal handling when AMQP is used. [\#310](https://github.com/php-enqueue/enqueue-dev/pull/310) ([makasim](https://github.com/makasim))
- Check if logger exists [\#299](https://github.com/php-enqueue/enqueue-dev/pull/299) ([pascaldevink](https://github.com/pascaldevink))

- Changed larvel to laravel [\#301](https://github.com/php-enqueue/enqueue-dev/pull/301) ([robinvdvleuten](https://github.com/robinvdvleuten))
- Fix reversed logic for native UUID detection [\#297](https://github.com/php-enqueue/enqueue-dev/pull/297) ([msheakoski](https://github.com/msheakoski))

## [0.8.11](https://github.com/php-enqueue/enqueue-dev/tree/0.8.11) (2017-12-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.10...0.8.11)

- \[job-queue\] Change type hint from Closure to callable [\#286](https://github.com/php-enqueue/enqueue-dev/issues/286)
- \[job-queue\] Change typehint, allow not only Closure but other callabl… [\#292](https://github.com/php-enqueue/enqueue-dev/pull/292) ([makasim](https://github.com/makasim))
- \[doc\] yii2-queue amqp driver [\#282](https://github.com/php-enqueue/enqueue-dev/pull/282) ([makasim](https://github.com/makasim))

- Consumer Requeue -\> DBAL NotNullConstraintViolationException [\#290](https://github.com/php-enqueue/enqueue-dev/issues/290)
- Set custom logger [\#287](https://github.com/php-enqueue/enqueue-dev/issues/287)
- \[Elastica\] persistence.driver = orm is optional [\#245](https://github.com/php-enqueue/enqueue-dev/issues/245)
- \[dbal\] Fix message re-queuing. Reuse producer for it. [\#291](https://github.com/php-enqueue/enqueue-dev/pull/291) ([makasim](https://github.com/makasim))
- \[consumption\] Add ability to overwrite logger. [\#289](https://github.com/php-enqueue/enqueue-dev/pull/289) ([makasim](https://github.com/makasim))

- \[composer\] Add support details to composer.json [\#288](https://github.com/php-enqueue/enqueue-dev/issues/288)

## [0.8.10](https://github.com/php-enqueue/enqueue-dev/tree/0.8.10) (2017-12-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.9...0.8.10)

- \[dbal\] Store id \(uuid\) as binary data. [\#279](https://github.com/php-enqueue/enqueue-dev/issues/279)
- \[doc\] Add a doc with job queue doctrine migration example [\#278](https://github.com/php-enqueue/enqueue-dev/issues/278)
- Add mongodb support. [\#251](https://github.com/php-enqueue/enqueue-dev/issues/251)
- Add zeromq support [\#208](https://github.com/php-enqueue/enqueue-dev/issues/208)
- \[amqp-lib\] It should be possible to create queue without a name,  [\#145](https://github.com/php-enqueue/enqueue-dev/issues/145)
- \[doc\] Add the doc for client extensions [\#73](https://github.com/php-enqueue/enqueue-dev/issues/73)
- \[doc\]\[skip ci\] add doc for client on send extensions. [\#285](https://github.com/php-enqueue/enqueue-dev/pull/285) ([makasim](https://github.com/makasim))
- \[doc\]\[skip ci\] Add processor examples, notes on exception and more. [\#283](https://github.com/php-enqueue/enqueue-dev/pull/283) ([makasim](https://github.com/makasim))
- \[travis\] add PHP 7.2 to build matrix. [\#281](https://github.com/php-enqueue/enqueue-dev/pull/281) ([makasim](https://github.com/makasim))

- \[enqueue/dbal\] Logic for "The platform does not support UUIDs natively" is incorrect [\#276](https://github.com/php-enqueue/enqueue-dev/issues/276)

## [0.8.9](https://github.com/php-enqueue/enqueue-dev/tree/0.8.9) (2017-11-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.8...0.8.9)

- \[rdkafka\] Introduce KeySerializer  [\#255](https://github.com/php-enqueue/enqueue-dev/issues/255)
- \[amqp-lib\]\[RabbitMQ\] Publisher Confirms [\#206](https://github.com/php-enqueue/enqueue-dev/issues/206)
- \[client\]\[amqp\] Idea. Add support of dead queues [\#39](https://github.com/php-enqueue/enqueue-dev/issues/39)
- \[docker\] Incorporate amqp ext compilation to docker build process. [\#275](https://github.com/php-enqueue/enqueue-dev/pull/275) ([makasim](https://github.com/makasim))
- \[fs\] Copy past Symfony's LockHandler \(not awailable in Sf4\). [\#272](https://github.com/php-enqueue/enqueue-dev/pull/272) ([makasim](https://github.com/makasim))
- Add Symfony4 support [\#269](https://github.com/php-enqueue/enqueue-dev/pull/269) ([makasim](https://github.com/makasim))
- \[bundle\] use enqueue logo in profiler panel. [\#268](https://github.com/php-enqueue/enqueue-dev/pull/268) ([makasim](https://github.com/makasim))

- \[bundle\] Apparently the use case tests have never worked properly. [\#273](https://github.com/php-enqueue/enqueue-dev/pull/273) ([makasim](https://github.com/makasim))
- \[rdkafka\] do not pass config if it was not set explisitly. [\#263](https://github.com/php-enqueue/enqueue-dev/pull/263) ([makasim](https://github.com/makasim))

- \[amqp-ext\] Problem with consume messages [\#274](https://github.com/php-enqueue/enqueue-dev/issues/274)

## [0.8.8](https://github.com/php-enqueue/enqueue-dev/tree/0.8.8) (2017-11-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.7...0.8.8)

- \[Redis\] add dsn support for symfony bundle. [\#266](https://github.com/php-enqueue/enqueue-dev/pull/266) ([wilson-ng](https://github.com/wilson-ng))

- onIdle is not triggered [\#260](https://github.com/php-enqueue/enqueue-dev/issues/260)
- On exception Context is not set  [\#259](https://github.com/php-enqueue/enqueue-dev/issues/259)
- \[consumption\]\[amqp\] onIdle is never called. [\#265](https://github.com/php-enqueue/enqueue-dev/pull/265) ([makasim](https://github.com/makasim))
- \[consumption\] fix context is missing message on exception. [\#264](https://github.com/php-enqueue/enqueue-dev/pull/264) ([makasim](https://github.com/makasim))

## [0.8.7](https://github.com/php-enqueue/enqueue-dev/tree/0.8.7) (2017-11-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.6...0.8.7)

- \[Redis\] add custom database index [\#258](https://github.com/php-enqueue/enqueue-dev/pull/258) ([IndraGunawan](https://github.com/IndraGunawan))

- SetRouterPropertiesExtension does not work with SQS [\#261](https://github.com/php-enqueue/enqueue-dev/issues/261)

- Changes SetRouterPropertiesExtension to use the driver to generate the queue name [\#262](https://github.com/php-enqueue/enqueue-dev/pull/262) ([iainmckay](https://github.com/iainmckay))

## [0.8.6](https://github.com/php-enqueue/enqueue-dev/tree/0.8.6) (2017-11-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.5...0.8.6)

- \[RdKafka\] Enable serializers to serialize message keys [\#254](https://github.com/php-enqueue/enqueue-dev/pull/254) ([tPl0ch](https://github.com/tPl0ch))

- \[Elastica Bundle\] tag 0.8 [\#253](https://github.com/php-enqueue/enqueue-dev/issues/253)

## [0.8.5](https://github.com/php-enqueue/enqueue-dev/tree/0.8.5) (2017-11-02)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.4...0.8.5)

- Amqp add ssl pass phrase option [\#249](https://github.com/php-enqueue/enqueue-dev/pull/249) ([makasim](https://github.com/makasim))

- \[amqp-lib\] Ignore empty ssl options. [\#248](https://github.com/php-enqueue/enqueue-dev/pull/248) ([makasim](https://github.com/makasim))

## [0.8.4](https://github.com/php-enqueue/enqueue-dev/tree/0.8.4) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.3...0.8.4)

## [0.8.3](https://github.com/php-enqueue/enqueue-dev/tree/0.8.3) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.2...0.8.3)

- \[Symfony\]\[Minor\] profiler view when no messages collected during the request [\#243](https://github.com/php-enqueue/enqueue-dev/issues/243)
- \[bundle\] streamline profiler view when no messages were sent [\#247](https://github.com/php-enqueue/enqueue-dev/pull/247) ([dkarlovi](https://github.com/dkarlovi))
- \[bundle\] Renamed exposed services' name to classes' FQCN [\#242](https://github.com/php-enqueue/enqueue-dev/pull/242) ([Lctrs](https://github.com/Lctrs))

## [0.8.2](https://github.com/php-enqueue/enqueue-dev/tree/0.8.2) (2017-10-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.1...0.8.2)

- \[amqp\] add ssl support  [\#147](https://github.com/php-enqueue/enqueue-dev/issues/147)
- \[amqp\] Add AMQP secure \(SSL\) connections support [\#246](https://github.com/php-enqueue/enqueue-dev/pull/246) ([makasim](https://github.com/makasim))

## [0.8.1](https://github.com/php-enqueue/enqueue-dev/tree/0.8.1) (2017-10-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.0...0.8.1)

- Allow kafka tests to fail.  [\#232](https://github.com/php-enqueue/enqueue-dev/issues/232)
- GPS Integration [\#239](https://github.com/php-enqueue/enqueue-dev/pull/239) ([ASKozienko](https://github.com/ASKozienko))

- GPSTransportFactory registration is missing from EnqueueBundle [\#235](https://github.com/php-enqueue/enqueue-dev/issues/235)

- Only add Ampq transport factories when packages are found [\#241](https://github.com/php-enqueue/enqueue-dev/pull/241) ([jverdeyen](https://github.com/jverdeyen))

## [0.8.0](https://github.com/php-enqueue/enqueue-dev/tree/0.8.0) (2017-10-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.19...0.8.0)

- \[amqp-lib\] The context should allow to get the lib's channel.  [\#146](https://github.com/php-enqueue/enqueue-dev/issues/146)
- 0.8v goes stable. [\#238](https://github.com/php-enqueue/enqueue-dev/pull/238) ([makasim](https://github.com/makasim))
- \[travis\] allow kafka tests to fail. [\#237](https://github.com/php-enqueue/enqueue-dev/pull/237) ([makasim](https://github.com/makasim))
- \[amqp\] One single transport factory for all supported amqp implementa… [\#233](https://github.com/php-enqueue/enqueue-dev/pull/233) ([makasim](https://github.com/makasim))
- \[BC break\]\[amqp\] Introduce connection config. Make it same across all transports. [\#228](https://github.com/php-enqueue/enqueue-dev/pull/228) ([makasim](https://github.com/makasim))

- \[amqp-bunny\] High CPU usage while using basic.consume.  [\#226](https://github.com/php-enqueue/enqueue-dev/issues/226)
- Amqp basic consume should restore default timeout inside consume callback.  [\#225](https://github.com/php-enqueue/enqueue-dev/issues/225)
- AmqpProducer::send method must throw only interop exception. [\#224](https://github.com/php-enqueue/enqueue-dev/issues/224)
- 0.8v goes stable. [\#238](https://github.com/php-enqueue/enqueue-dev/pull/238) ([makasim](https://github.com/makasim))
- \[consumption\]\[amqp\] move beforeReceive call at the end of the cycle f… [\#234](https://github.com/php-enqueue/enqueue-dev/pull/234) ([makasim](https://github.com/makasim))
- \\[BC break\\]\\[amqp\\] Introduce connection config. Make it same across all transports. [\#228](https://github.com/php-enqueue/enqueue-dev/pull/228) ([makasim](https://github.com/makasim))

- Missing client configuration in the documentation [\#231](https://github.com/php-enqueue/enqueue-dev/pull/231) ([lsv](https://github.com/lsv))
- Added MIT license badge [\#230](https://github.com/php-enqueue/enqueue-dev/pull/230) ([tarlepp](https://github.com/tarlepp))

## [0.7.19](https://github.com/php-enqueue/enqueue-dev/tree/0.7.19) (2017-10-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.18...0.7.19)

- Amqp basic consume fixes [\#223](https://github.com/php-enqueue/enqueue-dev/pull/223) ([makasim](https://github.com/makasim))
- Adds to small extension points to JobProcessor [\#222](https://github.com/php-enqueue/enqueue-dev/pull/222) ([iainmckay](https://github.com/iainmckay))
- \[BC break\]\[amqp\] Use same qos options across all all AMQP transports [\#221](https://github.com/php-enqueue/enqueue-dev/pull/221) ([makasim](https://github.com/makasim))
- \[BC break\] Amqp add basic consume support [\#217](https://github.com/php-enqueue/enqueue-dev/pull/217) ([makasim](https://github.com/makasim))

- Amqp basic consume fixes [\#223](https://github.com/php-enqueue/enqueue-dev/pull/223) ([makasim](https://github.com/makasim))

- Fix typo [\#227](https://github.com/php-enqueue/enqueue-dev/pull/227) ([f3ath](https://github.com/f3ath))

## [0.7.18](https://github.com/php-enqueue/enqueue-dev/tree/0.7.18) (2017-10-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.17...0.7.18)

- \[consumption\]\[client\] Add --skip option to consume commands. [\#216](https://github.com/php-enqueue/enqueue-dev/issues/216)
- \[json\] jsonSerialize could throw an a exception.  [\#132](https://github.com/php-enqueue/enqueue-dev/issues/132)
- \[client\] Add --skip option to consume command. [\#218](https://github.com/php-enqueue/enqueue-dev/pull/218) ([makasim](https://github.com/makasim))

## [0.7.17](https://github.com/php-enqueue/enqueue-dev/tree/0.7.17) (2017-10-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.16...0.7.17)

- \[Symfony\] Error using profiler with symfony 2.8 [\#211](https://github.com/php-enqueue/enqueue-dev/issues/211)
- \[fs\] ErrorException: The Symfony\Component\Filesystem\LockHandler class is deprecated since version 3.4 [\#166](https://github.com/php-enqueue/enqueue-dev/issues/166)
- Fs do not throw error on user deprecate [\#214](https://github.com/php-enqueue/enqueue-dev/pull/214) ([makasim](https://github.com/makasim))
- \[bundle\]\[profiler\] Fix array to string conversion notice. [\#212](https://github.com/php-enqueue/enqueue-dev/pull/212) ([makasim](https://github.com/makasim))

## [0.7.16](https://github.com/php-enqueue/enqueue-dev/tree/0.7.16) (2017-09-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.15...0.7.16)

- Fixes the notation for Twig template names in the data collector [\#207](https://github.com/php-enqueue/enqueue-dev/pull/207) ([Lctrs](https://github.com/Lctrs))

- \[BC Break\]\[dsn\] replace xxx:// to xxx: [\#205](https://github.com/php-enqueue/enqueue-dev/pull/205) ([makasim](https://github.com/makasim))

## [0.7.15](https://github.com/php-enqueue/enqueue-dev/tree/0.7.15) (2017-09-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.14...0.7.15)

- \[FS\]\[RFC\] Change to FIFO queue [\#171](https://github.com/php-enqueue/enqueue-dev/issues/171)
- Transports must support configuration via DSN string [\#87](https://github.com/php-enqueue/enqueue-dev/issues/87)
- Add support of async message processing to transport interfaces. Like Java JMS. [\#27](https://github.com/php-enqueue/enqueue-dev/issues/27)
- \[redis\] add dsn support for redis transport. [\#204](https://github.com/php-enqueue/enqueue-dev/pull/204) ([makasim](https://github.com/makasim))
- \[dbal\]\[bc break\] Performance improvements and new features. [\#199](https://github.com/php-enqueue/enqueue-dev/pull/199) ([makasim](https://github.com/makasim))

- \[FS\] Cannot decode json message [\#202](https://github.com/php-enqueue/enqueue-dev/issues/202)
- \[fs\] fix bugs introduced in \#181. [\#203](https://github.com/php-enqueue/enqueue-dev/pull/203) ([makasim](https://github.com/makasim))

- \[FS\] Cannot decode json message [\#201](https://github.com/php-enqueue/enqueue-dev/issues/201)
- \[FS\] Cannot decode json message [\#200](https://github.com/php-enqueue/enqueue-dev/issues/200)

## [0.7.14](https://github.com/php-enqueue/enqueue-dev/tree/0.7.14) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.13...0.7.14)

## [0.7.13](https://github.com/php-enqueue/enqueue-dev/tree/0.7.13) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.12...0.7.13)

- \[dbal\] add priority support on transport level. [\#198](https://github.com/php-enqueue/enqueue-dev/pull/198) ([makasim](https://github.com/makasim))

- Topic subscriber doesn't work with 2 separate apps [\#196](https://github.com/php-enqueue/enqueue-dev/issues/196)
- \\[dbal\\] add priority support on transport level. [\#198](https://github.com/php-enqueue/enqueue-dev/pull/198) ([makasim](https://github.com/makasim))
- Fixed losing message priority for dbal driver [\#195](https://github.com/php-enqueue/enqueue-dev/pull/195) ([vtsykun](https://github.com/vtsykun))

- \[bundle\] add tests for the case where topic subscriber does not def p… [\#197](https://github.com/php-enqueue/enqueue-dev/pull/197) ([makasim](https://github.com/makasim))

## [0.7.12](https://github.com/php-enqueue/enqueue-dev/tree/0.7.12) (2017-09-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.11...0.7.12)

- fixed NS [\#194](https://github.com/php-enqueue/enqueue-dev/pull/194) ([chdeliens](https://github.com/chdeliens))

## [0.7.11](https://github.com/php-enqueue/enqueue-dev/tree/0.7.11) (2017-09-11)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.10...0.7.11)

- Redis consumer has very high resource usage [\#191](https://github.com/php-enqueue/enqueue-dev/issues/191)

- Queue Consumer Options [\#193](https://github.com/php-enqueue/enqueue-dev/pull/193) ([ASKozienko](https://github.com/ASKozienko))
- \[FS\] Polling Interval [\#192](https://github.com/php-enqueue/enqueue-dev/pull/192) ([ASKozienko](https://github.com/ASKozienko))
- \[Symfony\] added toolbar info in profiler [\#190](https://github.com/php-enqueue/enqueue-dev/pull/190) ([Miliooo](https://github.com/Miliooo))
- docs cli\_commands.md fix [\#189](https://github.com/php-enqueue/enqueue-dev/pull/189) ([Miliooo](https://github.com/Miliooo))

## [0.7.10](https://github.com/php-enqueue/enqueue-dev/tree/0.7.10) (2017-08-31)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.9...0.7.10)

- Serialization beyond JSON [\#187](https://github.com/php-enqueue/enqueue-dev/issues/187)
- \[rdkafka\] Add abilito change the way a message is serialized. [\#188](https://github.com/php-enqueue/enqueue-dev/pull/188) ([makasim](https://github.com/makasim))

- Bug on AsyncDoctrineOrmProvider::setContext\(\) [\#186](https://github.com/php-enqueue/enqueue-dev/issues/186)

## [0.7.9](https://github.com/php-enqueue/enqueue-dev/tree/0.7.9) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.8...0.7.9)

- Update to phpstan 0.8 [\#141](https://github.com/php-enqueue/enqueue-dev/issues/141)
- \[client\] Add a reason while setting reject in DelayRedeliveredMessageExtension [\#41](https://github.com/php-enqueue/enqueue-dev/issues/41)

- \[Doctrine\] add support to convert Doctrine events to Enqueue messages [\#68](https://github.com/php-enqueue/enqueue-dev/issues/68)

- \[client\] DelayRedeliveredMessageExtension. Add reject reason. [\#185](https://github.com/php-enqueue/enqueue-dev/pull/185) ([makasim](https://github.com/makasim))
- \[phpstan\] update to 0.8 version [\#184](https://github.com/php-enqueue/enqueue-dev/pull/184) ([makasim](https://github.com/makasim))

## [0.7.8](https://github.com/php-enqueue/enqueue-dev/tree/0.7.8) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.7...0.7.8)

- fix sqs tests when run by not a member of the project. [\#179](https://github.com/php-enqueue/enqueue-dev/issues/179)
- \[bundle\] It is not possible to use client's producer in a cli event, for example on exception [\#177](https://github.com/php-enqueue/enqueue-dev/issues/177)
- Error on PurgeFosElasticPopulateQueueListener::\_\_construct\(\) [\#174](https://github.com/php-enqueue/enqueue-dev/issues/174)
- \[bundle\] Possible issue when something configured wronly [\#172](https://github.com/php-enqueue/enqueue-dev/issues/172)
- \[FS\] Frame not being read correctly [\#170](https://github.com/php-enqueue/enqueue-dev/issues/170)

- \[consumption\] Do not close context. [\#183](https://github.com/php-enqueue/enqueue-dev/pull/183) ([makasim](https://github.com/makasim))
- \[bundle\] do not use client's related stuff if it is disabled [\#182](https://github.com/php-enqueue/enqueue-dev/pull/182) ([makasim](https://github.com/makasim))
- \[fs\] fix bug that happens with specific message length. [\#181](https://github.com/php-enqueue/enqueue-dev/pull/181) ([makasim](https://github.com/makasim))
- \[sqs\] Skip tests if no amazon credentinals present. [\#180](https://github.com/php-enqueue/enqueue-dev/pull/180) ([makasim](https://github.com/makasim))
- Fix typo in configuration parameter [\#178](https://github.com/php-enqueue/enqueue-dev/pull/178) ([akucherenko](https://github.com/akucherenko))
- Google Pub/Sub [\#167](https://github.com/php-enqueue/enqueue-dev/pull/167) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.7](https://github.com/php-enqueue/enqueue-dev/tree/0.7.7) (2017-08-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.6...0.7.7)

- Add support for Google Cloud Pub/Sub [\#83](https://github.com/php-enqueue/enqueue-dev/issues/83)

- Use Query Builder for better support across platforms. [\#176](https://github.com/php-enqueue/enqueue-dev/pull/176) ([jenkoian](https://github.com/jenkoian))
- fix pheanstalk redelivered, receive [\#173](https://github.com/php-enqueue/enqueue-dev/pull/173) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.6](https://github.com/php-enqueue/enqueue-dev/tree/0.7.6) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.5...0.7.6)

## [0.7.5](https://github.com/php-enqueue/enqueue-dev/tree/0.7.5) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.4...0.7.5)

- Bundle disable async events by default [\#169](https://github.com/php-enqueue/enqueue-dev/pull/169) ([makasim](https://github.com/makasim))
- Delay Strategy Configuration [\#162](https://github.com/php-enqueue/enqueue-dev/pull/162) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.4](https://github.com/php-enqueue/enqueue-dev/tree/0.7.4) (2017-08-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.3...0.7.4)

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