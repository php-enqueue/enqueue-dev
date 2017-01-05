<?php

namespace Enqueue\Client\Meta;

class QueueMeta
{
    /**
     * @var string
     */
    private $clientName;

    /**
     * @var string
     */
    private $transportName;

    /**
     * @var string[]
     */
    private $processors;

    /**
     * @param string   $clientName
     * @param string   $transportName
     * @param string[] $processors
     */
    public function __construct($clientName, $transportName, array $processors = [])
    {
        $this->clientName = $clientName;
        $this->transportName = $transportName;
        $this->processors = $processors;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @return string
     */
    public function getTransportName()
    {
        return $this->transportName;
    }

    /**
     * @return string[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }
}
