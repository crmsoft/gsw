<?php

namespace App\Websocket;


use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;

use JWTAuth;

use App\User;
use App\Websocket\SocketAuth;
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
            SocketAuth::storeUser($fd, $user);

            $token = str_random(31);
            $server->push($fd, json_encode([
                'token' => $token,
                'action' => 'auth'
            ]));

            SocketPool::push($user, $fd, $token);
            StatusController::userOnline($user);
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

        // check for to many connections
        if (SocketPool::checkThrottle($frame->fd)) {
            $server->close($frame->fd, false);
            return;
        }

        if ($requestData = JSONRequest::validate($frame->data)) {
            if ($requestData->getAction() == 'message') {
                ChatMessageController::handle(
                    $requestData->getPayload(), $frame->fd
                );
            } elseif ($requestData->getAction() == 'find-dudes-message') {
                \App\Websocket\Controllers\FindDudesController::notifyRoom(
                    $requestData->getPayload(), $frame->fd
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
            SocketAuth::popUser($fd);
            if (SocketPool::pop($user_id, $fd)) {
                swoole_timer_after(config('app.user-left-timeout'), function ($user) {
                    StatusController::userOffline($user);
                }, $user_id);
            } // end if            
        } // end if
    }
}
