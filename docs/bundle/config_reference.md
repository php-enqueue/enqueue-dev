# Config reference

You can get this info by running `./bin/console config:dump-reference enqueue` command.

```yaml
enqueue:
    transport:            # Required
        default:
            alias:                ~ # Required
        null:                 []
        stomp:
            host:                 localhost
            port:                 61613
            login:                guest
            password:             guest
            vhost:                /
            sync:                 true
            connection_timeout:   1
            buffer_size:          1000
            lazy:                 true
        rabbitmq_stomp:
            host:                 localhost
            port:                 61613
            login:                guest
            password:             guest
            vhost:                /
            sync:                 true
            connection_timeout:   1
            buffer_size:          1000
            lazy:                 true

            # The option tells whether RabbitMQ broker has management plugin installed or not
            management_plugin_installed: false
            management_plugin_port: 15672

            # The option tells whether RabbitMQ broker has delay plugin installed or not
            delay_plugin_installed: false
        amqp:

            # The host to connect too. Note: Max 1024 characters
            host:                 localhost

            # Port on the host.
            port:                 5672

            # The login name to use. Note: Max 128 characters.
            login:                guest

            # Password. Note: Max 128 characters.
            password:             guest

            # The virtual host on the host. Note: Max 128 characters.
            vhost:                /

            # Connection timeout. Note: 0 or greater seconds. May be fractional.
            connect_timeout:      ~

            # Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
            read_timeout:         ~

            # Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
            write_timeout:        ~
            persisted:            false
            lazy:                 true
        rabbitmq_amqp:

            # The host to connect too. Note: Max 1024 characters
            host:                 localhost

            # Port on the host.
            port:                 5672

            # The login name to use. Note: Max 128 characters.
            login:                guest

            # Password. Note: Max 128 characters.
            password:             guest

            # The virtual host on the host. Note: Max 128 characters.
            vhost:                /

            # Connection timeout. Note: 0 or greater seconds. May be fractional.
            connect_timeout:      ~

            # Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
            read_timeout:         ~

            # Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
            write_timeout:        ~
            persisted:            false
            lazy:                 true

            # The option tells whether RabbitMQ broker has delay plugin installed or not
            delay_plugin_installed: false
        fs:

            # The store directory where all queue\topics files will be created and messages are stored
            store_dir:            ~ # Required

            # The option tells how many messages should be read from file at once. The feature save resources but could lead to bigger messages lose.
            pre_fetch_count:      1

            # The queue files are created with this given permissions if not exist.
            chmod:                384
        redis:

            # can be a host, or the path to a unix domain socket
            host:                 ~ # Required
            port:                 ~

            # The library used internally to interact with Redis server
            vendor:               ~ # One of "phpredis"; "predis", Required

            # bool, Whether it use single persisted connection or open a new one for every context
            persisted:            false

            # the connection will be performed as later as possible, if the option set to true
            lazy:                 true
        dbal:

            # Doctrine DBAL connection options. See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
            connection:           ~

            # Doctrine dbal connection name.
            dbal_connection_name: null

            # Database table name.
            table_name:           enqueue

            # How often query for new messages.
            polling_interval:     1000
            lazy:                 true
    client:
        traceable_producer:   false
        prefix:               enqueue
        app_name:             app
        router_topic:         router
        router_queue:         default
        router_processor:     enqueue.client.router_processor
        default_processor_queue: default
        redelivered_delay_time: 0
    job:                  false
    extensions:
        doctrine_ping_connection_extension: false
        doctrine_clear_identity_map_extension: false
        signal_extension:     true
```

[back to index](../index.md)