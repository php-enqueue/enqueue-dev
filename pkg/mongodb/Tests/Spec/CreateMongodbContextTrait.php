<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbConnectionFactory;

trait CreateMongodbContextTrait
{
    protected function createMongodbContext()
    {
        if (false == $env = getenv('MONGO_DSN')) {
            $this->markTestSkipped('The MONGO_DSN env is not available. Skip tests');
        }

        $factory = new MongodbConnectionFactory(['uri' => $env]);

        $context = $factory->createContext();

        return $context;
    }
}
