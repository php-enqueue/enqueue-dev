#!/usr/bin/env php
<?php
namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

require __DIR__.'/../../../../../vendor/autoload.php';

$kernel = new AppKernel('test', true);
(new Application($kernel))->run(new ArgvInput());
