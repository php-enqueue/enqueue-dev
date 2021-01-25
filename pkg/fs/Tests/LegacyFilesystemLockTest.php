<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsContext;
use Enqueue\Fs\LegacyFilesystemLock;
use Enqueue\Fs\Lock;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Makasim\File\TempFile;
use PHPUnit\Framework\TestCase;

class LegacyFilesystemLockTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementLockInterface()
    {
        $this->assertClassImplements(Lock::class, LegacyFilesystemLock::class);
    }

    public function testShouldReleaseAllLocksOnClose()
    {
        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);
        $fooQueue = $context->createQueue('foo');
        $barQueue = $context->createTopic('bar');

        new TempFile(sys_get_temp_dir().'/foo');
        new TempFile(sys_get_temp_dir().'/bar');

        $lock = new LegacyFilesystemLock();

        $this->assertAttributeCount(0, 'lockHandlers', $lock);

        $lock->lock($fooQueue);
        $this->assertAttributeCount(1, 'lockHandlers', $lock);

        $lock->release($fooQueue);
        $this->assertAttributeCount(1, 'lockHandlers', $lock);

        $lock->lock($barQueue);
        $lock->lock($fooQueue);
        $lock->lock($barQueue);

        $this->assertAttributeCount(2, 'lockHandlers', $lock);

        $lock->releaseAll();

        $this->assertAttributeCount(0, 'lockHandlers', $lock);
    }
}
