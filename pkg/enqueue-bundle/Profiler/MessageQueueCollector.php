<?php

namespace Enqueue\Bundle\Profiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

if (Kernel::MAJOR_VERSION < 5) {
    class MessageQueueCollector extends AbstractMessageQueueCollector
    {
        /**
         * {@inheritdoc}
         */
        public function collect(Request $request, Response $response, \Exception $exception = null)
        {
            $this->collectInternal($request, $response);
        }
    }
} else {
    class MessageQueueCollector extends AbstractMessageQueueCollector
    {
        /**
         * {@inheritdoc}
         */
        public function collect(Request $request, Response $response, \Throwable $exception = null)
        {
            $this->collectInternal($request, $response);
        }
    }
}
