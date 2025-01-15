<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Interop\Queue\Impl\MessageTrait;
use Interop\Queue\Message;
use Psr\Http\Message\StreamInterface;

class SnsMessage implements Message
{
    use MessageTrait;

    /**
     * @var string|null
     */
    private $snsMessageId;

    /**
     * @var string|null
     */
    private $messageStructure;

    /**
     * @var string|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $subject;

    /**
     * @var array|null
     */
    private $messageAttributes;

    /**
     * @var string|null
     */
    private $targetArn;

    /**
     * @var string|null
     */
    private $messageGroupId;

    /**
     * @var string|null
     */
    private $messageDeduplicationId;

    /**
     * SnsMessage constructor.
     *
     * See AWS documentation for message attribute structure.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#shape-messageattributevalue
     */
    public function __construct(
        string $body = '',
        array $properties = [],
        array $headers = [],
        ?array $messageAttributes = null,
        ?string $messageStructure = null,
        ?string $phoneNumber = null,
        ?string $subject = null,
        ?string $targetArn = null,
    ) {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->messageAttributes = $messageAttributes;
        $this->messageStructure = $messageStructure;
        $this->phoneNumber = $phoneNumber;
        $this->subject = $subject;
        $this->targetArn = $targetArn;
        $this->redelivered = false;
    }

    public function getSnsMessageId(): ?string
    {
        return $this->snsMessageId;
    }

    public function setSnsMessageId(?string $snsMessageId): void
    {
        $this->snsMessageId = $snsMessageId;
    }

    public function getMessageStructure(): ?string
    {
        return $this->messageStructure;
    }

    public function setMessageStructure(?string $messageStructure): void
    {
        $this->messageStructure = $messageStructure;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    public function setMessageAttributes(?array $messageAttributes): void
    {
        $this->messageAttributes = $messageAttributes;
    }

    /**
     * @param null $default
     *
     * @return array|null
     */
    public function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->messageAttributes) ? $this->messageAttributes[$name] : $default;
    }

    /**
     * Attribute array format:
     *     [
     *        'BinaryValue' => <string || resource || Psr\Http\Message\StreamInterface>,
     *        'DataType' => '<string>', // REQUIRED
     *        'StringValue' => '<string>',
     *     ].
     */
    public function setAttribute(string $name, ?array $attribute): void
    {
        if (null === $attribute) {
            unset($this->messageAttributes[$name]);
        } else {
            $this->messageAttributes[$name] = $attribute;
        }
    }

    /**
     * @param string                          $dataType String, String.Array, Number, or Binary
     * @param string|resource|StreamInterface $value
     */
    public function addAttribute(string $name, string $dataType, $value): void
    {
        $valueKey = 'Binary' === $dataType ? 'BinaryValue' : 'StringValue';

        $this->messageAttributes[$name] = [
            'DataType' => $dataType,
            $valueKey => $value,
        ];
    }

    public function getTargetArn(): ?string
    {
        return $this->targetArn;
    }

    public function setTargetArn(?string $targetArn): void
    {
        $this->targetArn = $targetArn;
    }

    /**
     * Only FIFO.
     *
     * The tag that specifies that a message belongs to a specific message group. Messages that belong to the same
     * message group are processed in a FIFO manner (however, messages in different message groups might be processed
     * out of order).
     * To interleave multiple ordered streams within a single queue, use MessageGroupId values (for example, session
     * data for multiple users). In this scenario, multiple readers can process the queue, but the session data
     * of each user is processed in a FIFO fashion.
     * For more information, see: https://docs.aws.amazon.com/sns/latest/dg/fifo-message-grouping.html
     */
    public function setMessageGroupId(?string $id = null): void
    {
        $this->messageGroupId = $id;
    }

    public function getMessageGroupId(): ?string
    {
        return $this->messageGroupId;
    }

    /**
     * Only FIFO.
     *
     * The token used for deduplication of sent messages. If a message with a particular MessageDeduplicationId is
     * sent successfully, any messages sent with the same MessageDeduplicationId are accepted successfully but
     * aren't delivered during the 5-minute deduplication interval.
     * For more information, see https://docs.aws.amazon.com/sns/latest/dg/fifo-message-dedup.html
     */
    public function setMessageDeduplicationId(?string $id = null): void
    {
        $this->messageDeduplicationId = $id;
    }

    public function getMessageDeduplicationId(): ?string
    {
        return $this->messageDeduplicationId;
    }
}
