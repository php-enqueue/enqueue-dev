---
layout: default
parent: "Symfony bundle"
title: Config reference
nav_order: 2
---
{% include support.md %}

# Config reference

You can get this info by running `./bin/console config:dump-reference enqueue` command.

```yaml
# Default configuration for extension with alias: "enqueue"
enqueue:

    # Prototype
    key:

        # The transport option could accept a string DSN, an array with DSN key, or null. It accept extra options. To find out what option you can set, look at connection factory constructor docblock.
        transport:            # Required

            # The MQ broker DSN. These schemes are supported: "file", "amqp", "amqps", "db2", "ibm-db2", "mssql", "sqlsrv", "mysql", "mysql2", "pgsql", "postgres", "sqlite", "sqlite3", "null", "gearman", "beanstalk", "kafka", "rdkafka", "redis", "rediss", "stomp", "sqs", "gps", "mongodb", "wamp", "ws", to use these "file", "amqp", "amqps", "db2", "ibm-db2", "mssql", "sqlsrv", "mysql", "mysql2", "pgsql", "postgres", "sqlite", "sqlite3", "null", "gearman", "beanstalk", "kafka", "rdkafka", "redis", "rediss", "stomp", "sqs", "gps", "mongodb", "wamp", "ws" you have to install a package.
            dsn:                  ~ # Required

            # The connection factory class should implement "Interop\Queue\ConnectionFactory" interface
            connection_factory_class: ~

            # The factory class should implement "Enqueue\ConnectionFactoryFactoryInterface" interface
            factory_service:      ~

            # The factory service should be a class that implements "Enqueue\ConnectionFactoryFactoryInterface" interface
            factory_class:        ~
        consumption:

            # the time in milliseconds queue consumer waits for a message (100 ms by default)
            receive_timeout:      10000
        client:
            traceable_producer:   true
            prefix:               enqueue
            separator:            .
            app_name:             app
            router_topic:         default
            router_queue:         default
            router_processor:     null
            redelivered_delay_time: 0
            default_queue:        default

            # The array contains driver specific options
            driver_options:       []

        # The "monitoring" option could accept a string DSN, an array with DSN key, or null. It accept extra options. To find out what option you can set, look at stats storage constructor doc block.
        monitoring:

            # The stats storage DSN. These schemes are supported: "wamp", "ws", "influxdb".
            dsn:                  ~ # Required

            # The factory class should implement "Enqueue\Monitoring\StatsStorageFactory" interface
            storage_factory_service: ~

            # The factory service should be a class that implements "Enqueue\Monitoring\StatsStorageFactory" interface
            storage_factory_class: ~
        async_commands:
            enabled:              false
            timeout:              60
            command_name:         ~
            queue_name:           ~
        job:
            enabled:              false
            default_mapping:      true
        async_events:
            enabled:              false
        extensions:
            doctrine_ping_connection_extension: false
            doctrine_clear_identity_map_extension: false
            doctrine_odm_clear_identity_map_extension: false
            doctrine_closed_entity_manager_extension: false
            reset_services_extension: false
            signal_extension:     true
            reply_extension:      true
```

[back to index](index.md)
