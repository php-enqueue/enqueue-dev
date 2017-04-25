<?php
namespace Enqueue\Sqs;

use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrQueue;

class SqsConsumer implements PsrConsumer
{
    public function getQueue()
    {

    }

    public function receive($timeout = 0)
    {

    }

    public function receiveNoWait()
    {

    }

    public function acknowledge(PsrMessage $message)
    {

    }

    public function reject(PsrMessage $message, $requeue = false)
    {

    }
}
