<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalConnectionFactory;

trait CreateDbalContextTrait
{
    protected function createDbalContext()
    {
        if (false == $env = getenv('DOCTINE_DSN')) {
            $this->markTestSkipped('The DOCTRINE_DSN env is not available. Skip tests');
        }

        $factory = new DbalConnectionFactory($env);

        $context = $factory->createContext();
        $context->getDbalConnection()->getSchemaManager()->dropTable($context->getTableName());
        $context->createDataBaseTable();

        return $context;
    }
}
