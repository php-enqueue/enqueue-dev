# Consumption extension

Here, I show how you can create a custom extension and register it.
Let's first create an extension itself:

```php
<?php
// src/AppBundle/Enqueue;
namespace AppBundle\Enqueue;

use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\Context;

class CountProcessedMessagesExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;
    
    private $processedMessages = 0;
    
    /**
     * {@inheritdoc}  
     */
    public function onPostReceived(Context $context)
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

[back to index](../index.md)
