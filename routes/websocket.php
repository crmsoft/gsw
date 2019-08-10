<?php


use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| Websocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register websocket events for your application.
|
*/

// Chat message handler
Websocket::on('message', 'App\Websocket\Controllers\ChatMessageController@handle');
// Find your dudes room message handler
Websocket::on('find-dudes-message', 'App\Websocket\Controllers\FindDudesController@notifyRoom');
