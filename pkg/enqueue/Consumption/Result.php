<?php

namespace Enqueue\Consumption;

use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

class Result
{
    /**
     * @see Processor::ACK for more details
     */
    public const ACK = Processor::ACK;

    /**
     * @see Processor::REJECT for more details
     */
    public const REJECT = Processor::REJECT;

    /**
     * @see Processor::REQUEUE for more details
     */
    public const REQUEUE = Processor::REQUEUE;

    public const ALREADY_ACKNOWLEDGED = 'enqueue.already_acknowledged';

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var InteropMessage|null
     */
    private $reply;

    public function __construct($status, $reason = '')
    {
        $this->status = (string) $status;
        $this->reason = (string) $reason;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return InteropMessage|null
     */
    public function getReply()
    {
        return $this->reply;
    }

    public function setReply(?InteropMessage $reply = null)
    {
        $this->reply = $reply;
    }

    /**
     * @param string $reason
     *
     * @return static
     */
    public static function ack($reason = '')
    {
        return new self(self::ACK, $reason);
    }

    /**
     * @param string $reason
     *
     * @return static
     */
    public static function reject($reason)
    {
        return new self(self::REJECT, $reason);
    }

    /**
     * @param string $reason
     *
     * @return static
     */
    public static function requeue($reason = '')
    {
        return new self(self::REQUEUE, $reason);
    }

    /**
     * @param string      $status
     * @param string|null $reason
     *
     * @return static
     */
    public static function reply(InteropMessage $replyMessage, $status = self::ACK, $reason = null)
    {
        $status = null === $status ? self::ACK : $status;

        $result = new self($status, $reason);
        $result->setReply($replyMessage);

        return $result;
    }
}
