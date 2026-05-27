<?php

use Govorun\Http\Request;

$app = require __DIR__.'/../bootstrap/app.php';

$response = $app->handleWebhook(Request::capture());

http_response_code($response->status);
echo $response->body;