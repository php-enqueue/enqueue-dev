<?php
namespace Enqueue\Bundle\Profiler;

use Enqueue\Client\MessagePriority;
use Enqueue\Client\MessageProducerInterface;
use Enqueue\Client\TraceableMessageProducer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class MessageQueueCollector extends DataCollector
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'sent_messages' => [],
        ];

        if ($this->messageProducer instanceof TraceableMessageProducer) {
            $this->data['sent_messages'] = $this->messageProducer->getTraces();
        }
    }

    /**
     * @return array
     */
    public function getSentMessages()
    {
        return $this->data['sent_messages'];
    }

    /**
     * @param string $priority
     *
     * @return string
     */
    public function prettyPrintPriority($priority)
    {
        $map = [
            MessagePriority::VERY_LOW => 'very low',
            MessagePriority::LOW => 'low',
            MessagePriority::NORMAL => 'normal',
            MessagePriority::HIGH => 'high',
            MessagePriority::VERY_HIGH => 'very high',
        ];

        return isset($map[$priority]) ? $map[$priority] : $priority;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public function prettyPrintMessage($message)
    {
        if (is_scalar($message)) {
            return htmlspecialchars($message);
        }

        return htmlspecialchars(
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'enqueue.message_queue';
    }
}
