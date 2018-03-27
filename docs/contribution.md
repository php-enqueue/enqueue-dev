# Contribution

To contribute you have to fork a [enqueue-dev](https://github.com/php-enqueue/enqueue-dev) repository.
Clone it locally.
 
## Setup environment

```
composer install
./bin/pre-commit -i
./bin/dev -b
```

Once you did it you can work on a feature or bug fix.

## Testing

To run tests simply run 

```
./bin/dev -t
```

## Commit 

When you try to commit changes `php-cs-fixer` is run. It fixes all coding style issues. Don't forget to stage them and commit everything.
Once everything is done open a pull request on official repository. 

[back to index](index.md)