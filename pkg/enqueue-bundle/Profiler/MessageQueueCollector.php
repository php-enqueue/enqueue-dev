<?php

namespace Enqueue\Bundle\Profiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MessageQueueCollector extends AbstractMessageQueueCollector
{
    public function collect(Request $request, Response $response, Throwable $exception = null)
    {
        $this->collectInternal($request, $response);
    }
}
