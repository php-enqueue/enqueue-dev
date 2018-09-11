<?php

namespace Enqueue\Test;

use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\MongodbContext;

trait MongodbExtensionTrait
{
    protected function buildMongodbContext(): MongodbContext
    {
        if (false == $env = getenv('MONGO_DSN')) {
            $this->markTestSkipped('The MONGO_DSN env is not available. Skip tests');
        }

        $factory = new MongodbConnectionFactory(['dsn' => $env]);

        $context = $factory->createContext();

        return $context;
    }
}
