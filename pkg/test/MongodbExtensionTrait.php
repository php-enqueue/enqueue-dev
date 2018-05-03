<?php

namespace Enqueue\Test;

use Enqueue\Mongodb\MongodbConnectionFactory;

trait MongodbExtensionTrait
{
    protected function buildMongodbContext()
    {
        if (false == $env = getenv('MONGO_DSN')) {
            $this->markTestSkipped('The MONGO_DSN env is not available. Skip tests');
        }

        $factory = new MongodbConnectionFactory(['dsn' => $env]);

        $context = $factory->createContext();

        return $context;
    }
}
