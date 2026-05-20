<?php

use Govorun\Http\Request;

$app = require __DIR__.'/../bootstrap/app.php';

$app->handleWebhook(Request::capture());
