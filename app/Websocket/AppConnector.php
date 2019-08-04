<?php 

namespace App\Websocket;

use Swoole\Coroutine\Redis;
use App\Websocket\Controllers\ChatMessageController;
use App\Websocket\Controllers\NotificationController;

class AppConnector {

    public static function handle()
    {
        go(function () {
            $redis = new Redis();
            $redis->connect(config('database.redis.default.host'), config('database.redis.default.port'));
            
            //$value = $redis->incr('app_service_provider_connected');
            //if ($value % 2 == 0){return;}

            if ($redis->subscribe([config('app.pub-sub-channel')])) {
                while ($msg = $redis->recv()) {
                    list($type, $name, $info) = $msg;
                    if ($type == 'subscribe') {}
                    elseif ($type == 'unsubscribe' && $info == 0) {
                        break;
                    }
                    elseif ($type == 'message') {
                        print_r("\nTRIGGER REDIS\n");
                        $payload = json_decode($info, true);
                        if ($payload['action'] == 'message-read') {
                            ChatMessageController::viewed($payload);
                        } elseif ($payload['action'] == 'notification') {
                            NotificationController::notifyNotification($payload['target']);
                        } // end if
                    } 
                }
            }
        });
    }
}