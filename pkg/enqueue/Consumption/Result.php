<?php

namespace Enqueue\Consumption;

use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class Result
{
    /**
     * @see PsrProcessor::ACK for more details
     */
    const ACK = PsrProcessor::ACK;

    /**
     * @see PsrProcessor::ACK for more details
     */
    const REJECT = PsrProcessor::REJECT;

    /**
     * @see PsrProcessor::ACK for more details
     */
    const REQUEUE = PsrProcessor::REQUEUE;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var PsrMessage|null
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
     * @return PsrMessage|null
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * @param PsrMessage|null $reply
     */
    public function setReply(PsrMessage $reply = null)
    {
        $this->reply = $reply;
    }

    /**
     * @param string $reason
     *
     * @return Result
     */
    public static function ack($reason = '')
    {
        return new static(self::ACK, $reason);
    }

    /**
     * @param string $reason
     *
     * @return Result
     */
    public static function reject($reason)
    {
        return new static(self::REJECT, $reason);
    }

    /**
     * @param string $reason
     *
     * @return Result
     */
    public static function requeue($reason = '')
    {
        return new static(self::REQUEUE, $reason);
    }

    /**
     * @param PsrMessage  $replyMessage
     * @param string      $status
     * @param string|null $reason
     *
     * @return Result
     */
    public static function reply(PsrMessage $replyMessage, $status = self::ACK, $reason = null)
    {
        $status = null === $status ? self::ACK : $status;

        $result = new static($status, $reason);
        $result->setReply($replyMessage);

        return $result;
    }
}
