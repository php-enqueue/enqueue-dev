<?php

namespace Enqueue\Psr;

class InvalidDeliveryModeException extends Exception
{
    /**
     * @param int $deliveryMode
     *
     * @throws static
     */
    public static function assertValidDeliveryMode($deliveryMode)
    {
        $deliveryModes = [DeliveryMode::PERSISTENT, DeliveryMode::NON_PERSISTENT];

        if (false == in_array($deliveryMode, $deliveryModes, true)) {
            throw new static(sprintf(
                'The delivery mode must be one of [%s].',
                implode(',', $deliveryModes)
            ));
        }
    }
}
