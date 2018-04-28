#!/bin/bash

set -x
set -e

rm -rf vendor/enqueue/*
ln -s ../../pkg/amqp-bunny vendor/enqueue
ln -s ../../pkg/amqp-ext vendor/enqueue
ln -s ../../pkg/amqp-lib vendor/enqueue
ln -s ../../pkg/amqp-tools vendor/enqueue
ln -s ../../pkg/async-event-dispatcher vendor/enqueue
ln -s ../../pkg/dbal vendor/enqueue
ln -s ../../pkg/enqueue vendor/enqueue
ln -s ../../pkg/enqueue-bundle vendor/enqueue
ln -s ../../pkg/fs vendor/enqueue
ln -s ../../pkg/gearman vendor/enqueue
ln -s ../../pkg/gps vendor/enqueue
ln -s ../../pkg/job-queue vendor/enqueue
ln -s ../../pkg/null vendor/enqueue
ln -s ../../pkg/pheanstalk vendor/enqueue
ln -s ../../pkg/rdkafka vendor/enqueue
ln -s ../../pkg/redis vendor/enqueue
ln -s ../../pkg/simple-client vendor/enqueue
ln -s ../../pkg/sqs vendor/enqueue
ln -s ../../pkg/stomp vendor/enqueue
ln -s ../../pkg/test vendor/enqueue
