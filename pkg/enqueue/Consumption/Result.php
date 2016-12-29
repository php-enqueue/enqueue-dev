<?php
namespace Enqueue\Consumption;

use Enqueue\Psr\Message;

class Result
{
    /**
     * Use this constant when the message is processed successfully and the message could be removed from the queue.
     */
    const ACK = 'enqueue.message_queue.consumption.ack';

    /**
     * Use this constant when the message is not valid or could not be processed
     * The message is removed from the queue.
     */
    const REJECT = 'enqueue.message_queue.consumption.reject';

    /**
     * Use this constant when the message is not valid or could not be processed right now but we can try again later
     * The original message is removed from the queue but a copy is publsihed to the queue again.
     */
    const REQUEUE = 'enqueue.message_queue.consumption.requeue';

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var Message|null
     */
    private $reply;

    /**
     * @return Message|null
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * @param Message|null $reply
     */
    public function setReply(Message $reply = null)
    {
        $this->reply = $reply;
    }

    /**
     * @param string $status
     * @param string $reason
     */
    public function __construct($status, $reason = '')
    {
        $this->status = (string) $status;
        $this->reason = (string) $reason;
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
     * @param Message     $replyMessage
     * @param string|null $reason
     *
     * @return Result
     */
    public static function reply(Message $replyMessage, $reason = '')
    {
        $result = static::ack($reason);
        $result->setReply($replyMessage);

        return $result;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->status;
    }
}
