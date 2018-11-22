<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

# NULL transport

This a special transport implementation, kind of stub. 
It does not send nor receive anything.
Useful in tests for example.

* [Installation](#installation)
* [Create context](#create-context)

## Installation

```bash
$ composer require enqueue/null
```

## Create context

```php
<?php
use Enqueue\Null\NullConnectionFactory;

$connectionFactory = new NullConnectionFactory();

$context = $connectionFactory->createContext();
```

[back to index](../index.md)