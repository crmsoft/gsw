<?php

namespace App\Websocket;


use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;

use JWTAuth;

use App\User;
use App\Websocket\Controllers\StatusController;
use App\Websocket\Controllers\ChatMessageController;

class SocketHandler implements WebSocketHandlerInterface
{
    public function __construct()
    {
    }

    /**
     * "onOpen" listener.
     *
     * @param int $fd
     * @param \Illuminate\Http\Request $request
     */
    public function onOpen(Server $server, Request $request) {
        echo "\n connection \n";
        try{
            $fd = $request->fd;
            // this will set the token on the object
            JWTAuth::parseToken();
            // and you can continue to chain methods
            $user = JWTAuth::parseToken()->authenticate();
            auth()->loginUsingId($user->id);

            $token = str_random(31);
            $server->push($fd, json_encode([
                'token' => $token,
                'action' => 'auth'
            ]));

            SocketPool::push($user, $fd, $token);
            StatusController::userOnline($server, $user);
        } catch (\Exception $e) {
            echo $e->getMessage()."\n";
            $server->close($fd, false);
        }
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     *
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Server $server, Frame $frame) {
        if ($requestData = Abc::validate($frame->data)) {
            if ($requestData->getAction() == 'message') {
                ChatMessageController::handle(
                    $server, $requestData->getPayload()
                );
            } elseif ($requestData->getAction() == 'find-dudes-message') {
                \App\Websocket\Controllers\FindDudesController::notifyRoom(
                    $server, $requestData->getPayload()
                );
            } // else if
        } // end if
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, $fd, $reactorId) {

        if ($user_id = SocketPool::find($fd)) {
            if (SocketPool::pop($user_id, $fd)) {
                swoole_timer_after(config('app.user-left-timeout'), function ($user, $server) {
                    StatusController::userOffline($server, $user);
                }, $user_id);
            } // end if            
        } // end if
    }
}
