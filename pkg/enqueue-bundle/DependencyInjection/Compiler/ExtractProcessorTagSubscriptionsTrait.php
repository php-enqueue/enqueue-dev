<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

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
        ];

        $data = [];
        if (is_subclass_of($processorClass, TopicSubscriberInterface::class)) {
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
        } else {
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
                ];
            }
        }

        return $data;
    }
}
