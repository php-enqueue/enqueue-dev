<?php

namespace Enqueue\Consumption;

use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

class Result
{
    /**
     * @see Processor::ACK for more details
     */
    const ACK = Processor::ACK;

    /**
     * @see Processor::REJECT for more details
     */
    const REJECT = Processor::REJECT;

    /**
     * @see Processor::REQUEUE for more details
     */
    const REQUEUE = Processor::REQUEUE;

    const ALREADY_ACKNOWLEDGED = 'enqueue.already_acknowledged';

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

    /**
     * @param mixed $status
     * @param mixed $reason
     */
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

    /**
     * @param InteropMessage|null $reply
     */
    public function setReply(InteropMessage $reply = null)
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
        return new static(self::ACK, $reason);
    }

    /**
     * @param string $reason
     *
     * @return static
     */
    public static function reject($reason)
    {
        return new static(self::REJECT, $reason);
    }

    /**
     * @param string $reason
     *
     * @return static
     */
    public static function requeue($reason = '')
    {
        return new static(self::REQUEUE, $reason);
    }

    /**
     * @param InteropMessage $replyMessage
     * @param string         $status
     * @param string|null    $reason
     *
     * @return static
     */
    public static function reply(InteropMessage $replyMessage, $status = self::ACK, $reason = null)
    {
        $status = null === $status ? self::ACK : $status;

        $result = new static($status, $reason);
        $result->setReply($replyMessage);

        return $result;
    }
}
