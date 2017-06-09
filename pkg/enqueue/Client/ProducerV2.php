<?php

namespace Enqueue\Client;

/**
 * @experimental
 */
class ProducerV2 implements ProducerV2Interface
{
    /**
     * @var ProducerInterface
     */
    private $realProducer;

    /**
     * @var RpcClient
     */
    private $rpcClient;

    /**
     * @param ProducerInterface $realProducer
     * @param RpcClient         $rpcClient
     */
    public function __construct(ProducerInterface $realProducer, RpcClient $rpcClient)
    {
        $this->realProducer = $realProducer;
        $this->rpcClient = $rpcClient;
    }

    /**
     * {@inheritdoc}
     */
    public function sendEvent($topic, $message)
    {
        $this->realProducer->send($topic, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, Config::COMMAND_TOPIC);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $command);
        $message->setScope(Message::SCOPE_APP);

        if ($needReply) {
            return $this->rpcClient->callAsync(Config::COMMAND_TOPIC, $message, 60);
        }

        $this->realProducer->send(Config::COMMAND_TOPIC, $message);
    }
}
