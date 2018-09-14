<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\CommandSubscriberInterface;
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

        $processorDefinition = $container->getDefinition($processorServiceId);
        $processorClass = $processorDefinition->getClass();
        if (false == $processorDefinition->getFactory() && false == class_exists($processorClass)) {
            throw new \LogicException(sprintf('The processor class "%s" could not be found.', $processorClass));
        }

        $defaultQueueName = $resolve($container->getParameter('enqueue.client.default_queue_name'));

        $data = [];
        if ($processorClass && is_subclass_of($processorClass, CommandSubscriberInterface::class)) {
            /** @var CommandSubscriberInterface $processorClass */
            $params = $processorClass::getSubscribedCommand();
            if (is_string($params)) {
                if (empty($params)) {
                    throw new \LogicException('The command name must not be empty.');
                }

                $data[] = [
                    'commandName' => $params,
                    'queueName' => $defaultQueueName,
                    'queueNameHardcoded' => false,
                    'processorName' => $processorServiceId,
                    'exclusive' => false,
                ];
            } elseif (is_array($params)) {
                if (empty($params['commandName'])) {
                    throw new \LogicException('The commandName must be set.');
                }

                if (false == $commandName = $resolve($params['commandName'])) {
                    throw new \LogicException('The commandName must not be empty.');
                }

                $data[] = [
                    'commandName' => $commandName,
                    'queueName' => $resolve($params['queueName'] ?? $defaultQueueName),
                    'queueNameHardcoded' => $params['queueNameHardcoded'] ?? false,
                    'processorName' => $params['processorName'] ?? $commandName,
                    'exclusive' => $params['exclusive'] ?? false,
                ];
            } else {
                throw new \LogicException(sprintf(
                    'Command subscriber configuration is invalid. "%s"',
                    json_encode($processorClass::getSubscribedCommand())
                ));
            }
        }

        if ($processorClass && is_subclass_of($processorClass, TopicSubscriberInterface::class)) {
            /** @var TopicSubscriberInterface $processorClass */
            $topics = $processorClass::getSubscribedTopics();
            if (!is_array($topics)) {
                throw new \LogicException(sprintf(
                    'Topic subscriber configuration is invalid for "%s::getSubscribedTopics()": expected array, got %s.',
                    $processorClass,
                    gettype($topics)
                ));
            }

            foreach ($topics as $topicName => $params) {
                if (is_string($params)) {
                    $data[] = [
                        'topicName' => $params,
                        'queueName' => $defaultQueueName,
                        'queueNameHardcoded' => false,
                        'processorName' => $processorServiceId,
                        'exclusive' => false,
                    ];
                } elseif (is_array($params)) {
                    $data[] = [
                        'topicName' => $topicName,
                        'queueName' => $resolve($params['queueName'] ?? $defaultQueueName),
                        'queueNameHardcoded' => $params['queueNameHardcoded'] ?? false,
                        'processorName' => $resolve($params['processorName']) ?: $processorServiceId,
                        'exclusive' => false,
                    ];
                } else {
                    throw new \LogicException(sprintf(
                        'Topic subscriber configuration is invalid for "%s::getSubscribedTopics()". "%s"',
                        $processorClass,
                        json_encode($processorClass::getSubscribedTopics())
                    ));
                }
            }
        }

        if (false == (
            $processorClass ||
            is_subclass_of($processorClass, CommandSubscriberInterface::class) ||
            is_subclass_of($processorClass, TopicSubscriberInterface::class)
        )) {
            foreach ($tagAttributes as $tagAttribute) {
                if (empty($tagAttribute['commandName']) && empty($tagAttribute['topicName'])) {
                    throw new \LogicException('Either commandName or topicName attribute must be set');
                }

                $topicName = $tagAttribute['topicName'] ?? null;
                $commandName = $tagAttribute['commandName'] ?? null;

                if ($topicName) {
                    $data[] = [
                        'topicName' => $resolve($tagAttribute['topicName']),
                        'queueName' => $resolve($tagAttribute['queueName'] ?? $defaultQueueName),
                        'queueNameHardcoded' => $tagAttribute['queueNameHardcoded'],
                        'processorName' => $resolve($tagAttribute['processorName'] ?? $processorServiceId),
                        'exclusive' => false,
                    ];
                }

                if ($commandName) {
                    $data[] = [
                        'commandName' => $resolve($tagAttribute['commandName']),
                        'queueName' => $resolve($tagAttribute['queueName'] ?? $defaultQueueName),
                        'queueNameHardcoded' => $tagAttribute['queueNameHardcoded'],
                        'processorName' => $resolve($tagAttribute['processorName'] ?? $processorServiceId),
                        'exclusive' => $tagAttribute['exclusive'] ?? false,
                    ];
                }
            }
        }

        return $data;
    }
}
