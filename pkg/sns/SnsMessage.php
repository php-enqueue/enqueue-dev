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
     * SnsMessage constructor.
     *
     * See AWS documentation for message attribute structure.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#shape-messageattributevalue
     *
     * @param string      $body
     * @param array       $properties
     * @param array       $headers
     * @param array|null  $messageAttributes
     * @param string|null $messageStructure
     * @param string|null $phoneNumber
     * @param string|null $subject
     * @param string|null $targetArn
     */
    public function __construct(
        string $body = '',
        array $properties = [],
        array $headers = [],
        array $messageAttributes = null,
        string $messageStructure = null,
        string $phoneNumber = null,
        string $subject = null,
        string $targetArn = null
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

    /**
     * @return string|null
     */
    public function getSnsMessageId(): ?string
    {
        return $this->snsMessageId;
    }

    /**
     * @param string|null $snsMessageId
     */
    public function setSnsMessageId(?string $snsMessageId): void
    {
        $this->snsMessageId = $snsMessageId;
    }

    /**
     * @return string|null
     */
    public function getMessageStructure(): ?string
    {
        return $this->messageStructure;
    }

    /**
     * @param string|null $messageStructure
     */
    public function setMessageStructure(?string $messageStructure): void
    {
        $this->messageStructure = $messageStructure;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     */
    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     */
    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return array|null
     */
    public function getMessageAttributes(): ?array
    {
        return $this->messageAttributes;
    }

    /**
     * @param array|null $messageAttributes
     */
    public function setMessageAttributes(?array $messageAttributes): void
    {
        $this->messageAttributes = $messageAttributes;
    }

    /**
     * @param string $name
     * @param null   $default
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
     *
     * @param string     $name
     * @param array|null $attribute
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
     * @param string                          $name
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

    /**
     * @return string|null
     */
    public function getTargetArn(): ?string
    {
        return $this->targetArn;
    }

    /**
     * @param string|null $targetArn
     */
    public function setTargetArn(?string $targetArn): void
    {
        $this->targetArn = $targetArn;
    }
}
