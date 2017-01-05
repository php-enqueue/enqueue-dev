<?php

namespace Enqueue\Client\Meta;

class TopicMeta
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string[]
     */
    private $processors;

    /**
     * @param string   $name
     * @param string   $description
     * @param string[] $processors
     */
    public function __construct($name, $description = '', array $processors = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->processors = $processors;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }
}
