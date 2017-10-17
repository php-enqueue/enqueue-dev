# Config reference

You can get this info by running `./bin/console config:dump-reference enqueue` command.

```yaml
# Default configuration for extension with alias: "enqueue"
enqueue:
    transport:            # Required
        default:
            alias:                ~
            dsn:                  ~
        null:
            dsn:                  ~
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
            driver:               ~ # One of "ext"; "lib"; "bunny"

            # The connection to AMQP broker set as a string. Other parameters could be used as defaults
            dsn:                  ~

            # The host to connect too. Note: Max 1024 characters
            host:                 ~

            # Port on the host.
            port:                 ~

            # The user name to use. Note: Max 128 characters.
            user:                 ~

            # Password. Note: Max 128 characters.
            pass:                 ~

            # The virtual host on the host. Note: Max 128 characters.
            vhost:                ~

            # Connection timeout. Note: 0 or greater seconds. May be fractional.
            connection_timeout:   ~

            # Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
            read_timeout:         ~

            # Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
            write_timeout:        ~

            # How often to send heartbeat. 0 means off.
            heartbeat:            ~
            persisted:            ~
            lazy:                 ~

            # The receive strategy to be used. We suggest to use basic_consume as it is more performant. Though you need AMQP extension 1.9.1 or higher
            receive_method:       ~ # One of "basic_get"; "basic_consume"

            # The server will send a message in advance if it is equal to or smaller in size than the available prefetch size. May be set to zero, meaning "no specific limit"
            qos_prefetch_size:    ~

            # Specifies a prefetch window in terms of whole messages
            qos_prefetch_count:   ~

            # If "false" the QoS settings apply to the current channel only. If this field is "true", they are applied to the entire connection.
            qos_global:           ~

            # The options that are specific to the amqp transport you chose. For example amqp+lib have insist, keepalive, stream options. amqp+bunny has tcp_nodelay extra option.
            driver_options:       ~
        rabbitmq_amqp:
            driver:               ~ # One of "ext"; "lib"; "bunny"

            # The connection to AMQP broker set as a string. Other parameters could be used as defaults
            dsn:                  ~

            # The host to connect too. Note: Max 1024 characters
            host:                 ~

            # Port on the host.
            port:                 ~

            # The user name to use. Note: Max 128 characters.
            user:                 ~

            # Password. Note: Max 128 characters.
            pass:                 ~

            # The virtual host on the host. Note: Max 128 characters.
            vhost:                ~

            # Connection timeout. Note: 0 or greater seconds. May be fractional.
            connection_timeout:   ~

            # Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
            read_timeout:         ~

            # Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
            write_timeout:        ~

            # How often to send heartbeat. 0 means off.
            heartbeat:            ~
            persisted:            ~
            lazy:                 ~

            # The receive strategy to be used. We suggest to use basic_consume as it is more performant. Though you need AMQP extension 1.9.1 or higher
            receive_method:       ~ # One of "basic_get"; "basic_consume"

            # The server will send a message in advance if it is equal to or smaller in size than the available prefetch size. May be set to zero, meaning "no specific limit"
            qos_prefetch_size:    ~

            # Specifies a prefetch window in terms of whole messages
            qos_prefetch_count:   ~

            # If "false" the QoS settings apply to the current channel only. If this field is "true", they are applied to the entire connection.
            qos_global:           ~

            # The options that are specific to the amqp transport you chose. For example amqp+lib have insist, keepalive, stream options. amqp+bunny has tcp_nodelay extra option.
            driver_options:       ~

            # The delay strategy to be used. Possible values are "dlx", "delayed_message_plugin" or service id
            delay_strategy:       dlx
        fs:

            # The path to a directory where to store messages given as DSN. For example file://tmp/foo
            dsn:                  ~

            # The store directory where all queue\topics files will be created and messages are stored
            path:                 ~

            # The option tells how many messages should be read from file at once. The feature save resources but could lead to bigger messages lose.
            pre_fetch_count:      1

            # The queue files are created with this given permissions if not exist.
            chmod:                384

            # How often query for new messages.
            polling_interval:     100
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

            # The Doctrine DBAL DSN. Other parameters are ignored if set
            dsn:                  ~

            # Doctrine DBAL connection options. See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
            connection:           ~

            # Doctrine dbal connection name.
            dbal_connection_name: null

            # Database table name.
            table_name:           enqueue

            # How often query for new messages.
            polling_interval:     1000
            lazy:                 true
        sqs:
            key:                  null
            secret:               null
            token:                null
            region:               ~ # Required
            retries:              3
            version:              '2012-11-05'

            # the connection will be performed as later as possible, if the option set to true
            lazy:                 true
    client:
        traceable_producer:   false
        prefix:               enqueue
        app_name:             app
        router_topic:         default
        router_queue:         default
        router_processor:     enqueue.client.router_processor
        default_processor_queue: default
        redelivered_delay_time: 0
    consumption:

        # the time in milliseconds queue consumer waits if no message received
        idle_timeout:         0

        # the time in milliseconds queue consumer waits for a message (100 ms by default)
        receive_timeout:      100
    job:                  false
    async_events:
        enabled:              false
    extensions:
        doctrine_ping_connection_extension: false
        doctrine_clear_identity_map_extension: false
        signal_extension:     true
        reply_extension:      true
```

[back to index](../index.md)
