<?php
namespace Enqueue\Client;

use Enqueue\Psr\PsrContext;
use Psr\Log\LoggerInterface;

interface ExtensionInterface
{
    /**
     * @param string  $topic
     * @param Message $message
     * @return
     */
    public function onPreSend($topic, Message $message);

    /**
     * @param string $topic
     * @param Message $message
     * @return
     */
    public function onPostSend($topic, Message $message);


    /**
     * @param PsrContext           $context
     * @param LoggerInterface|null $logger
     */
    public function onPostSetupBroker(PsrContext $context, LoggerInterface $logger = null);
}
