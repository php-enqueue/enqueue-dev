<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# Client extensions.

There is an ability to hook into sending process. You have to create an extension class that implements `Enqueue\Client\ExtensionInterface` interface.
For example, `TimestampMessageExtension` extension adds timestamps every message before sending it to MQ. 

```php
<?php
namespace Acme;

use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;

class TimestampMessageExtension implements ExtensionInterface
{
    public function onPreSend($topic, Message $message)
    {
        if ($message->getTimestamp()) {
            $message->setTimestamp(time());
        }
    }
    
    public function onPostSend($topic, Message $message)
    {
        
    }
} 
```

## Symfony

To use the extension in Symfony, you have to register it as a container service with a special tag. 

```yaml
# config/services.yaml

services:
  timestamp_message_extension:
    class: Acme\TimestampMessageExtension
    tags:
      - { name: 'enqueue.client.extensions' }    
```

You can add `priority` attribute with a number. The higher value you set the earlier the extension is called.  

[back to index](../index.md)
