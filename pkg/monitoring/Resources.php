<?php

namespace Enqueue\Monitoring;

final class Resources
{
    /**
     * @var array
     */
    private static $knownStorages = null;

    private function __construct()
    {
    }

    public static function getKnownSchemes(): array
    {
        $map = self::getKnownStorages();

        $schemes = [];
        foreach ($map as $storageClass => $item) {
            foreach ($item['schemes'] as $scheme) {
                $schemes[$scheme] = $storageClass;
            }
        }

        return $schemes;
    }

    public static function getKnownStorages(): array
    {
        if (null === self::$knownStorages) {
            $map = [];

            $map[WampStorage::class] = [
                'schemes' => ['wamp', 'ws'],
                'supportedSchemeExtensions' => [],
            ];

            $map[InfluxDbStorage::class] = [
                'schemes' => ['influxdb'],
                'supportedSchemeExtensions' => [],
            ];

            $map[DatadogStorage::class] = [
                'schemes' => ['datadog'],
                'supportedSchemeExtensions' => [],
            ];

            self::$knownStorages = $map;
        }

        return self::$knownStorages;
    }
}
