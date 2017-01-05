<?php

namespace Enqueue\Test;

trait RabbitmqManagmentExtensionTrait
{
    /**
     * @param string $queueName
     */
    private function removeQueue($queueName)
    {
        $rabbitmqHost = getenv('SYMFONY__RABBITMQ__HOST');
        $rabbitmqUser = getenv('SYMFONY__RABBITMQ__USER');
        $rabbitmqPassword = getenv('SYMFONY__RABBITMQ__PASSWORD');
        $rabbitmqVhost = getenv('SYMFONY__RABBITMQ__VHOST');

        $url = sprintf(
            'http://%s:15672/api/queues/%s/%s',
            $rabbitmqHost,
            urlencode($rabbitmqVhost),
            $queueName
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $rabbitmqUser.':'.$rabbitmqPassword);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type' => 'application/json',
        ]);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (false == in_array($httpCode, [204, 404], true)) {
            throw new \LogicException('Failed to remove queue. The response status is '.$httpCode);
        }
    }

    /**
     * @param string $exchangeName
     */
    private function removeExchange($exchangeName)
    {
        $rabbitmqHost = getenv('SYMFONY__RABBITMQ__HOST');
        $rabbitmqUser = getenv('SYMFONY__RABBITMQ__USER');
        $rabbitmqPassword = getenv('SYMFONY__RABBITMQ__PASSWORD');
        $rabbitmqVhost = getenv('SYMFONY__RABBITMQ__VHOST');

        $url = sprintf(
            'http://%s:15672/api/exchanges/%s/%s',
            $rabbitmqHost,
            urlencode($rabbitmqVhost),
            $exchangeName
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $rabbitmqUser.':'.$rabbitmqPassword);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type' => 'application/json',
        ]);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (false == in_array($httpCode, [204, 404], true)) {
            throw new \LogicException('Failed to remove queue. The response status is '.$httpCode);
        }
    }
}
