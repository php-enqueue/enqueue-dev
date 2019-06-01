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

If you need, you can also use composer scripts to run code linting and static analysis:
* For code style linting, run `composer run cs-lint`. Optionally add file names: 
`composer run cs-lint pkg/null/NullTopic.php` for example.
* You can also fix your code style with `composer run cs-fix`.
* Static code analysis can be run using `composer run phpstan`. As above, you can pass specific files.

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
