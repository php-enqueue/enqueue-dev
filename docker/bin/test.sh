#!/usr/bin/env bash

# wait for service
# $1 host
# $2 port
# $3 attempts

FORCE_EXIT=false

function waitForService()
{
    ATTEMPTS=0
    until nc -z $1 $2; do
        printf "wait for service %s:%s\n" $1 $2
        ((ATTEMPTS++))
        if [ $ATTEMPTS -ge $3 ]; then
            printf "service is not running %s:%s\n" $1 $2
            exit 1
        fi
        if [ "$FORCE_EXIT" = true ]; then
            exit;
        fi

        sleep 1
    done

    printf "service is online %s:%s\n" $1 $2
}

trap "FORCE_EXIT=true" SIGTERM SIGINT

waitForService rabbitmq 5672 50
waitForService rabbitmqssl 5671 50
waitForService mysql 3306 50
waitForService postgres 5432 50
waitForService redis 6379 50
waitForService beanstalkd 11300 50
waitForService gearmand 4730 50
waitForService kafka 9092 50
waitForService mongo 27017 50
waitForService thruway 9090 50
waitForService localstack 4566 50

php docker/bin/refresh-mysql-database.php || exit 1
php docker/bin/refresh-postgres-database.php  || exit 1
php pkg/job-queue/Tests/Functional/app/console doctrine:database:create --if-not-exists || exit 1
php pkg/job-queue/Tests/Functional/app/console doctrine:schema:update --force || exit 1

#php pkg/enqueue-bundle/Tests/Functional/app/console.php config:dump-reference  enqueue
bin/phpunit "$@"
