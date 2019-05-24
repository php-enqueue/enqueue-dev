<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

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

[back to index](index.md)
