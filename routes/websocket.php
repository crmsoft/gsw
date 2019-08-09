<?php


use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;
use App\Websocket\Controllers\ChatMessageController;
use App\Websocket\Controllers\FindDudesController;

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

Websocket::on('find-dudes-message', function ($websocket, $data) {
    FindDudesController::notifyRoom($data);
});
