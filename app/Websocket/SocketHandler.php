<?php

namespace App\Websocket;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use Swoole\Websocket\Frame;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Server\Facades\Server;

use JWTAuth;

use App\User;
use App\Websocket\Controllers\StatusController;
use App\Websocket\Controllers\ChatMessageController;

class SocketHandler implements HandlerContract
{
    /**
     * "onOpen" listener.
     *
     * @param int $fd
     * @param \Illuminate\Http\Request $request
     */
    public function onOpen($fd, Request $request) {
        echo "\n connection \n";
        try{
            // this will set the token on the object
            JWTAuth::parseToken();
            // and you can continue to chain methods
            $user = JWTAuth::parseToken()->authenticate();
            auth()->loginUsingId($user->id);

            $token = str_random(31);
            App::make(Server::class)->push($fd, json_encode([
                'token' => $token,
                'action' => 'auth'
            ]));

            SocketPool::push($user, $fd, $token);
            StatusController::userOnline($user);
        } catch (\Exception $e) {
            echo $e->getMessage()."\n";
            App::make(Server::class)->close($fd, false);
        }
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     *
     * @param \Swoole\Websocket\Frame $frame
     */
    public function onMessage(Frame $frame) {
        echo 'message received';
    }

    /**
     * "onClose" listener.
     *
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId) {

        if ($user_id = SocketPool::find($fd)) {
            if (SocketPool::pop($user_id, $fd)) {
                swoole_timer_after(config('app.user-left-timeout'), function ($user) {
                    StatusController::userOffline($user);
                }, $user_id);
            } // end if            
        } // end if
    }
}
