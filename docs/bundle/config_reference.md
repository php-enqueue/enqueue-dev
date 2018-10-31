# Config reference

You can get this info by running `./bin/console config:dump-reference enqueue` command.

```yaml
# Default configuration for extension with alias: "enqueue"
enqueue:

    # The transport option could accept a string DSN, an array with DSN key, or null. It accept extra options. To find out what option you can set, look at connection factory constructor docblock.
    transport:

        # The broker DSN. These schemes are supported: "file", "amqp", "amqps", "db2", "ibm-db2", "mssql", "sqlsrv", "mysql", "mysql2", "pgsql", "postgres", "sqlite", "sqlite3", "null", "gearman", "beanstalk", "kafka", "rdkafka", "redis", "stomp", "sqs", "gps", "mongodb", to use these "file", "amqp", "amqps", "db2", "ibm-db2", "mssql", "sqlsrv", "mysql", "mysql2", "pgsql", "postgres", "sqlite", "sqlite3", "null", "gearman", "beanstalk", "kafka", "rdkafka", "redis", "stomp", "sqs", "gps", "mongodb" you have to install a package.
        dsn:                  ~ # Required

        # The connection factory class should implement "Interop\Queue\ConnectionFactory" interface
        connection_factory_class: ~

        # The factory class should implement "Enqueue\ConnectionFactoryFactoryInterface" interface
        factory_service:      ~

        # The factory service should be a class that implements "Enqueue\ConnectionFactoryFactoryInterface" interface
        factory_class:        ~
    client:
        traceable_producer:   true
        prefix:               enqueue
        app_name:             app
        router_topic:         default
        router_queue:         default
        router_processor:     Enqueue\Client\RouterProcessor
        default_processor_queue: default
        redelivered_delay_time: 0
    consumption:

        # the time in milliseconds queue consumer waits for a message (100 ms by default)
        receive_timeout:      100
    job:                  false
    async_events:
        enabled:              false
    async_commands:
        enabled:              false
    extensions:
        doctrine_ping_connection_extension: false
        doctrine_clear_identity_map_extension: false
        signal_extension:     true
        reply_extension:      true
```

[back to index](../index.md)
