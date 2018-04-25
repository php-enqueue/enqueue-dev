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

$psrContext = $connectionFactory->createContext();
```

[back to index](../index.md)