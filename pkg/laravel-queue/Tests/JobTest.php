<?php

namespace Enqueue\LaravelQueue\Tests;

use Enqueue\LaravelQueue\Job;
use Enqueue\Test\ClassExtensionTrait;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as BaseJob;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsJobContract()
    {
        $this->assertClassExtends(JobContract::class, Job::class);
    }

    public function testShouldExtendsBaseQueue()
    {
        $this->assertClassExtends(BaseJob::class, Job::class);
    }
}
