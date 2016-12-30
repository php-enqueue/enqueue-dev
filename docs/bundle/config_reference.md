# Config reference

```yaml
# Default configuration for extension with alias: "enqueue"
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
        rabbitmq_stomp:
            host:                 localhost
            port:                 61613
            login:                guest
            password:             guest
            vhost:                /
            sync:                 true
            connection_timeout:   1
            buffer_size:          1000

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
        rabbitmq:

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

            # The option tells whether RabbitMQ broker has delay plugin installed or not
            delay_plugin_installed: false
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
```

[back to index](../index.md)