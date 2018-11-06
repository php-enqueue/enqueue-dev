<h2 align="center">Supporting Enqueue</h2>

Enqueue is an MIT-licensed open source project with its ongoing development made possible entirely by the support of community and our customers. If you'd like to join them, please consider:

- [Become a sponsor](https://www.patreon.com/makasim)
- [Become our client](http://forma-pro.com/)

---

## DSN Parser.

The [enqueue/dsn](https://github.com/php-enqueue/dsn) tool helps to parse DSN\URI string. 
The tool is used by Enqueue transports to parse DSNs. 

## Installation

```bash 
composer req enqueue/dsn 0.9.x
```

### Examples

Basic usage:

```php
<?php

use Enqueue\Dsn\Dsn;

$dsn = new Dsn('mysql+pdo://user:password@localhost:3306/database?connection_timeout=123');

$dsn->getSchemeProtocol(); // 'mysql'
$dsn->getScheme(); // 'mysql+pdo'
$dsn->getSchemeExtensions(); // ['pdo']
$dsn->getUser(); // 'user'
$dsn->getPassword(); // 'password'
$dsn->getHost(); // 'localhost'
$dsn->getPort(); // 3306

$dsn->getQueryString(); // 'connection_timeout=123'
$dsn->getQuery(); // ['connection_timeout' => '123']
$dsn->getQueryParameter('connection_timeout'); // '123'
$dsn->getInt('connection_timeout'); // 123  
```

Some parts could be omitted:

```php
<?php
use Enqueue\Dsn\Dsn;

$dsn = new Dsn('sqs:?key=aKey&secret=aSecret&token=aToken');

$dsn->getSchemeProtocol(); // 'sqs'
$dsn->getScheme(); // 'sqs'
$dsn->getSchemeExtensions(); // []
$dsn->getUser(); // null
$dsn->getPassword(); // null
$dsn->getHost(); // null
$dsn->getPort(); // null

$dsn->getQueryParameter('key'); // 'aKey'
$dsn->getQueryParameter('secret'); // 'aSecret'
```

Throws exception if DSN not valid:

```php
<?php
use Enqueue\Dsn\Dsn;

$dsn = new Dsn('foo'); // throws exception here
```

Throws exception if cannot cast query parameter:

```php
<?php
use Enqueue\Dsn\Dsn;

$dsn = new Dsn('mysql:?connection_timeout=notInt');

$dsn->getInt('connection_timeout'); // throws exception here
```

[back to index](index.md)