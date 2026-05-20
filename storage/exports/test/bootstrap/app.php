<?php

use Govorun\Foundation\Application;

require __DIR__.'/../vendor/autoload.php';

$app = new Application(
    basePath: dirname(__DIR__)
);

return $app;
