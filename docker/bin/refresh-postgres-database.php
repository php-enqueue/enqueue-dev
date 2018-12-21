<?php

use Enqueue\Dbal\DbalConnectionFactory;

require_once getcwd().'/vendor/autoload.php';

$dsn = getenv('DOCTRINE_POSTGRES_DSN');

$dbalContext = (new DbalConnectionFactory($dsn))->createContext();

$dbalContext->getDbalConnection()->getSchemaManager()->dropAndCreateDatabase('postgres');
$dbalContext->createDataBaseTable();

echo 'Postgresql Database is updated'.PHP_EOL;
