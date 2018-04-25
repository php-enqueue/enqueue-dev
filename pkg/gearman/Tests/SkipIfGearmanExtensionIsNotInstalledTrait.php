<?php

namespace Enqueue\Gearman\Tests;

trait SkipIfGearmanExtensionIsNotInstalledTrait
{
    public function setUp()
    {
        if (false == class_exists(\GearmanClient::class)) {
            $this->markTestSkipped('The gearman extension is not installed');
        }

        parent::setUp();
    }
}
