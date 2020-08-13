<?php

namespace Enqueue\Dbal\Tests\Spec\Mysql;

use Enqueue\Dbal\DbalConnectionFactory;

trait CreateDbalContextTrait
{
    protected function createDbalContext()
    {
        if (false == $env = getenv('MYSQL_DSN')) {
            $this->markTestSkipped('The MYSQL_DSN env is not available. Skip tests');
        }

        $factory = new DbalConnectionFactory($env);

        $context = $factory->createContext();

        $context->dropDataBaseTable();

        $context->createDataBaseTable();

        return $context;
    }
}
