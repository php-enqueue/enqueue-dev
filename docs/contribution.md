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
./bin/dev -t
```

or for a package only:


```
./bin/dev -t pkg/enqueue
```

## Commit 

When you try to commit changes `php-cs-fixer` is run. It fixes all coding style issues. Don't forget to stage them and commit everything.
Once everything is done open a pull request on official repository. 

[back to index](index.md)
