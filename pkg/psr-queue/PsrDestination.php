<?php

namespace Enqueue\Psr;

/**
 * A Destination object encapsulates a provider-specific address.
 * The transport API does not define a standard address syntax.
 * Although a standard address syntax was considered,
 * it was decided that the differences in address semantics between existing message-oriented middleware (MOM)
 * products were too wide to bridge with a single syntax.
 *
 * Since Destination is an administered object,
 * it may contain provider-specific configuration information in addition to its address.
 *
 * @see https://docs.oracle.com/javaee/7/api/javax/jms/Destination.html
 */
interface PsrDestination
{
}
