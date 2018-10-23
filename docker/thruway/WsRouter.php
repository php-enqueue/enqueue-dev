<?php

require '../../vendor/autoload.php';

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

$router = new Router();

$transportProvider = new RatchetTransportProvider('0.0.0.0', 9090);

$router->addTransportProvider($transportProvider);

$router->start();
