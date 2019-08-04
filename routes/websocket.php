<?php


use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;
use App\Websocket\Controllers\ChatMessageController;

use Co\Redis;

$client = new Redis;
$client->connect('127.0.0.1', 6379);
// $client->subscribe('message');

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

Websocket::on('message', function ($websocket, $data) {
    $a = new ChatMessageController($data);
});
