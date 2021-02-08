<?php

declare(strict_types=1);


namespace Enqueue\RdKafka;


use Interop\Queue\Context;

interface RdKafkaContextInterface extends Context, SerializerAwareInterface
{
}
