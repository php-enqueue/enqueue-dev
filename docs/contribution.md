---
layout: default
title: Contribution
nav_order: 99
---

{% include support.md %}

# Contribution

To contribute you have to send a pull request to [enqueue-dev](https://github.com/php-enqueue/enqueue-dev) repository.
The pull requests to read only subtree split [repositories](https://github.com/php-enqueue/enqueue-dev/blob/master/bin/subtree-split#L46) will be closed.

## Setup environment

```
composer install
./bin/pre-commit -i
./bin/dev -b
```

Once you did it you can work on a feature or bug fix.

## Testing

To run tests

```
./bin/test.sh
```

or for a package only:


```
./bin/test.sh pkg/enqueue
```

## Commit

When you try to commit changes `php-cs-fixer` is run. It fixes all coding style issues. Don't forget to stage them and commit everything.
Once everything is done open a pull request on official repository.

## WTF?!

* If you get `rabbitmqssl: forward host lookup failed: Unknown host, wait for service rabbitmqssl:5671` do `docker-compose down`.

[back to index](index.md)
