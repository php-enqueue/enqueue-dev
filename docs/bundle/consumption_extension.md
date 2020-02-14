---
layout: default
parent: "Symfony bundle"
title: Consumption extension
nav_order: 9
---
{% include support.md %}

# Consumption extension

Here, I show how you can create a custom extension and register it.
Let's first create an extension itself:

```php
<?php
// src/AppBundle/Enqueue;
namespace AppBundle\Enqueue;

use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Context\PostMessageReceived;

class CountProcessedMessagesExtension implements PostMessageReceivedExtensionInterface
{
    private $processedMessages = 0;

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $this->processedMessages += 1;
    }
}
```

Now we have to register as a Symfony service with special tag:

```yaml
services:
    app.enqueue.count_processed_messages_extension:
        class: 'AppBundle\Enqueue\CountProcessedMessagesExtension'
        tags:
            - { name: 'enqueue.consumption.extension', priority: 10 }
```

When using multiple enqueue instances, you can apply extension to 
specific or all instances by providing an additional tag attribute:

```
services:
    app.enqueue.count_processed_messages_extension:
        class: 'AppBundle\Enqueue\CountProcessedMessagesExtension'
        tags:
            - { name: 'enqueue.consumption.extension', priority: 10, client: 'all' }
```

[back to index](index.md)
