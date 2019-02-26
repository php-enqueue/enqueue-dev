# Upgrading Enqueue:

From `0.8.x` to `0.9.x`:

## Processor declaration

`Interop\Queue\PsrProcessor` interface has been replaced by `Interop\Queue\Processor`
`Interop\Queue\PsrMessage` interface has been replaced by `Interop\Queue\Message`
`Interop\Queue\PsrContext` interface has been replaced by `Interop\Queue\Context`



## Symfony Bundle

### Configuration changes:

`0.8.x`


```
enqueue:
    transport:
       default: ...
```

`0.9.x`


```
enqueue:
    default:
        transport: ...
```

In `0.9.x` the client name is a root config node.

The `default_processor_queue` Client option was removed.

### Service declarations:

`0.8.x`


```
tags:
     - { name: 'enqueue.client.processor' }
```

`0.9.x`


```
tags:
     - { name: 'enqueue.command_subscriber' }
     - { name: 'enqueue.topic_subscriber' }
     - { name: 'enqueue.processor' }
```

The tag to register message processors has changed and is now split into processor sub types.

### CommandSubscriberInterface `getSubscribedCommand`


`0.8.x`

return `aCommandName` or 
```
      [
        'processorName' => 'aCommandName',
        'queueName' => 'a_client_queue_name',
        'queueNameHardcoded' => true,
        'exclusive' => true,
      ]
```

`0.9.x`


return `aCommandName` or 
```
     [
        'command' => 'aSubscribedCommand',
        'processor' => 'aProcessorName',
        'queue' => 'a_client_queue_name',
        'prefix_queue' => true,
        'exclusive' => true,
     ]
```

