<?php 

namespace App\Websocket;

use Swoole\Coroutine\Redis;
use App\Websocket\Controllers\ChatMessageController;
use App\Websocket\Controllers\NotificationController;
use App\Websocket\Controllers\FindDudesController;
use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Swoole\Http\Server;

class AppConnector implements WorkerStartInterface {

    public function __construct()
    {
    }

    public function handle(Server $server, $worker_id)
    {
        go(function () use ($server) {
            $redis = new Redis();
            $redis->connect(config('database.redis.default.host'), config('database.redis.default.port'));
            
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
                            ChatMessageController::viewed($server, $payload);
                        } elseif ($payload['action'] == 'notification') {
                            NotificationController::notifyNotification($server, $payload['target']);
                        } elseif ($payload['action'] == 'sub-find-dudes') {
                            FindDudesController::pushToRoom($payload);
                        } elseif ($payload['action'] == 'unsub-find-dudes') {
                            FindDudesController::popFromRoom($payload);
                        } // end if
                    } 
                }
            }
        });
    }
}