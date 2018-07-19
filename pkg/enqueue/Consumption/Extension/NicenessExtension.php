<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class NicenessExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var int
     */
    protected $niceness = 0;

    /**
     * @param int $niceness
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($niceness)
    {
        if (false === is_int($niceness)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected niceness value is int but got: "%s"',
                is_object($niceness) ? get_class($niceness) : gettype($niceness)
            ));
        }

        $this->niceness = $niceness;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        if (0 !== $this->niceness) {
            $changed = @proc_nice($this->niceness);
            if (!$changed) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot change process niceness, got warning: "%s"',
                    error_get_last()['message']
                ));
            }
        }
    }
}
