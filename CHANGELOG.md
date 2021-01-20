# Change Log

## [0.10.6](https://github.com/php-enqueue/enqueue-dev/tree/0.10.6) (2020-10-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.5...0.10.6)

**Merged pull requests:**

- fixing issue \#1085 [\#1105](https://github.com/php-enqueue/enqueue-dev/pull/1105) ([nivpenso](https://github.com/nivpenso))
- Fix DoctrineConnectionFactoryFactory due to doctrine/common changes [\#1089](https://github.com/php-enqueue/enqueue-dev/pull/1089) ([kdefives](https://github.com/kdefives))

## [0.10.5](https://github.com/php-enqueue/enqueue-dev/tree/0.10.5) (2020-10-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.4...0.10.5)

**Merged pull requests:**

- update image [\#1104](https://github.com/php-enqueue/enqueue-dev/pull/1104) ([nick-zh](https://github.com/nick-zh))
- \[rdkafka\]use supported librdkafka version of ext [\#1103](https://github.com/php-enqueue/enqueue-dev/pull/1103) ([nick-zh](https://github.com/nick-zh))
- \[rdkafka\] add non-blocking poll call to serve cb's [\#1102](https://github.com/php-enqueue/enqueue-dev/pull/1102) ([nick-zh](https://github.com/nick-zh))
- \[rdkafka\] remove topic conf, deprecated [\#1101](https://github.com/php-enqueue/enqueue-dev/pull/1101) ([nick-zh](https://github.com/nick-zh))
- \[stomp\] Fix - Add automatic reconnect support for STOMP producers [\#1099](https://github.com/php-enqueue/enqueue-dev/pull/1099) ([atrauzzi](https://github.com/atrauzzi))
- fix localstack version \(one that worked\) [\#1094](https://github.com/php-enqueue/enqueue-dev/pull/1094) ([makasim](https://github.com/makasim))
- Allow false-y values for unsupported options [\#1093](https://github.com/php-enqueue/enqueue-dev/pull/1093) ([atrauzzi](https://github.com/atrauzzi))
- Lock doctrine perisistence version. Fix tests. [\#1092](https://github.com/php-enqueue/enqueue-dev/pull/1092) ([makasim](https://github.com/makasim))

## [0.10.4](https://github.com/php-enqueue/enqueue-dev/tree/0.10.4) (2020-09-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.3...0.10.4)

**Merged pull requests:**

- \[stomp\] Add first pass for Apache ActiveMQ Artemis support [\#1091](https://github.com/php-enqueue/enqueue-dev/pull/1091) ([atrauzzi](https://github.com/atrauzzi))
- \[amqp\]Solves binding Headers Exchange with Queue using custom arguments [\#1087](https://github.com/php-enqueue/enqueue-dev/pull/1087) ([dgafka](https://github.com/dgafka))
- \[async-command\] Fix service definition to apply the timeout [\#1084](https://github.com/php-enqueue/enqueue-dev/pull/1084) ([jcrombez](https://github.com/jcrombez))
- \[mongodb\] fix\(MongoDB\) Redelivery not working \(fixes \#1077\) [\#1078](https://github.com/php-enqueue/enqueue-dev/pull/1078) ([josefsabl](https://github.com/josefsabl))
- Add php 7.3 and 7.4 travis env to every package [\#1076](https://github.com/php-enqueue/enqueue-dev/pull/1076) ([snapshotpl](https://github.com/snapshotpl))
- Docs: update Supported Brokers [\#1074](https://github.com/php-enqueue/enqueue-dev/pull/1074) ([Nebual](https://github.com/Nebual))
- \[rdkafka\] Compatibility with Phprdkafka 4.0 [\#959](https://github.com/php-enqueue/enqueue-dev/pull/959) ([Steveb-p](https://github.com/Steveb-p))

## [0.10.3](https://github.com/php-enqueue/enqueue-dev/tree/0.10.3) (2020-07-31)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.2...0.10.3)

**Merged pull requests:**

- Allow to install ramsey/uuid:^4 [\#1075](https://github.com/php-enqueue/enqueue-dev/pull/1075) ([snapshotpl](https://github.com/snapshotpl))
- chore: add typehint to RdKafkaConsumer\#getQueue [\#1071](https://github.com/php-enqueue/enqueue-dev/pull/1071) ([qkdreyer](https://github.com/qkdreyer))
- Fixes typo on client messages exemples doc [\#1065](https://github.com/php-enqueue/enqueue-dev/pull/1065) ([brunousml](https://github.com/brunousml))
- Fix contact us link [\#1058](https://github.com/php-enqueue/enqueue-dev/pull/1058) ([andrew-demb](https://github.com/andrew-demb))
- Fix typos [\#1049](https://github.com/php-enqueue/enqueue-dev/pull/1049) ([pgrimaud](https://github.com/pgrimaud))
- Added support for ramsey/uuid 4.0 [\#1043](https://github.com/php-enqueue/enqueue-dev/pull/1043) ([a-menshchikov](https://github.com/a-menshchikov))
- Changed: cast redelivery\_delay to int [\#1034](https://github.com/php-enqueue/enqueue-dev/pull/1034) ([balabis](https://github.com/balabis))
- Add php 7.4 to test matrix [\#991](https://github.com/php-enqueue/enqueue-dev/pull/991) ([snapshotpl](https://github.com/snapshotpl))

## [0.10.2](https://github.com/php-enqueue/enqueue-dev/tree/0.10.2) (2020-03-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.1...0.10.2)

**Merged pull requests:**

- Implement DeliveryDelay, Priority and TimeToLive in PheanstalkProducer [\#1033](https://github.com/php-enqueue/enqueue-dev/pull/1033) ([likeuntomurphy](https://github.com/likeuntomurphy))
- fix\(mongodb\): Exception throwing fatal error, Broken handling of Mong… [\#1032](https://github.com/php-enqueue/enqueue-dev/pull/1032) ([josefsabl](https://github.com/josefsabl))
- RUN\_COMMAND Option example [\#1030](https://github.com/php-enqueue/enqueue-dev/pull/1030) ([gam6itko](https://github.com/gam6itko))
- typo [\#1026](https://github.com/php-enqueue/enqueue-dev/pull/1026) ([sebastianneubert](https://github.com/sebastianneubert))
- Add extension tag parameter note [\#1023](https://github.com/php-enqueue/enqueue-dev/pull/1023) ([Steveb-p](https://github.com/Steveb-p))
- STOMP. add additional configuration [\#1018](https://github.com/php-enqueue/enqueue-dev/pull/1018) ([versh23](https://github.com/versh23))

## [0.10.1](https://github.com/php-enqueue/enqueue-dev/tree/0.10.1) (2020-01-31)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.10.0...0.10.1)

**Merged pull requests:**

- \[dbal\] fix: allow absolute paths for sqlite transport [\#1015](https://github.com/php-enqueue/enqueue-dev/pull/1015) ([cawolf](https://github.com/cawolf))
- \[tests\] Add schema declaration to phpunit files [\#1014](https://github.com/php-enqueue/enqueue-dev/pull/1014) ([Steveb-p](https://github.com/Steveb-p))
- \[rdkafka\] Catch consume error "Local: Broker transport failure" and continue consume [\#1009](https://github.com/php-enqueue/enqueue-dev/pull/1009) ([rdotter](https://github.com/rdotter))
- \[sqs\] SQS Transport - Add support for AWS profiles. [\#1008](https://github.com/php-enqueue/enqueue-dev/pull/1008) ([bgaillard](https://github.com/bgaillard))
- \[amqp\] fixes \#1003 Return value of Enqueue\AmqpLib\AmqpContext::declareQueue() must be of the type int [\#1004](https://github.com/php-enqueue/enqueue-dev/pull/1004) ([kalyabin](https://github.com/kalyabin))
- \[gearman\] Gearman Consumer receive should only fetch one message [\#998](https://github.com/php-enqueue/enqueue-dev/pull/998) ([arep](https://github.com/arep))
- \[sqs\] add messageId to the sqsMessage [\#992](https://github.com/php-enqueue/enqueue-dev/pull/992) ([BenoitLeveque](https://github.com/BenoitLeveque))

## [0.10.0](https://github.com/php-enqueue/enqueue-dev/tree/0.10.0) (2019-12-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.15...0.10.0)

**Merged pull requests:**

- Symfony 5 [\#997](https://github.com/php-enqueue/enqueue-dev/pull/997) ([kuraobi](https://github.com/kuraobi))
- Replace the Magento 1 code into the Magento 2 documentation [\#999](https://github.com/php-enqueue/enqueue-dev/pull/999) ([hochgenug](https://github.com/hochgenug))
- Wrong parameter description [\#994](https://github.com/php-enqueue/enqueue-dev/pull/994) ([bramstroker](https://github.com/bramstroker))

## [0.9.15](https://github.com/php-enqueue/enqueue-dev/tree/0.9.15) (2019-11-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.14...0.9.15)

**Merged pull requests:**

- Fix Incompatibility for doctrine [\#988](https://github.com/php-enqueue/enqueue-dev/pull/988) ([Baachi](https://github.com/Baachi))
- Prefer early returns in consumer code [\#982](https://github.com/php-enqueue/enqueue-dev/pull/982) ([Steveb-p](https://github.com/Steveb-p))
- \#977 - Fix issues with MS SQL server and dbal transport [\#979](https://github.com/php-enqueue/enqueue-dev/pull/979) ([NeilWhitworth](https://github.com/NeilWhitworth))
- Add header support for Symfony's produce command [\#965](https://github.com/php-enqueue/enqueue-dev/pull/965) ([TiMESPLiNTER](https://github.com/TiMESPLiNTER))

## [0.9.14](https://github.com/php-enqueue/enqueue-dev/tree/0.9.14) (2019-10-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.13...0.9.14)

**Merged pull requests:**

- Fix deprecated heartbeat check method [\#967](https://github.com/php-enqueue/enqueue-dev/pull/967) ([ramunasd](https://github.com/ramunasd))
- Add missing rabbitmq DSN example [\#966](https://github.com/php-enqueue/enqueue-dev/pull/966) ([ramunasd](https://github.com/ramunasd))
- Fix empty class for autowired services \(Fix \#957\) [\#958](https://github.com/php-enqueue/enqueue-dev/pull/958) ([NicolasGuilloux](https://github.com/NicolasGuilloux))
- Add header support for kafka [\#955](https://github.com/php-enqueue/enqueue-dev/pull/955) ([TiMESPLiNTER](https://github.com/TiMESPLiNTER))
- Kafka singleton consumer [\#947](https://github.com/php-enqueue/enqueue-dev/pull/947) ([dirk39](https://github.com/dirk39))

## [0.9.13](https://github.com/php-enqueue/enqueue-dev/tree/0.9.13) (2019-09-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.12...0.9.13)

**Merged pull requests:**

- docs: describe drawbacks of using amqp extension [\#942](https://github.com/php-enqueue/enqueue-dev/pull/942) ([gnumoksha](https://github.com/gnumoksha))
- Add a service to reset doctrine/odm identity maps [\#933](https://github.com/php-enqueue/enqueue-dev/pull/933) ([Lctrs](https://github.com/Lctrs))
- Add an extension to stop consumption on closed entity manager [\#932](https://github.com/php-enqueue/enqueue-dev/pull/932) ([Lctrs](https://github.com/Lctrs))
- Add an extension to reset services [\#929](https://github.com/php-enqueue/enqueue-dev/pull/929) ([Lctrs](https://github.com/Lctrs))
- \[DoctrineClearIdentityMapExtension\] allow instances of ManagerRegistry [\#927](https://github.com/php-enqueue/enqueue-dev/pull/927) ([Lctrs](https://github.com/Lctrs))
- Link to documentation from logo [\#926](https://github.com/php-enqueue/enqueue-dev/pull/926) ([Steveb-p](https://github.com/Steveb-p))
- DBAL Change ParameterType class to Type class [\#916](https://github.com/php-enqueue/enqueue-dev/pull/916) ([Nevoss](https://github.com/Nevoss))
- async\_commands: extended configuration proposal [\#914](https://github.com/php-enqueue/enqueue-dev/pull/914) ([uro](https://github.com/uro))

## [0.9.12](https://github.com/php-enqueue/enqueue-dev/tree/0.9.12) (2019-06-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.11...0.9.12)

**Merged pull requests:**

- \[SNSQS\] Fix issue with delay [\#909](https://github.com/php-enqueue/enqueue-dev/pull/909) ([uro](https://github.com/uro))
- \[SNS\] Fix: Missing throw issue [\#908](https://github.com/php-enqueue/enqueue-dev/pull/908) ([uro](https://github.com/uro))
- \[SNS\] Adding generic driver for schema SNS [\#906](https://github.com/php-enqueue/enqueue-dev/pull/906) ([Nyholm](https://github.com/Nyholm))
- \[SQS\] deserialize sqs message attributes [\#901](https://github.com/php-enqueue/enqueue-dev/pull/901) ([bendavies](https://github.com/bendavies))
- \[SNS\] Updates dependencies requirements for sns\(qs\) [\#899](https://github.com/php-enqueue/enqueue-dev/pull/899) ([xavismeh](https://github.com/xavismeh))
- Cast int for redelivery\_delay and polling\_interval [\#896](https://github.com/php-enqueue/enqueue-dev/pull/896) ([linh4github](https://github.com/linh4github))
- \[doc\] Move support note to an external include file [\#892](https://github.com/php-enqueue/enqueue-dev/pull/892) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Allow reading headers from Kafka Message headers [\#891](https://github.com/php-enqueue/enqueue-dev/pull/891) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Fix Code Style in all files [\#889](https://github.com/php-enqueue/enqueue-dev/pull/889) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Move "key concepts" to second position in menu. Fix typos. [\#886](https://github.com/php-enqueue/enqueue-dev/pull/886) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\]\[Bundle\] Expand quick tour for Symfony Bundle [\#885](https://github.com/php-enqueue/enqueue-dev/pull/885) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Fix link for cli commands [\#882](https://github.com/php-enqueue/enqueue-dev/pull/882) ([samnela](https://github.com/samnela))
- Add composer runnable scripts for PHPStan & PHP-CS [\#881](https://github.com/php-enqueue/enqueue-dev/pull/881) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Fixed quick tour link [\#878](https://github.com/php-enqueue/enqueue-dev/pull/878) ([samnela](https://github.com/samnela))
- \[doc\] Fix documentation links [\#877](https://github.com/php-enqueue/enqueue-dev/pull/877) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Add editor config settings for IDE's that support it [\#875](https://github.com/php-enqueue/enqueue-dev/pull/875) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Prefer github pages in packages' readme files [\#874](https://github.com/php-enqueue/enqueue-dev/pull/874) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Add Amazon SNS documentation placeholder [\#873](https://github.com/php-enqueue/enqueue-dev/pull/873) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Prefer github pages in readme [\#872](https://github.com/php-enqueue/enqueue-dev/pull/872) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Github Pages - Match topic order from index.md [\#870](https://github.com/php-enqueue/enqueue-dev/pull/870) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Github pages navigation structure [\#869](https://github.com/php-enqueue/enqueue-dev/pull/869) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Fixed the service id for Transport [\#868](https://github.com/php-enqueue/enqueue-dev/pull/868) ([samnela](https://github.com/samnela))
- \[doc\] Use organization repository for doc hosting [\#867](https://github.com/php-enqueue/enqueue-dev/pull/867) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Switch documentation to github pages [\#866](https://github.com/php-enqueue/enqueue-dev/pull/866) ([Steveb-p](https://github.com/Steveb-p))
- Prefer stable dependencies for development [\#865](https://github.com/php-enqueue/enqueue-dev/pull/865) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] Key concepts [\#863](https://github.com/php-enqueue/enqueue-dev/pull/863) ([sylfabre](https://github.com/sylfabre))
- \[doc\] Better Symfony doc nav [\#862](https://github.com/php-enqueue/enqueue-dev/pull/862) ([sylfabre](https://github.com/sylfabre))

## [0.9.11](https://github.com/php-enqueue/enqueue-dev/tree/0.9.11) (2019-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.10...0.9.11)

**Merged pull requests:**

- \[client\] Fix --logger option. Removed unintentionally set console logger. [\#861](https://github.com/php-enqueue/enqueue-dev/pull/861) ([makasim](https://github.com/makasim))
- \[client\] Fix reference to logger service. [\#860](https://github.com/php-enqueue/enqueue-dev/pull/860) ([makasim](https://github.com/makasim))
- \[consumption\] Fix bindCallback method will require new arg deprecation notice [\#859](https://github.com/php-enqueue/enqueue-dev/pull/859) ([makasim](https://github.com/makasim))
- \[amqp-bunny\] Revert "Fix heartbeat configuration in bunny with 0 \(off\) value" [\#855](https://github.com/php-enqueue/enqueue-dev/pull/855) ([DamienHarper](https://github.com/DamienHarper))
- \[sqs\] Requeue with a visibility timeout [\#852](https://github.com/php-enqueue/enqueue-dev/pull/852) ([deguif](https://github.com/deguif))
- \[monitoring\] Send topic and command for consumed messages [\#849](https://github.com/php-enqueue/enqueue-dev/pull/849) ([mariusbalcytis](https://github.com/mariusbalcytis))
- Fixed typo [\#856](https://github.com/php-enqueue/enqueue-dev/pull/856) ([samnela](https://github.com/samnela))

## [0.9.10](https://github.com/php-enqueue/enqueue-dev/tree/0.9.10) (2019-05-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.9...0.9.10)

**Merged pull requests:**

- \[client\] Lazy producer. [\#845](https://github.com/php-enqueue/enqueue-dev/pull/845) ([makasim](https://github.com/makasim))
- \[kafka\] Fix consumption errors in kafka against recent versions in librdkafka/phprdkafka [\#842](https://github.com/php-enqueue/enqueue-dev/pull/842) ([Steveb-p](https://github.com/Steveb-p))
- \[amqp-lib\] Fix un-initialized property use [\#836](https://github.com/php-enqueue/enqueue-dev/pull/836) ([Steveb-p](https://github.com/Steveb-p))
- \[amqp-bunny\] Fix heartbeat configuration in bunny with 0 \(off\) value [\#820](https://github.com/php-enqueue/enqueue-dev/pull/820) ([nightlinus](https://github.com/nightlinus))
- \[stomp\] Add support for using the /topic prefix instead of /exchange. [\#826](https://github.com/php-enqueue/enqueue-dev/pull/826) ([alessandroniciforo](https://github.com/alessandroniciforo))
- \[sns\] Allow setting SNS message attributes, other fields [\#799](https://github.com/php-enqueue/enqueue-dev/pull/799) ([aldenw](https://github.com/aldenw))
- Fixed docs [\#822](https://github.com/php-enqueue/enqueue-dev/pull/822) ([Toflar](https://github.com/Toflar))
- Typo on the tag [\#818](https://github.com/php-enqueue/enqueue-dev/pull/818) ([appeltaert](https://github.com/appeltaert))

## [0.9.9](https://github.com/php-enqueue/enqueue-dev/tree/0.9.9) (2019-04-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.8...0.9.9)

**Merged pull requests:**

- \[amqp-bunny\] Fix bunny producer to properly map headers to expected by bunny headers [\#816](https://github.com/php-enqueue/enqueue-dev/pull/816) ([nightlinus](https://github.com/nightlinus))
- \[amqp-bunny\]\[doc\] Update amqp\_bunny.md [\#797](https://github.com/php-enqueue/enqueue-dev/pull/797) ([enumag](https://github.com/enumag))
- \[dbal\] Fix DBAL Consumer duplicating messages when rejecting with requeue [\#815](https://github.com/php-enqueue/enqueue-dev/pull/815) ([Steveb-p](https://github.com/Steveb-p))
- \[rdkafka\] Set `commit\_async` as true by default for Kafka, update docs [\#810](https://github.com/php-enqueue/enqueue-dev/pull/810) ([Steveb-p](https://github.com/Steveb-p))
- \[rdkafka\] stats\_cb support [\#798](https://github.com/php-enqueue/enqueue-dev/pull/798) ([fkulakov](https://github.com/fkulakov))
- \[Monitoring\]\[InfluxDB\] Allow passing Client as configuration option. [\#809](https://github.com/php-enqueue/enqueue-dev/pull/809) ([Steveb-p](https://github.com/Steveb-p))
- \[doc\] better doc for traceable message producer [\#813](https://github.com/php-enqueue/enqueue-dev/pull/813) ([sylfabre](https://github.com/sylfabre))
- \[doc\] Minor typo fix in docblock [\#805](https://github.com/php-enqueue/enqueue-dev/pull/805) ([gpenverne](https://github.com/gpenverne))
- fix comment on QueueConsumer constructor [\#796](https://github.com/php-enqueue/enqueue-dev/pull/796) ([kaznovac](https://github.com/kaznovac))

## [0.9.8](https://github.com/php-enqueue/enqueue-dev/tree/0.9.8) (2019-02-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.7...0.9.8)

**Merged pull requests:**

- Add upgrade instructions [\#787](https://github.com/php-enqueue/enqueue-dev/pull/787) ([KDederichs](https://github.com/KDederichs))
- \[consumption\] Fix exception loop in QueueConsumer [\#776](https://github.com/php-enqueue/enqueue-dev/pull/776) ([enumag](https://github.com/enumag))
- \[consumption\] Add ability to change process exit status from within queue consumer extension [\#766](https://github.com/php-enqueue/enqueue-dev/pull/766) ([greblov](https://github.com/greblov))
- \[amqp-tools\] Fix amqp-tools dependency [\#785](https://github.com/php-enqueue/enqueue-dev/pull/785) ([TomPradat](https://github.com/TomPradat))
- \[amqp-tools\] Enable 'ssl\_on' param for 'ssl' scheme extension [\#781](https://github.com/php-enqueue/enqueue-dev/pull/781) ([Leprechaunz](https://github.com/Leprechaunz))
- \[amqp-bunny\] Catch signal in Bunny adapter [\#771](https://github.com/php-enqueue/enqueue-dev/pull/771) ([snapshotpl](https://github.com/snapshotpl))
- \[amqp-lib\] supporting channel\_rpc\_timeout option [\#755](https://github.com/php-enqueue/enqueue-dev/pull/755) ([derek9gag](https://github.com/derek9gag))
- \[dbal\]: make dbal connection config usable again [\#765](https://github.com/php-enqueue/enqueue-dev/pull/765) ([ssiergl](https://github.com/ssiergl))
- \[fs\] polling\_interval config should be milliseconds not microseconds [\#764](https://github.com/php-enqueue/enqueue-dev/pull/764) ([ssiergl](https://github.com/ssiergl))
- \[simple-client\] Fix Logger Initialisation [\#752](https://github.com/php-enqueue/enqueue-dev/pull/752) ([ajbonner](https://github.com/ajbonner))
- \[snsqs\] Corrected the installation part in the docs/transport/snsqs.md [\#791](https://github.com/php-enqueue/enqueue-dev/pull/791) ([dgreda](https://github.com/dgreda))
- \[sqs\] Update SqsConnectionFactory.php [\#751](https://github.com/php-enqueue/enqueue-dev/pull/751) ([Orkin](https://github.com/Orkin))
- correct typo in composer.json [\#767](https://github.com/php-enqueue/enqueue-dev/pull/767) ([greblov](https://github.com/greblov))

## [0.9.7](https://github.com/php-enqueue/enqueue-dev/tree/0.9.7) (2019-02-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.6...0.9.7)

**Merged pull requests:**

- Avoid OutOfMemoryException [\#725](https://github.com/php-enqueue/enqueue-dev/pull/725) ([DamienHarper](https://github.com/DamienHarper))
- \[async-event-dispatcher\] Add default to php\_serializer\_event\_transformer [\#748](https://github.com/php-enqueue/enqueue-dev/pull/748) ([GCalmels](https://github.com/GCalmels))
- \[async-event-dispatcher\] Fixed param on EventTransformer [\#736](https://github.com/php-enqueue/enqueue-dev/pull/736) ([samnela](https://github.com/samnela))
- \[job-queue\] Install stable dependencies [\#745](https://github.com/php-enqueue/enqueue-dev/pull/745) ([mbabic131](https://github.com/mbabic131))
- \[job-queue\] Fix job status processor [\#735](https://github.com/php-enqueue/enqueue-dev/pull/735) ([ASKozienko](https://github.com/ASKozienko))
- \[redis\] Fix messages sent with incorrect delivery delay [\#738](https://github.com/php-enqueue/enqueue-dev/pull/738) ([niels-nijens](https://github.com/niels-nijens))
- \[dbal\] Exception on affected record !=1 [\#733](https://github.com/php-enqueue/enqueue-dev/pull/733) ([otzy](https://github.com/otzy))
- \[bundle\]\[dbal\] Use doctrine bundle configured connections [\#732](https://github.com/php-enqueue/enqueue-dev/pull/732) ([ASKozienko](https://github.com/ASKozienko))
- \[pheanstalk\] Add unit tests for PheanstalkConsumer [\#726](https://github.com/php-enqueue/enqueue-dev/pull/726) ([alanpoulain](https://github.com/alanpoulain))
- \[pheanstalk\] Requeuing a message should not acknowledge it beforehand [\#722](https://github.com/php-enqueue/enqueue-dev/pull/722) ([alanpoulain](https://github.com/alanpoulain))
- \[sqs\] Dead Letter Queue Adoption [\#720](https://github.com/php-enqueue/enqueue-dev/pull/720) ([cshum](https://github.com/cshum))

## [0.9.6](https://github.com/php-enqueue/enqueue-dev/tree/0.9.6) (2019-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.5...0.9.6)

**Merged pull requests:**

- Fix async command/event pkgs [\#717](https://github.com/php-enqueue/enqueue-dev/pull/717) ([GCalmels](https://github.com/GCalmels))
- Use database from config in PRedis driver [\#715](https://github.com/php-enqueue/enqueue-dev/pull/715) ([lalov](https://github.com/lalov))
- \[monitoring\] Add support of Datadog [\#716](https://github.com/php-enqueue/enqueue-dev/pull/716) ([uro](https://github.com/uro))
- \[monitoring\] Fixed influxdb write on sentMessageStats [\#712](https://github.com/php-enqueue/enqueue-dev/pull/712) ([uro](https://github.com/uro))
- \[monitoring\] Add support for minimum stability - stable [\#711](https://github.com/php-enqueue/enqueue-dev/pull/711) ([uro](https://github.com/uro))
- \[consumption\] fix wrong niceness extension param [\#709](https://github.com/php-enqueue/enqueue-dev/pull/709) ([ramunasd](https://github.com/ramunasd))

## [0.9.5](https://github.com/php-enqueue/enqueue-dev/tree/0.9.5) (2018-12-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.4...0.9.5)

**Merged pull requests:**

- \[dbal\] Run tests on PostgreSQS [\#705](https://github.com/php-enqueue/enqueue-dev/pull/705) ([makasim](https://github.com/makasim))
- \[dbal\] Use string-based UUIDs instead of binary [\#698](https://github.com/php-enqueue/enqueue-dev/pull/698) ([jverdeyen](https://github.com/jverdeyen))

## [0.9.4](https://github.com/php-enqueue/enqueue-dev/tree/0.9.4) (2018-12-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.3...0.9.4)

**Merged pull requests:**

- \[client\] sendToProcessor should able to send message to router processor. [\#703](https://github.com/php-enqueue/enqueue-dev/pull/703) ([makasim](https://github.com/makasim))
- \[client\] Fix SetRouterPropertiesExtension should skip no topic messages. [\#702](https://github.com/php-enqueue/enqueue-dev/pull/702) ([makasim](https://github.com/makasim))
- \[client\] Fix Exclusive Command Extension ignores route queue prefix option. [\#701](https://github.com/php-enqueue/enqueue-dev/pull/701) ([makasim](https://github.com/makasim))
- \[amqp\] fix \#696 parsing vhost from amqp dsn [\#697](https://github.com/php-enqueue/enqueue-dev/pull/697) ([rpanfili](https://github.com/rpanfili))
- \[doc\] Fix link to declare queue [\#699](https://github.com/php-enqueue/enqueue-dev/pull/699) ([samnela](https://github.com/samnela))

## [0.9.3](https://github.com/php-enqueue/enqueue-dev/tree/0.9.3) (2018-12-17)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.2...0.9.3)

**Merged pull requests:**

- Fix async command package [\#694](https://github.com/php-enqueue/enqueue-dev/pull/694) ([makasim](https://github.com/makasim))
- Fix async events package [\#694](https://github.com/php-enqueue/enqueue-dev/pull/694) ([makasim](https://github.com/makasim))
- Add commands for single transport\client with typed arguments. [\#693](https://github.com/php-enqueue/enqueue-dev/pull/693) ([makasim](https://github.com/makasim))
- Fix TreeBuilder in Symfony 4.2 [\#692](https://github.com/php-enqueue/enqueue-dev/pull/692) ([angelsk](https://github.com/angelsk))
- [doc] update docs [\#689](https://github.com/php-enqueue/enqueue-dev/pull/689) ([OskarStark](https://github.com/OskarStark))

## [0.9.2](https://github.com/php-enqueue/enqueue-dev/tree/0.9.2) (2018-12-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.1...0.9.2)

**Merged pull requests:**

- Allow 0.8.x Queue Interop \(without deprecated Psr prefixed interfaces\)  [\#688](https://github.com/php-enqueue/enqueue-dev/pull/688) ([makasim](https://github.com/makasim))
- \[dsn\] remove commented out code [\#661](https://github.com/php-enqueue/enqueue-dev/pull/661) ([kunicmarko20](https://github.com/kunicmarko20))
- \[fs\]: fix: Wrong parameters for Exception [\#678](https://github.com/php-enqueue/enqueue-dev/pull/678) ([ssiergl](https://github.com/ssiergl))
- \[fs\] Do not throw error in jsonUnserialize on deprecation notice [\#671](https://github.com/php-enqueue/enqueue-dev/pull/671) ([ssiergl](https://github.com/ssiergl))
- \[mongodb\] polling\_integer type not correctly handled when using DSN [\#673](https://github.com/php-enqueue/enqueue-dev/pull/673) ([jak](https://github.com/jak))
- \[dbal\] Use ordered bytes time uuid codec on message id decode. [\#665](https://github.com/php-enqueue/enqueue-dev/pull/665) ([makasim](https://github.com/makasim))
- \[dbal\] fix: Wrong parameters for Exception [\#676](https://github.com/php-enqueue/enqueue-dev/pull/676) ([Nommyde](https://github.com/Nommyde))
- \[sqs\] Add ability to use another aws account per queue. [\#666](https://github.com/php-enqueue/enqueue-dev/pull/666) ([makasim](https://github.com/makasim))
- \[sqs\] Multi region support [\#664](https://github.com/php-enqueue/enqueue-dev/pull/664) ([makasim](https://github.com/makasim))
- \[sqs\] Use a queue created in another AWS account. [\#662](https://github.com/php-enqueue/enqueue-dev/pull/662) ([makasim](https://github.com/makasim))
- \[job-queue\] Fix tests on newer dbal versions. [\#687](https://github.com/php-enqueue/enqueue-dev/pull/687) ([makasim](https://github.com/makasim))
- [doc] typo [\#686](https://github.com/php-enqueue/enqueue-dev/pull/686) ([OskarStark](https://github.com/OskarStark))
- [doc] typo [\#683](https://github.com/php-enqueue/enqueue-dev/pull/683) ([OskarStark](https://github.com/OskarStark))
- [doc] Fix package name for redis [\#680](https://github.com/php-enqueue/enqueue-dev/pull/680) ([gnumoksha](https://github.com/gnumoksha))

## [0.9.1](https://github.com/php-enqueue/enqueue-dev/tree/0.9.1) (2018-11-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.9.0...0.9.1)

**Merged pull requests:**

- Allow installing stable dependencies. [\#660](https://github.com/php-enqueue/enqueue-dev/pull/660) ([makasim](https://github.com/makasim))

## [0.9.0](https://github.com/php-enqueue/enqueue-dev/tree/0.9) (2018-11-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.42...0.9)

**Merged pull requests:**

- \[amqp\]\[lib\] Improve heartbeat handling. Introduce heartbeat on tick. Fixes "Invalid frame type 65" and "Broken pipe or closed connection" [\#658](https://github.com/php-enqueue/enqueue-dev/pull/658) ([makasim](https://github.com/makasim))
- Redis dsn and password fixes [\#656](https://github.com/php-enqueue/enqueue-dev/pull/656) ([makasim](https://github.com/makasim))
- Fix ping to check each connection, not only first one [\#651](https://github.com/php-enqueue/enqueue-dev/pull/651) ([webmake](https://github.com/webmake))
- Rework DriverFactory, add separator option to Client Config. [\#646](https://github.com/php-enqueue/enqueue-dev/pull/646) ([makasim](https://github.com/makasim))
- \[dsn\] Parse DSN Cluster [\#643](https://github.com/php-enqueue/enqueue-dev/pull/643) ([makasim](https://github.com/makasim))
- \[dbal\] Use RetryableException, wrap fetchMessage exception to it too. [\#642](https://github.com/php-enqueue/enqueue-dev/pull/642) ([makasim](https://github.com/makasim))
- \[bundle\] Add BC for topic\command subscribers. [\#641](https://github.com/php-enqueue/enqueue-dev/pull/641) ([makasim](https://github.com/makasim))
- \[dbal\] handle gracefully concurrency issues or 3rd party interruptions.  [\#640](https://github.com/php-enqueue/enqueue-dev/pull/640) ([makasim](https://github.com/makasim))
- Fix compiler pass [\#639](https://github.com/php-enqueue/enqueue-dev/pull/639) ([ASKozienko](https://github.com/ASKozienko))
- Fix wrong exceptions in transports [\#637](https://github.com/php-enqueue/enqueue-dev/pull/637) ([FrankGiesecke](https://github.com/FrankGiesecke))
- Enable job-queue for default configuration [\#636](https://github.com/php-enqueue/enqueue-dev/pull/636) ([ASKozienko](https://github.com/ASKozienko))
- better readability [\#632](https://github.com/php-enqueue/enqueue-dev/pull/632) ([OskarStark](https://github.com/OskarStark))
- Fixed headline [\#631](https://github.com/php-enqueue/enqueue-dev/pull/631) ([OskarStark](https://github.com/OskarStark))
- \[bundle\] Multi Client Configuration [\#628](https://github.com/php-enqueue/enqueue-dev/pull/628) ([ASKozienko](https://github.com/ASKozienko))
- removed some dots [\#627](https://github.com/php-enqueue/enqueue-dev/pull/627) ([OskarStark](https://github.com/OskarStark))
- Avoid receiveNoWait when only one subscriber [\#626](https://github.com/php-enqueue/enqueue-dev/pull/626) ([deguif](https://github.com/deguif))
- Add context services to locator [\#623](https://github.com/php-enqueue/enqueue-dev/pull/623) ([Gnucki](https://github.com/Gnucki))
- \[doc\]\[skip ci\] Add sponsoring section. [\#618](https://github.com/php-enqueue/enqueue-dev/pull/618) ([makasim](https://github.com/makasim))
- Merge 0.8x -\> 0.9x [\#617](https://github.com/php-enqueue/enqueue-dev/pull/617) ([ASKozienko](https://github.com/ASKozienko))
- Compatibility with 0.8x [\#616](https://github.com/php-enqueue/enqueue-dev/pull/616) ([ASKozienko](https://github.com/ASKozienko))
- \[dbal\] Use concurrent fetch message approach \(no transaction, no pessimistic lock\) [\#613](https://github.com/php-enqueue/enqueue-dev/pull/613) ([makasim](https://github.com/makasim))
- \[fs\] Use enqueue/dsn to parse DSN [\#612](https://github.com/php-enqueue/enqueue-dev/pull/612) ([makasim](https://github.com/makasim))
- \[client\]\[bundle\] Take queue prefix into account while queue binding. [\#611](https://github.com/php-enqueue/enqueue-dev/pull/611) ([makasim](https://github.com/makasim))
- Add support for the 'ciphers' ssl option [\#607](https://github.com/php-enqueue/enqueue-dev/pull/607) ([eperazzo](https://github.com/eperazzo))
- Queue monitoring.  [\#606](https://github.com/php-enqueue/enqueue-dev/pull/606) ([ASKozienko](https://github.com/ASKozienko))
- Fix comment about queue deletion [\#604](https://github.com/php-enqueue/enqueue-dev/pull/604) ([a-ast](https://github.com/a-ast))
- \[docs\] Fixed docs. Removed prefix Psr. [\#603](https://github.com/php-enqueue/enqueue-dev/pull/603) ([yurez](https://github.com/yurez))
- fix wamp [\#597](https://github.com/php-enqueue/enqueue-dev/pull/597) ([ASKozienko](https://github.com/ASKozienko))
- \[doc\]\[skip ci\] Add supporting section [\#595](https://github.com/php-enqueue/enqueue-dev/pull/595) ([makasim](https://github.com/makasim))
- Do not export non source files [\#588](https://github.com/php-enqueue/enqueue-dev/pull/588) ([webmake](https://github.com/webmake))
- Redis New Implementation [\#585](https://github.com/php-enqueue/enqueue-dev/pull/585) ([ASKozienko](https://github.com/ASKozienko))
- Fix Redis Tests [\#582](https://github.com/php-enqueue/enqueue-dev/pull/582) ([ASKozienko](https://github.com/ASKozienko))
- \[dbal\] Introduce redelivery support based on visibility approach.  [\#581](https://github.com/php-enqueue/enqueue-dev/pull/581) ([rosamarsky](https://github.com/rosamarsky))
- fix redis tests [\#578](https://github.com/php-enqueue/enqueue-dev/pull/578) ([ASKozienko](https://github.com/ASKozienko))
- \[client\] Make symfony compiler passes multi client [\#577](https://github.com/php-enqueue/enqueue-dev/pull/577) ([makasim](https://github.com/makasim))
- Removed predis from composer.json [\#576](https://github.com/php-enqueue/enqueue-dev/pull/576) ([rosamarsky](https://github.com/rosamarsky))
- Added index for queue field in the enqueue collection [\#574](https://github.com/php-enqueue/enqueue-dev/pull/574) ([rosamarsky](https://github.com/rosamarsky))
- WAMP [\#573](https://github.com/php-enqueue/enqueue-dev/pull/573) ([ASKozienko](https://github.com/ASKozienko))
- Bundle multi transport configuration [\#572](https://github.com/php-enqueue/enqueue-dev/pull/572) ([makasim](https://github.com/makasim))
- \[client\] Move client config to the factory. [\#571](https://github.com/php-enqueue/enqueue-dev/pull/571) ([makasim](https://github.com/makasim))
- Update quick\_tour.md [\#569](https://github.com/php-enqueue/enqueue-dev/pull/569) ([luceos](https://github.com/luceos))
- \[rdkafka\] Use default queue as router topic [\#567](https://github.com/php-enqueue/enqueue-dev/pull/567) ([rosamarsky](https://github.com/rosamarsky))
- Fixing composer.json to require enqueue/dsn [\#566](https://github.com/php-enqueue/enqueue-dev/pull/566) ([adumas37](https://github.com/adumas37))
- MongoDB Subscription Consumer feature [\#565](https://github.com/php-enqueue/enqueue-dev/pull/565) ([rosamarsky](https://github.com/rosamarsky))
- Remove deprecated testcase implementation [\#564](https://github.com/php-enqueue/enqueue-dev/pull/564) ([samnela](https://github.com/samnela))
- Dbal Subscription Consumer feature [\#563](https://github.com/php-enqueue/enqueue-dev/pull/563) ([rosamarsky](https://github.com/rosamarsky))
- \[client\] Move services definition to ClientFactory. [\#556](https://github.com/php-enqueue/enqueue-dev/pull/556) ([makasim](https://github.com/makasim))
- Fixed exception message in testThrowErrorIfServiceDoesNotImplementProcessorReturnType  [\#559](https://github.com/php-enqueue/enqueue-dev/pull/559) ([rosamarsky](https://github.com/rosamarsky))
- Update supported\_brokers.md [\#558](https://github.com/php-enqueue/enqueue-dev/pull/558) ([edgji](https://github.com/edgji))
- \[consumption\] Logging improvements [\#555](https://github.com/php-enqueue/enqueue-dev/pull/555) ([makasim](https://github.com/makasim))
- \[consumption\] Rework QueueConsumer extension points. [\#554](https://github.com/php-enqueue/enqueue-dev/pull/554) ([makasim](https://github.com/makasim))
- \[STOMP\] make getStomp public [\#552](https://github.com/php-enqueue/enqueue-dev/pull/552) ([versh23](https://github.com/versh23))
- \[consumption\] Add ability to consume from multiple transports. [\#548](https://github.com/php-enqueue/enqueue-dev/pull/548) ([makasim](https://github.com/makasim))
- \[client\] Rename config options. [\#547](https://github.com/php-enqueue/enqueue-dev/pull/547) ([makasim](https://github.com/makasim))
- Remove config parameters [\#545](https://github.com/php-enqueue/enqueue-dev/pull/545) ([makasim](https://github.com/makasim))
- Remove transport factories [\#544](https://github.com/php-enqueue/enqueue-dev/pull/544) ([makasim](https://github.com/makasim))
- Remove psr prefix [\#543](https://github.com/php-enqueue/enqueue-dev/pull/543) ([makasim](https://github.com/makasim))
- \[amqp\] Set delay strategy if rabbitmq scheme extension present. [\#536](https://github.com/php-enqueue/enqueue-dev/pull/536) ([makasim](https://github.com/makasim))
- \[client\] Add type hints to driver interface and its implementations. [\#535](https://github.com/php-enqueue/enqueue-dev/pull/535) ([makasim](https://github.com/makasim))
- \[client\] Introduce routes. Foundation for multi transport support.  [\#534](https://github.com/php-enqueue/enqueue-dev/pull/534) ([makasim](https://github.com/makasim))
- \[gps\] enhance connection configuration. [\#531](https://github.com/php-enqueue/enqueue-dev/pull/531) ([makasim](https://github.com/makasim))
- \[sqs\] Configuration enhancements [\#530](https://github.com/php-enqueue/enqueue-dev/pull/530) ([makasim](https://github.com/makasim))
- \[redis\] Improve redis config, use enqueue/dsn [\#528](https://github.com/php-enqueue/enqueue-dev/pull/528) ([makasim](https://github.com/makasim))
- \[dsn\] Add typed methods for query parameters. [\#527](https://github.com/php-enqueue/enqueue-dev/pull/527) ([makasim](https://github.com/makasim))
- \[redis\] Revert timeout change. [\#526](https://github.com/php-enqueue/enqueue-dev/pull/526) ([makasim](https://github.com/makasim))
- \[Redis\] Add support of secure\TLS connections \(based on PR 515\) [\#524](https://github.com/php-enqueue/enqueue-dev/pull/524) ([makasim](https://github.com/makasim))
- Simplify Enqueue configuration. [\#522](https://github.com/php-enqueue/enqueue-dev/pull/522) ([makasim](https://github.com/makasim))
- \[client\] Add typehints to producer interface, its implementations [\#521](https://github.com/php-enqueue/enqueue-dev/pull/521) ([makasim](https://github.com/makasim))
- \[client\] Improve client extension. [\#517](https://github.com/php-enqueue/enqueue-dev/pull/517) ([makasim](https://github.com/makasim))
- Add declare strict [\#516](https://github.com/php-enqueue/enqueue-dev/pull/516) ([makasim](https://github.com/makasim))
- PHP 7.1+. Queue Interop typed interfaces. [\#512](https://github.com/php-enqueue/enqueue-dev/pull/512) ([makasim](https://github.com/makasim))
- \[Symfony\] default factory should resolve DSN in runtime [\#510](https://github.com/php-enqueue/enqueue-dev/pull/510) ([makasim](https://github.com/makasim))
- Fixed password auth for predis [\#509](https://github.com/php-enqueue/enqueue-dev/pull/509) ([Toflar](https://github.com/Toflar))
- Allow either subscribe or assign in RdKafkaConsumer [\#508](https://github.com/php-enqueue/enqueue-dev/pull/508) ([Engerim](https://github.com/Engerim))
- Remove deprecated in 0.8 code [\#507](https://github.com/php-enqueue/enqueue-dev/pull/507) ([makasim](https://github.com/makasim))
- Run tests on rabbitmq 3.7 [\#506](https://github.com/php-enqueue/enqueue-dev/pull/506) ([makasim](https://github.com/makasim))
- Symfony add default command name [\#505](https://github.com/php-enqueue/enqueue-dev/pull/505) ([makasim](https://github.com/makasim))
- \[Consumption\] Add QueueConsumerInterface, make QueueConsumer final. [\#504](https://github.com/php-enqueue/enqueue-dev/pull/504) ([makasim](https://github.com/makasim))
- Redis subscription consumer [\#503](https://github.com/php-enqueue/enqueue-dev/pull/503) ([makasim](https://github.com/makasim))
- Remove support of old Symfony versions. [\#502](https://github.com/php-enqueue/enqueue-dev/pull/502) ([makasim](https://github.com/makasim))
- \[BC break\]\[dbal\] Convert between Message::$expire and DbalMessage::$timeToLive [\#501](https://github.com/php-enqueue/enqueue-dev/pull/501) ([makasim](https://github.com/makasim))
- \[BC break\]\[dbal\] Change columns type from int to bigint. [\#500](https://github.com/php-enqueue/enqueue-dev/pull/500) ([makasim](https://github.com/makasim))
- \[BC break\]\[dbal\] Fix time conversion in DbalDriver. [\#499](https://github.com/php-enqueue/enqueue-dev/pull/499) ([makasim](https://github.com/makasim))
- \[BC break\]\[dbal\] Add index, fix performance issue.  [\#498](https://github.com/php-enqueue/enqueue-dev/pull/498) ([makasim](https://github.com/makasim))
- \[redis\] Authentication support added  [\#497](https://github.com/php-enqueue/enqueue-dev/pull/497) ([makasim](https://github.com/makasim))
- add subscription consumer specs to amqp pkgs [\#495](https://github.com/php-enqueue/enqueue-dev/pull/495) ([makasim](https://github.com/makasim))
- add contribution to subtree split message [\#494](https://github.com/php-enqueue/enqueue-dev/pull/494) ([makasim](https://github.com/makasim))
- Get rid of path repository [\#493](https://github.com/php-enqueue/enqueue-dev/pull/493) ([makasim](https://github.com/makasim))
- Move subscription related logic to SubscriptionConsumer class. [\#492](https://github.com/php-enqueue/enqueue-dev/pull/492) ([makasim](https://github.com/makasim))
- remove bc layer. [\#489](https://github.com/php-enqueue/enqueue-dev/pull/489) ([makasim](https://github.com/makasim))
- Job Queue: Throw orphan job exception when child job cleanup fails. [\#496](https://github.com/php-enqueue/enqueue-dev/pull/496) ([garrettrayj](https://github.com/garrettrayj))
- \[bundle\] Fix panel rendering when message body is an object [\#442](https://github.com/php-enqueue/enqueue-dev/pull/442) ([thePanz](https://github.com/thePanz))
- \[symfony\] Async commands [\#403](https://github.com/php-enqueue/enqueue-dev/pull/403) ([makasim](https://github.com/makasim))

## [0.8.42](https://github.com/php-enqueue/enqueue-dev/tree/0.8.42) (2018-11-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.41...0.8.42)

**Merged pull requests:**

- Gitattributes backporting [\#654](https://github.com/php-enqueue/enqueue-dev/pull/654) ([webmake](https://github.com/webmake))

## [0.8.41](https://github.com/php-enqueue/enqueue-dev/tree/0.8.41) (2018-11-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.40...0.8.41)

**Merged pull requests:**

- Compatibility with 0.9x [\#615](https://github.com/php-enqueue/enqueue-dev/pull/615) ([ASKozienko](https://github.com/ASKozienko))
- Fix Tests 0.8x [\#609](https://github.com/php-enqueue/enqueue-dev/pull/609) ([ASKozienko](https://github.com/ASKozienko))
- Allow JobStorage to reset the EntityManager [\#586](https://github.com/php-enqueue/enqueue-dev/pull/586) ([damijank](https://github.com/damijank))
- Fix delay not working on SQS [\#584](https://github.com/php-enqueue/enqueue-dev/pull/584) ([mbeccati](https://github.com/mbeccati))

## [0.8.40](https://github.com/php-enqueue/enqueue-dev/tree/0.8.40) (2018-10-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.39...0.8.40)

**Merged pull requests:**

- \[rdkafka\] Backport changes to topic subscription [\#575](https://github.com/php-enqueue/enqueue-dev/pull/575) ([Steveb-p](https://github.com/Steveb-p))

## [0.8.39](https://github.com/php-enqueue/enqueue-dev/tree/0.8.39) (2018-10-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.38...0.8.39)

**Merged pull requests:**

- Merge pull request \#552 from versh23/stomp-public [\#568](https://github.com/php-enqueue/enqueue-dev/pull/568) ([versh23](https://github.com/versh23))

## [0.8.38](https://github.com/php-enqueue/enqueue-dev/tree/0.8.38) (2018-10-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.37...0.8.38)

**Merged pull requests:**

- Fixing kafka default configuration [\#562](https://github.com/php-enqueue/enqueue-dev/pull/562) ([adumas37](https://github.com/adumas37))
- enableSubscriptionConsumer setter [\#541](https://github.com/php-enqueue/enqueue-dev/pull/541) ([ArnaudTarroux](https://github.com/ArnaudTarroux))

## [0.8.37](https://github.com/php-enqueue/enqueue-dev/tree/0.8.37) (2018-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.36...0.8.37)

**Merged pull requests:**

## [0.8.36](https://github.com/php-enqueue/enqueue-dev/tree/0.8.36) (2018-08-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.35...0.8.36)

**Merged pull requests:**

- Remove bool typehint for php \< 7 supports [\#513](https://github.com/php-enqueue/enqueue-dev/pull/513) ([ArnaudTarroux](https://github.com/ArnaudTarroux))

## [0.8.35](https://github.com/php-enqueue/enqueue-dev/tree/0.8.35) (2018-08-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.34...0.8.35)

**Merged pull requests:**

- Improve multi queue consumption. [\#488](https://github.com/php-enqueue/enqueue-dev/pull/488) ([makasim](https://github.com/makasim))

## [0.8.34](https://github.com/php-enqueue/enqueue-dev/tree/0.8.34) (2018-08-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.33...0.8.34)

**Merged pull requests:**

- simple client dsn issue [\#486](https://github.com/php-enqueue/enqueue-dev/pull/486) ([makasim](https://github.com/makasim))
- Update SQS DSN doc sample with mention urlencode [\#484](https://github.com/php-enqueue/enqueue-dev/pull/484) ([dgoujard](https://github.com/dgoujard))
- Prevent SqsProducer from sending messages with empty bodies [\#478](https://github.com/php-enqueue/enqueue-dev/pull/478) ([elazar](https://github.com/elazar))

## [0.8.33](https://github.com/php-enqueue/enqueue-dev/tree/0.8.33) (2018-07-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.32...0.8.33)

**Merged pull requests:**

- Fix call debug method on null [\#480](https://github.com/php-enqueue/enqueue-dev/pull/480) ([makasim](https://github.com/makasim))
- Fix AMQPContext::unsubscribe [\#479](https://github.com/php-enqueue/enqueue-dev/pull/479) ([adrienbrault](https://github.com/adrienbrault))
- Add Localstack Docker container for SQS functional tests [\#473](https://github.com/php-enqueue/enqueue-dev/pull/473) ([elazar](https://github.com/elazar))
- \[consumption\] add process niceness extension [\#467](https://github.com/php-enqueue/enqueue-dev/pull/467) ([ramunasd](https://github.com/ramunasd))

## [0.8.32](https://github.com/php-enqueue/enqueue-dev/tree/0.8.32) (2018-07-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.31...0.8.32)

**Merged pull requests:**

- Update of "back to index" link [\#468](https://github.com/php-enqueue/enqueue-dev/pull/468) ([N-M](https://github.com/N-M))
- PHP\_URL\_SCHEME doesn't support underscores [\#453](https://github.com/php-enqueue/enqueue-dev/pull/453) ([coudenysj](https://github.com/coudenysj))
- Add autoconfigure for services extending PsrProcess interface [\#452](https://github.com/php-enqueue/enqueue-dev/pull/452) ([mnavarrocarter](https://github.com/mnavarrocarter))
- WIP: Add support for using a pre-configured client with the SQS driver [\#444](https://github.com/php-enqueue/enqueue-dev/pull/444) ([elazar](https://github.com/elazar))

## [0.8.31](https://github.com/php-enqueue/enqueue-dev/tree/0.8.31) (2018-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.30...0.8.31)

**Merged pull requests:**

- Allow newer version of bunny [\#446](https://github.com/php-enqueue/enqueue-dev/pull/446) ([enumag](https://github.com/enumag))
- Fix mistype at async\_events docs [\#445](https://github.com/php-enqueue/enqueue-dev/pull/445) ([diimpp](https://github.com/diimpp))
- Improve exception messages for topic-subscribers [\#441](https://github.com/php-enqueue/enqueue-dev/pull/441) ([thePanz](https://github.com/thePanz))

## [0.8.30](https://github.com/php-enqueue/enqueue-dev/tree/0.8.30) (2018-05-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.29...0.8.30)

## [0.8.29](https://github.com/php-enqueue/enqueue-dev/tree/0.8.29) (2018-05-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.28...0.8.29)

**Merged pull requests:**

- \[mongodb\] Parse DSN if array [\#438](https://github.com/php-enqueue/enqueue-dev/pull/438) ([makasim](https://github.com/makasim))
- \[gps\] Add support for google/cloud-pubsub ^1.0 [\#437](https://github.com/php-enqueue/enqueue-dev/pull/437) ([kfb-ts](https://github.com/kfb-ts))
- fix typo in message\_producer.md [\#436](https://github.com/php-enqueue/enqueue-dev/pull/436) ([halidovz](https://github.com/halidovz))

## [0.8.28](https://github.com/php-enqueue/enqueue-dev/tree/0.8.28) (2018-05-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.27...0.8.28)

**Merged pull requests:**

- remove enqueue core dependency [\#434](https://github.com/php-enqueue/enqueue-dev/pull/434) ([ASKozienko](https://github.com/ASKozienko))
- Mongodb transport [\#430](https://github.com/php-enqueue/enqueue-dev/pull/430) ([turboboy88](https://github.com/turboboy88))

## [0.8.27](https://github.com/php-enqueue/enqueue-dev/tree/0.8.27) (2018-05-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.26...0.8.27)

**Merged pull requests:**

- Kafka symfony transport [\#432](https://github.com/php-enqueue/enqueue-dev/pull/432) ([dheineman](https://github.com/dheineman))
- Drop PHP5 support, Drop Symfony 2.X support.  [\#419](https://github.com/php-enqueue/enqueue-dev/pull/419) ([makasim](https://github.com/makasim))

## [0.8.26](https://github.com/php-enqueue/enqueue-dev/tree/0.8.26) (2018-04-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.25...0.8.26)

**Merged pull requests:**

- Allow to enable SSL in StompConnectionFactory [\#427](https://github.com/php-enqueue/enqueue-dev/pull/427) ([arjanvdbos](https://github.com/arjanvdbos))
- Fix namespace in doc [\#426](https://github.com/php-enqueue/enqueue-dev/pull/426) ([Koc](https://github.com/Koc))

## [0.8.25](https://github.com/php-enqueue/enqueue-dev/tree/0.8.25) (2018-04-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.24...0.8.25)

**Merged pull requests:**

- \[skip ci\] Update doc block. return value should be "self" [\#425](https://github.com/php-enqueue/enqueue-dev/pull/425) ([makasim](https://github.com/makasim))
- \[bundle\] Make TraceableProducer service public [\#422](https://github.com/php-enqueue/enqueue-dev/pull/422) ([sbacelic](https://github.com/sbacelic))
- Fix a tiny little typo in documentation [\#416](https://github.com/php-enqueue/enqueue-dev/pull/416) ([bobey](https://github.com/bobey))

## [0.8.24](https://github.com/php-enqueue/enqueue-dev/tree/0.8.24) (2018-03-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.23...0.8.24)

**Merged pull requests:**

- \[bundle\] Don't ping DBAL connection if it wasn't opened [\#414](https://github.com/php-enqueue/enqueue-dev/pull/414) ([ramunasd](https://github.com/ramunasd))
- Fix AMQP\(s\) code in amqp.md [\#413](https://github.com/php-enqueue/enqueue-dev/pull/413) ([xdbas](https://github.com/xdbas))
- Fixed typos [\#412](https://github.com/php-enqueue/enqueue-dev/pull/412) ([pborreli](https://github.com/pborreli))
- Fixed typo [\#411](https://github.com/php-enqueue/enqueue-dev/pull/411) ([pborreli](https://github.com/pborreli))
- Update sqs transport factory with missing endpoint parameter [\#404](https://github.com/php-enqueue/enqueue-dev/pull/404) ([asilgalis](https://github.com/asilgalis))
- \[fs\] Escape delimiter symbols.  [\#402](https://github.com/php-enqueue/enqueue-dev/pull/402) ([makasim](https://github.com/makasim))

## [0.8.23](https://github.com/php-enqueue/enqueue-dev/tree/0.8.23) (2018-03-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.22...0.8.23)

**Merged pull requests:**

- \[doc\]\[magento2\]\[skip ci\] Add docs for Mangeto2 module. [\#401](https://github.com/php-enqueue/enqueue-dev/pull/401) ([makasim](https://github.com/makasim))
- Allow queue interop 1.0 alpha. [\#400](https://github.com/php-enqueue/enqueue-dev/pull/400) ([makasim](https://github.com/makasim))
- Update Travis config to use Symfony 4 release [\#397](https://github.com/php-enqueue/enqueue-dev/pull/397) ([msheakoski](https://github.com/msheakoski))
- Clean up when a job triggers an exception [\#395](https://github.com/php-enqueue/enqueue-dev/pull/395) ([msheakoski](https://github.com/msheakoski))

## [0.8.22](https://github.com/php-enqueue/enqueue-dev/tree/0.8.22) (2018-03-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.21...0.8.22)

**Merged pull requests:**

- \[client\] Simple Client should not depend on amqp-ext. [\#389](https://github.com/php-enqueue/enqueue-dev/pull/389) ([makasim](https://github.com/makasim))
- \[bundle\] fix for "Transport factory with such name already added" [\#388](https://github.com/php-enqueue/enqueue-dev/pull/388) ([makasim](https://github.com/makasim))
- \[bundle\] add producer interface alias. [\#382](https://github.com/php-enqueue/enqueue-dev/pull/382) ([makasim](https://github.com/makasim))

## [0.8.21](https://github.com/php-enqueue/enqueue-dev/tree/0.8.21) (2018-02-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.20...0.8.21)

**Merged pull requests:**

- \[symfony\] Print command name [\#374](https://github.com/php-enqueue/enqueue-dev/pull/374) ([makasim](https://github.com/makasim))

## [0.8.20](https://github.com/php-enqueue/enqueue-dev/tree/0.8.20) (2018-02-15)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.19...0.8.20)

**Merged pull requests:**

- \[Redis\] Add ability to pass Redis instance to connection factory [\#372](https://github.com/php-enqueue/enqueue-dev/pull/372) ([makasim](https://github.com/makasim))

## [0.8.19](https://github.com/php-enqueue/enqueue-dev/tree/0.8.19) (2018-02-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.18...0.8.19)

**Merged pull requests:**

- \[dbal\] Sort priority messages by published at date too.  [\#371](https://github.com/php-enqueue/enqueue-dev/pull/371) ([makasim](https://github.com/makasim))
- Fix typo [\#369](https://github.com/php-enqueue/enqueue-dev/pull/369) ([kubk](https://github.com/kubk))
- \[client\]\[skip ci\] Explain meaning of sendEvent, sendCommand methods. [\#365](https://github.com/php-enqueue/enqueue-dev/pull/365) ([makasim](https://github.com/makasim))
- Modify async\_events.md grammar [\#364](https://github.com/php-enqueue/enqueue-dev/pull/364) ([ddproxy](https://github.com/ddproxy))
- Fix wrong argument type [\#361](https://github.com/php-enqueue/enqueue-dev/pull/361) ([olix21](https://github.com/olix21))

## [0.8.18](https://github.com/php-enqueue/enqueue-dev/tree/0.8.18) (2018-02-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.17...0.8.18)

**Merged pull requests:**

- \[bundle\] DefaultTransportFactory should accept DSN like foo: [\#358](https://github.com/php-enqueue/enqueue-dev/pull/358) ([makasim](https://github.com/makasim))
- Added endpoint configuration and updated the tests [\#353](https://github.com/php-enqueue/enqueue-dev/pull/353) ([gitis](https://github.com/gitis))
- Moved symfony/framework-bundle to require-dev [\#348](https://github.com/php-enqueue/enqueue-dev/pull/348) ([prisis](https://github.com/prisis))
- Gearman PHP 7 support [\#347](https://github.com/php-enqueue/enqueue-dev/pull/347) ([Jawshua](https://github.com/Jawshua))
- \[dbal\] Consumer never fetches messages ordered by published time [\#343](https://github.com/php-enqueue/enqueue-dev/pull/343) ([f7h](https://github.com/f7h))

## [0.8.17](https://github.com/php-enqueue/enqueue-dev/tree/0.8.17) (2018-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.16...0.8.17)

**Merged pull requests:**

- \[consumption\] Prepare QueueConsumer for changes in 0.9 [\#337](https://github.com/php-enqueue/enqueue-dev/pull/337) ([makasim](https://github.com/makasim))
- \[consumption\] Make QueueConsumer final [\#336](https://github.com/php-enqueue/enqueue-dev/pull/336) ([makasim](https://github.com/makasim))
- \[bundle\]\[dx\] Add a message that suggest installing a pkg to use the transport. [\#335](https://github.com/php-enqueue/enqueue-dev/pull/335) ([makasim](https://github.com/makasim))
- \[0.9\]\[BC break\]\[dbal\] Store UUIDs as binary data. Improves performance [\#280](https://github.com/php-enqueue/enqueue-dev/pull/280) ([makasim](https://github.com/makasim))

## [0.8.16](https://github.com/php-enqueue/enqueue-dev/tree/0.8.16) (2018-01-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.15...0.8.16)

**Merged pull requests:**

- \[Sqs\] Allow array-based DSN configuration [\#315](https://github.com/php-enqueue/enqueue-dev/pull/315) ([beryllium](https://github.com/beryllium))

## [0.8.15](https://github.com/php-enqueue/enqueue-dev/tree/0.8.15) (2018-01-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.14...0.8.15)

**Merged pull requests:**

- \[amqp\] fix signal handler if consume called from consume [\#328](https://github.com/php-enqueue/enqueue-dev/pull/328) ([makasim](https://github.com/makasim))
- Update config\_reference.md [\#326](https://github.com/php-enqueue/enqueue-dev/pull/326) ([errogaht](https://github.com/errogaht))
- Update message\_producer.md [\#325](https://github.com/php-enqueue/enqueue-dev/pull/325) ([errogaht](https://github.com/errogaht))
- Update consumption\_extension.md [\#324](https://github.com/php-enqueue/enqueue-dev/pull/324) ([errogaht](https://github.com/errogaht))
- \[consumption\] Correct message in LoggerExtension [\#322](https://github.com/php-enqueue/enqueue-dev/pull/322) ([makasim](https://github.com/makasim))

## [0.8.14](https://github.com/php-enqueue/enqueue-dev/tree/0.8.14) (2018-01-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.13...0.8.14)

## [0.8.13](https://github.com/php-enqueue/enqueue-dev/tree/0.8.13) (2018-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.12...0.8.13)

**Merged pull requests:**

- \[amqp\] Fix socket and signal issue. [\#317](https://github.com/php-enqueue/enqueue-dev/pull/317) ([makasim](https://github.com/makasim))
- \[kafka\] add ability to set offset. [\#314](https://github.com/php-enqueue/enqueue-dev/pull/314) ([makasim](https://github.com/makasim))

## [0.8.12](https://github.com/php-enqueue/enqueue-dev/tree/0.8.12) (2018-01-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.11...0.8.12)

**Merged pull requests:**

- \[rdkafka\] Don't do unnecessary subscribe\unsubscribe on every receive call [\#313](https://github.com/php-enqueue/enqueue-dev/pull/313) ([makasim](https://github.com/makasim))
- \[consumption\] Fix signal handling when AMQP is used. [\#310](https://github.com/php-enqueue/enqueue-dev/pull/310) ([makasim](https://github.com/makasim))
- Using Laravel helper to resolve filepath [\#302](https://github.com/php-enqueue/enqueue-dev/pull/302) ([robinvdvleuten](https://github.com/robinvdvleuten))
- Changed larvel to laravel [\#301](https://github.com/php-enqueue/enqueue-dev/pull/301) ([robinvdvleuten](https://github.com/robinvdvleuten))
- Check if logger exists [\#299](https://github.com/php-enqueue/enqueue-dev/pull/299) ([pascaldevink](https://github.com/pascaldevink))
- Fix reversed logic for native UUID detection [\#297](https://github.com/php-enqueue/enqueue-dev/pull/297) ([msheakoski](https://github.com/msheakoski))
- Job queue create tables [\#293](https://github.com/php-enqueue/enqueue-dev/pull/293) ([makasim](https://github.com/makasim))

## [0.8.11](https://github.com/php-enqueue/enqueue-dev/tree/0.8.11) (2017-12-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.10...0.8.11)

**Merged pull requests:**

- \[job-queue\] Change typehint, allow not only Closure but other callabl… [\#292](https://github.com/php-enqueue/enqueue-dev/pull/292) ([makasim](https://github.com/makasim))
- \[dbal\] Fix message re-queuing. Reuse producer for it. [\#291](https://github.com/php-enqueue/enqueue-dev/pull/291) ([makasim](https://github.com/makasim))
- \[consumption\] Add ability to overwrite logger. [\#289](https://github.com/php-enqueue/enqueue-dev/pull/289) ([makasim](https://github.com/makasim))
- \[doc\] yii2-queue amqp driver [\#282](https://github.com/php-enqueue/enqueue-dev/pull/282) ([makasim](https://github.com/makasim))

## [0.8.10](https://github.com/php-enqueue/enqueue-dev/tree/0.8.10) (2017-12-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.9...0.8.10)

**Merged pull requests:**

- \[doc\]\[skip ci\] add doc for client on send extensions. [\#285](https://github.com/php-enqueue/enqueue-dev/pull/285) ([makasim](https://github.com/makasim))
- \[doc\]\[skip ci\] Add processor examples, notes on exception and more. [\#283](https://github.com/php-enqueue/enqueue-dev/pull/283) ([makasim](https://github.com/makasim))
- \[travis\] add PHP 7.2 to build matrix. [\#281](https://github.com/php-enqueue/enqueue-dev/pull/281) ([makasim](https://github.com/makasim))

## [0.8.9](https://github.com/php-enqueue/enqueue-dev/tree/0.8.9) (2017-11-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.8...0.8.9)

**Merged pull requests:**

- \[docker\] Incorporate amqp ext compilation to docker build process. [\#275](https://github.com/php-enqueue/enqueue-dev/pull/275) ([makasim](https://github.com/makasim))
- \[bundle\] Apparently the use case tests have never worked properly. [\#273](https://github.com/php-enqueue/enqueue-dev/pull/273) ([makasim](https://github.com/makasim))
- \[fs\] Copy past Symfony's LockHandler \(not awailable in Sf4\). [\#272](https://github.com/php-enqueue/enqueue-dev/pull/272) ([makasim](https://github.com/makasim))
- Add Symfony4 support [\#269](https://github.com/php-enqueue/enqueue-dev/pull/269) ([makasim](https://github.com/makasim))
- \[bundle\] use enqueue logo in profiler panel. [\#268](https://github.com/php-enqueue/enqueue-dev/pull/268) ([makasim](https://github.com/makasim))
- \[rdkafka\] do not pass config if it was not set explisitly. [\#263](https://github.com/php-enqueue/enqueue-dev/pull/263) ([makasim](https://github.com/makasim))

## [0.8.8](https://github.com/php-enqueue/enqueue-dev/tree/0.8.8) (2017-11-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.7...0.8.8)

**Merged pull requests:**

- \[Redis\] add dsn support for symfony bundle. [\#266](https://github.com/php-enqueue/enqueue-dev/pull/266) ([wilson-ng](https://github.com/wilson-ng))
- \[consumption\]\[amqp\] onIdle is never called. [\#265](https://github.com/php-enqueue/enqueue-dev/pull/265) ([makasim](https://github.com/makasim))
- \[consumption\] fix context is missing message on exception. [\#264](https://github.com/php-enqueue/enqueue-dev/pull/264) ([makasim](https://github.com/makasim))

## [0.8.7](https://github.com/php-enqueue/enqueue-dev/tree/0.8.7) (2017-11-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.6...0.8.7)

**Merged pull requests:**

- Changes SetRouterPropertiesExtension to use the driver to generate the queue name [\#262](https://github.com/php-enqueue/enqueue-dev/pull/262) ([iainmckay](https://github.com/iainmckay))
- \[Redis\] add custom database index [\#258](https://github.com/php-enqueue/enqueue-dev/pull/258) ([IndraGunawan](https://github.com/IndraGunawan))

## [0.8.6](https://github.com/php-enqueue/enqueue-dev/tree/0.8.6) (2017-11-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.5...0.8.6)

**Merged pull requests:**

- \[RdKafka\] Enable serializers to serialize message keys [\#254](https://github.com/php-enqueue/enqueue-dev/pull/254) ([tPl0ch](https://github.com/tPl0ch))

## [0.8.5](https://github.com/php-enqueue/enqueue-dev/tree/0.8.5) (2017-11-02)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.4...0.8.5)

**Merged pull requests:**

- Amqp add ssl pass phrase option [\#249](https://github.com/php-enqueue/enqueue-dev/pull/249) ([makasim](https://github.com/makasim))
- \[amqp-lib\] Ignore empty ssl options. [\#248](https://github.com/php-enqueue/enqueue-dev/pull/248) ([makasim](https://github.com/makasim))

## [0.8.4](https://github.com/php-enqueue/enqueue-dev/tree/0.8.4) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.3...0.8.4)

## [0.8.3](https://github.com/php-enqueue/enqueue-dev/tree/0.8.3) (2017-11-01)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.2...0.8.3)

**Merged pull requests:**

- \[bundle\] streamline profiler view when no messages were sent [\#247](https://github.com/php-enqueue/enqueue-dev/pull/247) ([dkarlovi](https://github.com/dkarlovi))
- \[bundle\] Renamed exposed services' name to classes' FQCN [\#242](https://github.com/php-enqueue/enqueue-dev/pull/242) ([Lctrs](https://github.com/Lctrs))

## [0.8.2](https://github.com/php-enqueue/enqueue-dev/tree/0.8.2) (2017-10-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.1...0.8.2)

**Merged pull requests:**

- \[amqp\] Add AMQP secure \(SSL\) connections support [\#246](https://github.com/php-enqueue/enqueue-dev/pull/246) ([makasim](https://github.com/makasim))

## [0.8.1](https://github.com/php-enqueue/enqueue-dev/tree/0.8.1) (2017-10-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.8.0...0.8.1)

**Merged pull requests:**

- Only add Ampq transport factories when packages are found [\#241](https://github.com/php-enqueue/enqueue-dev/pull/241) ([jverdeyen](https://github.com/jverdeyen))
- GPS Integration [\#239](https://github.com/php-enqueue/enqueue-dev/pull/239) ([ASKozienko](https://github.com/ASKozienko))

## [0.8.0](https://github.com/php-enqueue/enqueue-dev/tree/0.8.0) (2017-10-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.19...0.8.0)

**Merged pull requests:**

- 0.8v goes stable. [\#238](https://github.com/php-enqueue/enqueue-dev/pull/238) ([makasim](https://github.com/makasim))
- \[travis\] allow kafka tests to fail. [\#237](https://github.com/php-enqueue/enqueue-dev/pull/237) ([makasim](https://github.com/makasim))
- \[consumption\]\[amqp\] move beforeReceive call at the end of the cycle f… [\#234](https://github.com/php-enqueue/enqueue-dev/pull/234) ([makasim](https://github.com/makasim))
- \[amqp\] One single transport factory for all supported amqp implementa… [\#233](https://github.com/php-enqueue/enqueue-dev/pull/233) ([makasim](https://github.com/makasim))
- Missing client configuration in the documentation [\#231](https://github.com/php-enqueue/enqueue-dev/pull/231) ([lsv](https://github.com/lsv))
- Added MIT license badge [\#230](https://github.com/php-enqueue/enqueue-dev/pull/230) ([tarlepp](https://github.com/tarlepp))
- \[BC break\]\[amqp\] Introduce connection config. Make it same across all transports. [\#228](https://github.com/php-enqueue/enqueue-dev/pull/228) ([makasim](https://github.com/makasim))

## [0.7.19](https://github.com/php-enqueue/enqueue-dev/tree/0.7.19) (2017-10-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.18...0.7.19)

**Merged pull requests:**

- Fix typo [\#227](https://github.com/php-enqueue/enqueue-dev/pull/227) ([f3ath](https://github.com/f3ath))
- Amqp basic consume fixes [\#223](https://github.com/php-enqueue/enqueue-dev/pull/223) ([makasim](https://github.com/makasim))
- Adds to small extension points to JobProcessor [\#222](https://github.com/php-enqueue/enqueue-dev/pull/222) ([iainmckay](https://github.com/iainmckay))
- \[BC break\]\[amqp\] Use same qos options across all all AMQP transports [\#221](https://github.com/php-enqueue/enqueue-dev/pull/221) ([makasim](https://github.com/makasim))
- \[BC break\] Amqp add basic consume support [\#217](https://github.com/php-enqueue/enqueue-dev/pull/217) ([makasim](https://github.com/makasim))

## [0.7.18](https://github.com/php-enqueue/enqueue-dev/tree/0.7.18) (2017-10-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.17...0.7.18)

**Merged pull requests:**

- \[client\] Add --skip option to consume command. [\#218](https://github.com/php-enqueue/enqueue-dev/pull/218) ([makasim](https://github.com/makasim))

## [0.7.17](https://github.com/php-enqueue/enqueue-dev/tree/0.7.17) (2017-10-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.16...0.7.17)

**Merged pull requests:**

- Fs do not throw error on user deprecate [\#214](https://github.com/php-enqueue/enqueue-dev/pull/214) ([makasim](https://github.com/makasim))
- \[bundle\]\[profiler\] Fix array to string conversion notice. [\#212](https://github.com/php-enqueue/enqueue-dev/pull/212) ([makasim](https://github.com/makasim))

## [0.7.16](https://github.com/php-enqueue/enqueue-dev/tree/0.7.16) (2017-09-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.15...0.7.16)

**Merged pull requests:**

- Fixes the notation for Twig template names in the data collector [\#207](https://github.com/php-enqueue/enqueue-dev/pull/207) ([Lctrs](https://github.com/Lctrs))
- \[BC Break\]\[dsn\] replace xxx:// to xxx: [\#205](https://github.com/php-enqueue/enqueue-dev/pull/205) ([makasim](https://github.com/makasim))

## [0.7.15](https://github.com/php-enqueue/enqueue-dev/tree/0.7.15) (2017-09-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.14...0.7.15)

**Merged pull requests:**

- \[redis\] add dsn support for redis transport. [\#204](https://github.com/php-enqueue/enqueue-dev/pull/204) ([makasim](https://github.com/makasim))
- \[fs\] fix bugs introduced in \#181. [\#203](https://github.com/php-enqueue/enqueue-dev/pull/203) ([makasim](https://github.com/makasim))
- \[dbal\]\[bc break\] Performance improvements and new features. [\#199](https://github.com/php-enqueue/enqueue-dev/pull/199) ([makasim](https://github.com/makasim))

## [0.7.14](https://github.com/php-enqueue/enqueue-dev/tree/0.7.14) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.13...0.7.14)

## [0.7.13](https://github.com/php-enqueue/enqueue-dev/tree/0.7.13) (2017-09-13)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.12...0.7.13)

**Merged pull requests:**

- \[dbal\] add priority support on transport level. [\#198](https://github.com/php-enqueue/enqueue-dev/pull/198) ([makasim](https://github.com/makasim))
- \[bundle\] add tests for the case where topic subscriber does not def p… [\#197](https://github.com/php-enqueue/enqueue-dev/pull/197) ([makasim](https://github.com/makasim))
- Fixed losing message priority for dbal driver [\#195](https://github.com/php-enqueue/enqueue-dev/pull/195) ([vtsykun](https://github.com/vtsykun))

## [0.7.12](https://github.com/php-enqueue/enqueue-dev/tree/0.7.12) (2017-09-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.11...0.7.12)

**Merged pull requests:**

- fixed NS [\#194](https://github.com/php-enqueue/enqueue-dev/pull/194) ([chdeliens](https://github.com/chdeliens))

## [0.7.11](https://github.com/php-enqueue/enqueue-dev/tree/0.7.11) (2017-09-11)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.10...0.7.11)

**Merged pull requests:**

- Queue Consumer Options [\#193](https://github.com/php-enqueue/enqueue-dev/pull/193) ([ASKozienko](https://github.com/ASKozienko))
- \[FS\] Polling Interval [\#192](https://github.com/php-enqueue/enqueue-dev/pull/192) ([ASKozienko](https://github.com/ASKozienko))
- \[Symfony\] added toolbar info in profiler [\#190](https://github.com/php-enqueue/enqueue-dev/pull/190) ([Miliooo](https://github.com/Miliooo))
- docs cli\_commands.md fix [\#189](https://github.com/php-enqueue/enqueue-dev/pull/189) ([Miliooo](https://github.com/Miliooo))

## [0.7.10](https://github.com/php-enqueue/enqueue-dev/tree/0.7.10) (2017-08-31)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.9...0.7.10)

**Merged pull requests:**

- \[rdkafka\] Add abilito change the way a message is serialized. [\#188](https://github.com/php-enqueue/enqueue-dev/pull/188) ([makasim](https://github.com/makasim))

## [0.7.9](https://github.com/php-enqueue/enqueue-dev/tree/0.7.9) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.8...0.7.9)

**Merged pull requests:**

- \[client\] DelayRedeliveredMessageExtension. Add reject reason. [\#185](https://github.com/php-enqueue/enqueue-dev/pull/185) ([makasim](https://github.com/makasim))
- \[phpstan\] update to 0.8 version [\#184](https://github.com/php-enqueue/enqueue-dev/pull/184) ([makasim](https://github.com/makasim))

## [0.7.8](https://github.com/php-enqueue/enqueue-dev/tree/0.7.8) (2017-08-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.7...0.7.8)

**Merged pull requests:**

- \[consumption\] Do not close context. [\#183](https://github.com/php-enqueue/enqueue-dev/pull/183) ([makasim](https://github.com/makasim))
- \[bundle\] do not use client's related stuff if it is disabled [\#182](https://github.com/php-enqueue/enqueue-dev/pull/182) ([makasim](https://github.com/makasim))
- \[fs\] fix bug that happens with specific message length. [\#181](https://github.com/php-enqueue/enqueue-dev/pull/181) ([makasim](https://github.com/makasim))
- \[sqs\] Skip tests if no amazon credentinals present. [\#180](https://github.com/php-enqueue/enqueue-dev/pull/180) ([makasim](https://github.com/makasim))
- Fix typo in configuration parameter [\#178](https://github.com/php-enqueue/enqueue-dev/pull/178) ([akucherenko](https://github.com/akucherenko))
- Google Pub/Sub [\#167](https://github.com/php-enqueue/enqueue-dev/pull/167) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.7](https://github.com/php-enqueue/enqueue-dev/tree/0.7.7) (2017-08-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.6...0.7.7)

**Merged pull requests:**

- Use Query Builder for better support across platforms. [\#176](https://github.com/php-enqueue/enqueue-dev/pull/176) ([jenkoian](https://github.com/jenkoian))
- fix pheanstalk redelivered, receive [\#173](https://github.com/php-enqueue/enqueue-dev/pull/173) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.6](https://github.com/php-enqueue/enqueue-dev/tree/0.7.6) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.5...0.7.6)

## [0.7.5](https://github.com/php-enqueue/enqueue-dev/tree/0.7.5) (2017-08-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.4...0.7.5)

**Merged pull requests:**

- Bundle disable async events by default [\#169](https://github.com/php-enqueue/enqueue-dev/pull/169) ([makasim](https://github.com/makasim))
- Delay Strategy Configuration [\#162](https://github.com/php-enqueue/enqueue-dev/pull/162) ([ASKozienko](https://github.com/ASKozienko))

## [0.7.4](https://github.com/php-enqueue/enqueue-dev/tree/0.7.4) (2017-08-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.3...0.7.4)

## [0.7.3](https://github.com/php-enqueue/enqueue-dev/tree/0.7.3) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.2...0.7.3)

## [0.7.2](https://github.com/php-enqueue/enqueue-dev/tree/0.7.2) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.1...0.7.2)

**Merged pull requests:**

- \[consumption\] adjust receive and idle timeouts [\#165](https://github.com/php-enqueue/enqueue-dev/pull/165) ([makasim](https://github.com/makasim))
- Remove maxDepth option on profiler dump. [\#164](https://github.com/php-enqueue/enqueue-dev/pull/164) ([jenkoian](https://github.com/jenkoian))

## [0.7.1](https://github.com/php-enqueue/enqueue-dev/tree/0.7.1) (2017-08-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.7.0...0.7.1)

**Merged pull requests:**

- Client fix command routing [\#163](https://github.com/php-enqueue/enqueue-dev/pull/163) ([makasim](https://github.com/makasim))

## [0.7.0](https://github.com/php-enqueue/enqueue-dev/tree/0.7.0) (2017-08-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.2...0.7.0)

**Merged pull requests:**

- continue if exclusive is set to false [\#156](https://github.com/php-enqueue/enqueue-dev/pull/156) ([toooni](https://github.com/toooni))
- \[doc\] add elastica populate bundle [\#155](https://github.com/php-enqueue/enqueue-dev/pull/155) ([makasim](https://github.com/makasim))
- \[producer\] do not throw exception if feature not implemented and null… [\#154](https://github.com/php-enqueue/enqueue-dev/pull/154) ([makasim](https://github.com/makasim))
- Amqp bunny [\#153](https://github.com/php-enqueue/enqueue-dev/pull/153) ([makasim](https://github.com/makasim))
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

**Merged pull requests:**

- Laravel queue package [\#137](https://github.com/php-enqueue/enqueue-dev/pull/137) ([makasim](https://github.com/makasim))
- Add AmqpLib support [\#136](https://github.com/php-enqueue/enqueue-dev/pull/136) ([fibula](https://github.com/fibula))

## [0.6.1](https://github.com/php-enqueue/enqueue-dev/tree/0.6.1) (2017-07-17)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.6.0...0.6.1)

**Merged pull requests:**

- RdKafka Transport [\#134](https://github.com/php-enqueue/enqueue-dev/pull/134) ([ASKozienko](https://github.com/ASKozienko))

## [0.6.0](https://github.com/php-enqueue/enqueue-dev/tree/0.6.0) (2017-07-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.3...0.6.0)

**Merged pull requests:**

- Remove previously deprecated code. [\#131](https://github.com/php-enqueue/enqueue-dev/pull/131) ([makasim](https://github.com/makasim))
- Migrate to queue interop [\#130](https://github.com/php-enqueue/enqueue-dev/pull/130) ([makasim](https://github.com/makasim))

## [0.5.3](https://github.com/php-enqueue/enqueue-dev/tree/0.5.3) (2017-07-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.2...0.5.3)

**Merged pull requests:**

- \[bundle\] Extend EventDispatcher instead of container aware one. [\#129](https://github.com/php-enqueue/enqueue-dev/pull/129) ([makasim](https://github.com/makasim))

## [0.5.2](https://github.com/php-enqueue/enqueue-dev/tree/0.5.2) (2017-07-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.1...0.5.2)

**Merged pull requests:**

- \[client\] Send exclusive commands to their queues directly, by passing… [\#127](https://github.com/php-enqueue/enqueue-dev/pull/127) ([makasim](https://github.com/makasim))
- \[symfony\] Extract DriverFactoryInterface from TransportFactoryInterface. [\#126](https://github.com/php-enqueue/enqueue-dev/pull/126) ([makasim](https://github.com/makasim))

## [0.5.1](https://github.com/php-enqueue/enqueue-dev/tree/0.5.1) (2017-06-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.5.0...0.5.1)

**Merged pull requests:**

- Add Gearman transport. [\#125](https://github.com/php-enqueue/enqueue-dev/pull/125) ([makasim](https://github.com/makasim))

## [0.5.0](https://github.com/php-enqueue/enqueue-dev/tree/0.5.0) (2017-06-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.20...0.5.0)

**Merged pull requests:**

- \[client\] Merge experimental ProducerV2 methods to Producer interface.  [\#124](https://github.com/php-enqueue/enqueue-dev/pull/124) ([makasim](https://github.com/makasim))
- \[WIP\]\[beanstalk\] Add transport for beanstalkd [\#123](https://github.com/php-enqueue/enqueue-dev/pull/123) ([makasim](https://github.com/makasim))
- fix dbal polling interval configuration option [\#122](https://github.com/php-enqueue/enqueue-dev/pull/122) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.20](https://github.com/php-enqueue/enqueue-dev/tree/0.4.20) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.19...0.4.20)

## [0.4.19](https://github.com/php-enqueue/enqueue-dev/tree/0.4.19) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.18...0.4.19)

## [0.4.18](https://github.com/php-enqueue/enqueue-dev/tree/0.4.18) (2017-06-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.17...0.4.18)

**Merged pull requests:**

- \[client\] Add ability to define a command as exclusive [\#120](https://github.com/php-enqueue/enqueue-dev/pull/120) ([makasim](https://github.com/makasim))

## [0.4.17](https://github.com/php-enqueue/enqueue-dev/tree/0.4.17) (2017-06-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.16...0.4.17)

**Merged pull requests:**

- \[simple-client\] Allow processor instance bind. [\#119](https://github.com/php-enqueue/enqueue-dev/pull/119) ([makasim](https://github.com/makasim))
- \[amqp\] Add 'receive\_method' to amqp transport factory. [\#118](https://github.com/php-enqueue/enqueue-dev/pull/118) ([makasim](https://github.com/makasim))
- \[amqp\] Fixes high CPU consumption when basic get is used [\#117](https://github.com/php-enqueue/enqueue-dev/pull/117) ([makasim](https://github.com/makasim))

## [0.4.16](https://github.com/php-enqueue/enqueue-dev/tree/0.4.16) (2017-06-16)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.15...0.4.16)

**Merged pull requests:**

- ProducerV2 For SimpleClient [\#115](https://github.com/php-enqueue/enqueue-dev/pull/115) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.15](https://github.com/php-enqueue/enqueue-dev/tree/0.4.15) (2017-06-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.14...0.4.15)

**Merged pull requests:**

- RPC Deletes Reply Queue After Receive Message [\#114](https://github.com/php-enqueue/enqueue-dev/pull/114) ([ASKozienko](https://github.com/ASKozienko))

## [0.4.14](https://github.com/php-enqueue/enqueue-dev/tree/0.4.14) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.13...0.4.14)

**Merged pull requests:**

- \[RFC\]\[client\] Add ability to send events or commands. [\#113](https://github.com/php-enqueue/enqueue-dev/pull/113) ([makasim](https://github.com/makasim))

## [0.4.13](https://github.com/php-enqueue/enqueue-dev/tree/0.4.13) (2017-06-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.12...0.4.13)

**Merged pull requests:**

- \[amqp\] Add ability to choose what receive method to use: basic\_get or basic\_consume. [\#112](https://github.com/php-enqueue/enqueue-dev/pull/112) ([makasim](https://github.com/makasim))

## [0.4.12](https://github.com/php-enqueue/enqueue-dev/tree/0.4.12) (2017-06-08)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.11...0.4.12)

**Merged pull requests:**

- \[amqp\]\[hotfix\] Switch to AMQP' basic.get till the issue with basic.consume is solved. [\#111](https://github.com/php-enqueue/enqueue-dev/pull/111) ([makasim](https://github.com/makasim))
- \[amqp\] Add pre\_fetch\_count, pre\_fetch\_size options. [\#108](https://github.com/php-enqueue/enqueue-dev/pull/108) ([makasim](https://github.com/makasim))

## [0.4.11](https://github.com/php-enqueue/enqueue-dev/tree/0.4.11) (2017-05-30)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.10...0.4.11)

**Merged pull requests:**

- \[bundle\] Fix "Incompatible use of dynamic environment variables "ENQUEUE\_DSN" found in parameters." [\#107](https://github.com/php-enqueue/enqueue-dev/pull/107) ([makasim](https://github.com/makasim))

## [0.4.10](https://github.com/php-enqueue/enqueue-dev/tree/0.4.10) (2017-05-26)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.9...0.4.10)

**Merged pull requests:**

- \[dbal\] Add DSN support. [\#104](https://github.com/php-enqueue/enqueue-dev/pull/104) ([makasim](https://github.com/makasim))
- Calling AmqpContext::declareQueue\(\) now returns an integer holding the queue message count [\#66](https://github.com/php-enqueue/enqueue-dev/pull/66) ([J7mbo](https://github.com/J7mbo))

## [0.4.9](https://github.com/php-enqueue/enqueue-dev/tree/0.4.9) (2017-05-25)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.8...0.4.9)

**Merged pull requests:**

- \[transport\] Fs transport dsn must contain one extra "/" [\#103](https://github.com/php-enqueue/enqueue-dev/pull/103) ([makasim](https://github.com/makasim))
- Add message spec test case [\#102](https://github.com/php-enqueue/enqueue-dev/pull/102) ([makasim](https://github.com/makasim))

## [0.4.8](https://github.com/php-enqueue/enqueue-dev/tree/0.4.8) (2017-05-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.6...0.4.8)

**Merged pull requests:**

- \[client\] Fixes edge cases in client's routing logic. [\#101](https://github.com/php-enqueue/enqueue-dev/pull/101) ([makasim](https://github.com/makasim))
- \[bundle\] Auto register reply extension. [\#100](https://github.com/php-enqueue/enqueue-dev/pull/100) ([makasim](https://github.com/makasim))
- Do pkg release if there are changes in it. [\#98](https://github.com/php-enqueue/enqueue-dev/pull/98) ([makasim](https://github.com/makasim))

## [0.4.6](https://github.com/php-enqueue/enqueue-dev/tree/0.4.6) (2017-05-23)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.5...0.4.6)

## [0.4.5](https://github.com/php-enqueue/enqueue-dev/tree/0.4.5) (2017-05-22)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.4...0.4.5)

**Merged pull requests:**

- Symfony. Async event subscriber. [\#95](https://github.com/php-enqueue/enqueue-dev/pull/95) ([makasim](https://github.com/makasim))

## [0.4.4](https://github.com/php-enqueue/enqueue-dev/tree/0.4.4) (2017-05-20)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.3...0.4.4)

**Merged pull requests:**

- Symfony. Async event dispatching  [\#86](https://github.com/php-enqueue/enqueue-dev/pull/86) ([makasim](https://github.com/makasim))

## [0.4.3](https://github.com/php-enqueue/enqueue-dev/tree/0.4.3) (2017-05-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.2...0.4.3)

**Merged pull requests:**

- \[client\] SpoolProducer [\#93](https://github.com/php-enqueue/enqueue-dev/pull/93) ([makasim](https://github.com/makasim))
- Add some handy functions. Improve READMEs [\#92](https://github.com/php-enqueue/enqueue-dev/pull/92) ([makasim](https://github.com/makasim))
- Run phpstan and php-cs-fixer on travis  [\#85](https://github.com/php-enqueue/enqueue-dev/pull/85) ([makasim](https://github.com/makasim))

## [0.4.2](https://github.com/php-enqueue/enqueue-dev/tree/0.4.2) (2017-05-15)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.1...0.4.2)

**Merged pull requests:**

- Add dsn\_to\_connection\_factory and dsn\_to\_context functions. [\#84](https://github.com/php-enqueue/enqueue-dev/pull/84) ([makasim](https://github.com/makasim))
- Add ability to set transport DSN directly to default transport factory. [\#81](https://github.com/php-enqueue/enqueue-dev/pull/81) ([makasim](https://github.com/makasim))
- \[bundle\] Set null transport as default. Prevent errors on bundle install. [\#77](https://github.com/php-enqueue/enqueue-dev/pull/77) ([makasim](https://github.com/makasim))

## [0.4.1](https://github.com/php-enqueue/enqueue-dev/tree/0.4.1) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.4.0...0.4.1)

## [0.4.0](https://github.com/php-enqueue/enqueue-dev/tree/0.4.0) (2017-05-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.8...0.4.0)

**Merged pull requests:**

- \[fs\] add DSN support [\#82](https://github.com/php-enqueue/enqueue-dev/pull/82) ([makasim](https://github.com/makasim))
- \[amqp\] Configure by string DSN. [\#80](https://github.com/php-enqueue/enqueue-dev/pull/80) ([makasim](https://github.com/makasim))
- \[fs\] Filesystem transport must create a storage dir if it does not exists. [\#78](https://github.com/php-enqueue/enqueue-dev/pull/78) ([makasim](https://github.com/makasim))
- \[magento\] Add basic docs for enqueue magento extension. [\#76](https://github.com/php-enqueue/enqueue-dev/pull/76) ([makasim](https://github.com/makasim))

## [0.3.8](https://github.com/php-enqueue/enqueue-dev/tree/0.3.8) (2017-05-10)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.7...0.3.8)

**Merged pull requests:**

- Multi Transport Simple Client [\#75](https://github.com/php-enqueue/enqueue-dev/pull/75) ([ASKozienko](https://github.com/ASKozienko))
- Client Extensions [\#72](https://github.com/php-enqueue/enqueue-dev/pull/72) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.7](https://github.com/php-enqueue/enqueue-dev/tree/0.3.7) (2017-05-04)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.6...0.3.7)

**Merged pull requests:**

- JobQueue/Job shouldn't be required when Doctrine schema update [\#71](https://github.com/php-enqueue/enqueue-dev/pull/71) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.6](https://github.com/php-enqueue/enqueue-dev/tree/0.3.6) (2017-04-28)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.5...0.3.6)

**Merged pull requests:**

- Amazon SQS Transport [\#60](https://github.com/php-enqueue/enqueue-dev/pull/60) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.5](https://github.com/php-enqueue/enqueue-dev/tree/0.3.5) (2017-04-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.4...0.3.5)

**Merged pull requests:**

- \[consumption\] Add support of QueueSubscriberInterface to transport consume command. [\#63](https://github.com/php-enqueue/enqueue-dev/pull/63) ([makasim](https://github.com/makasim))
- \[client\] Add ability to hardcode queue name. It is used as is and not adjusted or modified in any way [\#61](https://github.com/php-enqueue/enqueue-dev/pull/61) ([makasim](https://github.com/makasim))

## [0.3.4](https://github.com/php-enqueue/enqueue-dev/tree/0.3.4) (2017-04-24)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.3...0.3.4)

**Merged pull requests:**

- DBAL Transport [\#54](https://github.com/php-enqueue/enqueue-dev/pull/54) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.3](https://github.com/php-enqueue/enqueue-dev/tree/0.3.3) (2017-04-21)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.2...0.3.3)

**Merged pull requests:**

- \[client\] Redis driver [\#59](https://github.com/php-enqueue/enqueue-dev/pull/59) ([makasim](https://github.com/makasim))
- Redis transport. [\#55](https://github.com/php-enqueue/enqueue-dev/pull/55) ([makasim](https://github.com/makasim))

## [0.3.2](https://github.com/php-enqueue/enqueue-dev/tree/0.3.2) (2017-04-19)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.1...0.3.2)

**Merged pull requests:**

- share simple client context [\#52](https://github.com/php-enqueue/enqueue-dev/pull/52) ([ASKozienko](https://github.com/ASKozienko))

## [0.3.1](https://github.com/php-enqueue/enqueue-dev/tree/0.3.1) (2017-04-12)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.3.0...0.3.1)

**Merged pull requests:**

- \[client\] Add RpcClient on client level. [\#50](https://github.com/php-enqueue/enqueue-dev/pull/50) ([makasim](https://github.com/makasim))

## [0.3.0](https://github.com/php-enqueue/enqueue-dev/tree/0.3.0) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.12...0.3.0)

**Merged pull requests:**

- Remove deprecated stuff [\#48](https://github.com/php-enqueue/enqueue-dev/pull/48) ([makasim](https://github.com/makasim))

## [0.2.12](https://github.com/php-enqueue/enqueue-dev/tree/0.2.12) (2017-04-07)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.11...0.2.12)

**Merged pull requests:**

- \[client\] Rename MessageProducer classes to Producer [\#47](https://github.com/php-enqueue/enqueue-dev/pull/47) ([makasim](https://github.com/makasim))
- \[consumption\] Add onResult extension point. [\#46](https://github.com/php-enqueue/enqueue-dev/pull/46) ([makasim](https://github.com/makasim))
- \[transport\] Add Psr prefix to transport interfaces. Deprecates old ones. [\#45](https://github.com/php-enqueue/enqueue-dev/pull/45) ([makasim](https://github.com/makasim))

## [0.2.11](https://github.com/php-enqueue/enqueue-dev/tree/0.2.11) (2017-04-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.10...0.2.11)

**Merged pull requests:**

- \[client\] Add ability to define scope of send message. [\#40](https://github.com/php-enqueue/enqueue-dev/pull/40) ([makasim](https://github.com/makasim))

## [0.2.10](https://github.com/php-enqueue/enqueue-dev/tree/0.2.10) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.9...0.2.10)

## [0.2.9](https://github.com/php-enqueue/enqueue-dev/tree/0.2.9) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.8...0.2.9)

**Merged pull requests:**

- \[bundle\] Fix extensions priority ordering. Must be from high to low. [\#38](https://github.com/php-enqueue/enqueue-dev/pull/38) ([makasim](https://github.com/makasim))

## [0.2.8](https://github.com/php-enqueue/enqueue-dev/tree/0.2.8) (2017-04-03)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.7...0.2.8)

**Merged pull requests:**

- Improvements and fixes [\#37](https://github.com/php-enqueue/enqueue-dev/pull/37) ([makasim](https://github.com/makasim))
- fix fsdriver router topic name [\#34](https://github.com/php-enqueue/enqueue-dev/pull/34) ([bendavies](https://github.com/bendavies))
- run php-cs-fixer [\#33](https://github.com/php-enqueue/enqueue-dev/pull/33) ([bendavies](https://github.com/bendavies))

## [0.2.7](https://github.com/php-enqueue/enqueue-dev/tree/0.2.7) (2017-03-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.6...0.2.7)

**Merged pull requests:**

- \[client\] Allow send objects that implements \JsonSerializable interface. [\#30](https://github.com/php-enqueue/enqueue-dev/pull/30) ([makasim](https://github.com/makasim))

## [0.2.6](https://github.com/php-enqueue/enqueue-dev/tree/0.2.6) (2017-03-14)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.5...0.2.6)

**Merged pull requests:**

- Fix Simple Client [\#29](https://github.com/php-enqueue/enqueue-dev/pull/29) ([ASKozienko](https://github.com/ASKozienko))
- Update quick\_tour.md add Bundle to AppKernel [\#26](https://github.com/php-enqueue/enqueue-dev/pull/26) ([jverdeyen](https://github.com/jverdeyen))
- \[doc\] Add docs about message processors. [\#24](https://github.com/php-enqueue/enqueue-dev/pull/24) ([makasim](https://github.com/makasim))
- Fix unclear sentences in docs [\#21](https://github.com/php-enqueue/enqueue-dev/pull/21) ([cirnatdan](https://github.com/cirnatdan))

## [0.2.5](https://github.com/php-enqueue/enqueue-dev/tree/0.2.5) (2017-01-27)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.4...0.2.5)

**Merged pull requests:**

- \[amqp\] Put in buffer not our message. Continue consumption.  [\#22](https://github.com/php-enqueue/enqueue-dev/pull/22) ([makasim](https://github.com/makasim))
- \[travis\] Run test with different Symfony versions. 2.8, 3.0 [\#19](https://github.com/php-enqueue/enqueue-dev/pull/19) ([makasim](https://github.com/makasim))
- \[fs\] Add missing enqueue/psr-queue package to composer.json. [\#18](https://github.com/php-enqueue/enqueue-dev/pull/18) ([makasim](https://github.com/makasim))

## [0.2.4](https://github.com/php-enqueue/enqueue-dev/tree/0.2.4) (2017-01-18)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.3...0.2.4)

**Merged pull requests:**

- \[consumption\]\[bug\] Receive timeout is in milliseconds. Set it to 5000.… [\#14](https://github.com/php-enqueue/enqueue-dev/pull/14) ([makasim](https://github.com/makasim))
- Filesystem transport [\#12](https://github.com/php-enqueue/enqueue-dev/pull/12) ([makasim](https://github.com/makasim))
- \[consumption\] Do not print "Switch to queue xxx" if queue the same. [\#11](https://github.com/php-enqueue/enqueue-dev/pull/11) ([makasim](https://github.com/makasim))

## [0.2.3](https://github.com/php-enqueue/enqueue-dev/tree/0.2.3) (2017-01-09)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.2...0.2.3)

**Merged pull requests:**

- Auto generate changelog  [\#10](https://github.com/php-enqueue/enqueue-dev/pull/10) ([makasim](https://github.com/makasim))
- \[travis\] Cache docker images on travis. [\#9](https://github.com/php-enqueue/enqueue-dev/pull/9) ([makasim](https://github.com/makasim))
- \[enhancement\]\[amqp-ext\] Add purge queue method to amqp context. [\#8](https://github.com/php-enqueue/enqueue-dev/pull/8) ([makasim](https://github.com/makasim))
- \[bug\]\[amqp-ext\] Receive timeout parameter is miliseconds [\#7](https://github.com/php-enqueue/enqueue-dev/pull/7) ([makasim](https://github.com/makasim))

## [0.2.2](https://github.com/php-enqueue/enqueue-dev/tree/0.2.2) (2017-01-06)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.1...0.2.2)

**Merged pull requests:**

- \[amqp\] introduce lazy context. [\#6](https://github.com/php-enqueue/enqueue-dev/pull/6) ([makasim](https://github.com/makasim))

## [0.2.1](https://github.com/php-enqueue/enqueue-dev/tree/0.2.1) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.2.0...0.2.1)

## [0.2.0](https://github.com/php-enqueue/enqueue-dev/tree/0.2.0) (2017-01-05)
[Full Changelog](https://github.com/php-enqueue/enqueue-dev/compare/0.1.0...0.2.0)

**Merged pull requests:**

- Upd php cs fixer [\#3](https://github.com/php-enqueue/enqueue-dev/pull/3) ([makasim](https://github.com/makasim))
- \[psr\] Introduce MessageProcessor interface \(moved from consumption\). [\#2](https://github.com/php-enqueue/enqueue-dev/pull/2) ([makasim](https://github.com/makasim))
- \[bundle\] Add ability to disable signal extension. [\#1](https://github.com/php-enqueue/enqueue-dev/pull/1) ([makasim](https://github.com/makasim))

## [0.1.0](https://github.com/php-enqueue/enqueue-dev/tree/0.1.0) (2016-12-29)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*
