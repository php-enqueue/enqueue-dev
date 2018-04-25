<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FsContextTest extends TestCase
{
    /**
     * @var FsContext|null
     */
    private $fsContext;

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/enqueue');
    }

    public function testShouldCreateFoldersIfNotExistOnConstruct()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/enqueue');

        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir().'/enqueue/dir/notexiststest']))->createContext();

        $this->assertDirectoryExists(sys_get_temp_dir().'/enqueue/dir/notexiststest');
    }
}
