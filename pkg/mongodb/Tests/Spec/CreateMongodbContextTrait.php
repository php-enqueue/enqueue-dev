<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbConnectionFactory;

trait CreateMongodbContextTrait
{
    protected function createMongodbContext()
    {
        if (false == $env = getenv('MONGO_CONNECTION_STRING')) {
            $this->markTestSkipped('The MONGO_CONNECTION_STRING env is not available. Skip tests');
        }

        $factory = new MongodbConnectionFactory($env);

        $context = $factory->createContext();

        //$context->getClient()->dropDatabase('enqueue');

        return $context;
    }
}
