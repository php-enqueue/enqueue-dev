<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Config;
use Enqueue\Client\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

trait ExtractProcessorTagSubscriptionsTrait
{
    /**
     * @param ContainerBuilder $container
     * @param string           $processorServiceId
     * @param array            $tagAttributes
     *
     * @return array
     */
    protected function extractSubscriptions(ContainerBuilder $container, $processorServiceId, array $tagAttributes)
    {
        $resolve = function ($value) use ($container) {
            if (0 !== strpos($value, '%')) {
                return $value;
            }

            try {
                return $container->getParameter(trim($value, '%'));
            } catch (ParameterNotFoundException $e) {
                return $value;
            }
        };

        $processorClass = $container->getDefinition($processorServiceId)->getClass();
        if (false == class_exists($processorClass)) {
            throw new \LogicException(sprintf('The class "%s" could not be found.', $processorClass));
        }

        $defaultQueueName = $resolve($container->getParameter('enqueue.client.default_queue_name'));
        $subscriptionPrototype = [
            'topicName' => null,
            'queueName' => null,
            'queueNameHardcoded' => false,
            'processorName' => null,
            'exclusive' => false,
        ];

        $data = [];
        if (is_subclass_of($processorClass, CommandSubscriberInterface::class)) {
            /** @var CommandSubscriberInterface $processorClass */
            $params = $processorClass::getSubscribedCommand();
            if (is_string($params)) {
                if (empty($params)) {
                    throw new \LogicException('The processor name (it is also the command name) must not be empty.');
                }

                $data[] = [
                    'topicName' => Config::COMMAND_TOPIC,
                    'queueName' => $defaultQueueName,
                    'queueNameHardcoded' => false,
                    'processorName' => $params,
                ];
            } elseif (is_array($params)) {
                $params = array_replace($subscriptionPrototype, $params);
                if (false == $processorName = $resolve($params['processorName'])) {
                    throw new \LogicException('The processor name (it is also the command name) must not be empty.');
                }

                $data[] = [
                    'topicName' => Config::COMMAND_TOPIC,
                    'queueName' => $resolve($params['queueName']) ?: $defaultQueueName,
                    'queueNameHardcoded' => $resolve($params['queueNameHardcoded']),
                    'processorName' => $processorName,
                    'exclusive' => array_key_exists('exclusive', $params) ? $params['exclusive'] : false,
                ];
            } else {
                throw new \LogicException(sprintf(
                    'Command subscriber configuration is invalid. "%s"',
                    json_encode($processorClass::getSubscribedCommand())
                ));
            }
        }

        if (is_subclass_of($processorClass, TopicSubscriberInterface::class)) {
            /** @var TopicSubscriberInterface $processorClass */
            foreach ($processorClass::getSubscribedTopics() as $topicName => $params) {
                if (is_string($params)) {
                    $data[] = [
                        'topicName' => $params,
                        'queueName' => $defaultQueueName,
                        'queueNameHardcoded' => false,
                        'processorName' => $processorServiceId,
                    ];
                } elseif (is_array($params)) {
                    $params = array_replace($subscriptionPrototype, $params);

                    $data[] = [
                        'topicName' => $topicName,
                        'queueName' => $resolve($params['queueName']) ?: $defaultQueueName,
                        'queueNameHardcoded' => $resolve($params['queueNameHardcoded']),
                        'processorName' => $resolve($params['processorName']) ?: $processorServiceId,
                    ];
                } else {
                    throw new \LogicException(sprintf(
                        'Topic subscriber configuration is invalid. "%s"',
                        json_encode($processorClass::getSubscribedTopics())
                    ));
                }
            }
        }

        if (false == (
            is_subclass_of($processorClass, CommandSubscriberInterface::class) ||
            is_subclass_of($processorClass, TopicSubscriberInterface::class)
        )) {
            foreach ($tagAttributes as $tagAttribute) {
                $tagAttribute = array_replace($subscriptionPrototype, $tagAttribute);

                if (false == $tagAttribute['topicName']) {
                    throw new \LogicException(sprintf('Topic name is not set on message processor tag but it is required. Service %s', $processorServiceId));
                }

                $data[] = [
                    'topicName' => $resolve($tagAttribute['topicName']),
                    'queueName' => $resolve($tagAttribute['queueName']) ?: $defaultQueueName,
                    'queueNameHardcoded' => $resolve($tagAttribute['queueNameHardcoded']),
                    'processorName' => $resolve($tagAttribute['processorName']) ?: $processorServiceId,
                    'exclusive' => Config::COMMAND_TOPIC == $resolve($tagAttribute['topicName']) &&
                        array_key_exists('exclusive', $tagAttribute) ? $tagAttribute['exclusive'] : false,
                ];
            }
        }

        return $data;
    }
}
