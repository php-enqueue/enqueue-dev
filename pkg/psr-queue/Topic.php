<?php

namespace Enqueue\Psr;

@trigger_error('The class is deprecated.', E_USER_DEPRECATED);

/**
 * @deprecated use PsrTopic
 */
interface Topic extends PsrTopic, Destination
{
}
