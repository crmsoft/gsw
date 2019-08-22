<?php 

namespace App\Websocket;

use Swoole\Coroutine\Redis;
use App\Websocket\Controllers\ChatMessageController;
use App\Websocket\Controllers\NotificationController;
use App\Websocket\Controllers\FindDudesController;
use Hhxsv5\LaravelS\Swoole\Events\WorkerStartInterface;
use Swoole\Http\Server;
use SwooleTW\Http\Websocket\Rooms\RoomContract;
use SwooleTW\Http\Websocket\Rooms\TableRoom;

class AppConnector implements WorkerStartInterface {

    public function __construct()
    {
    }

    public function handle(Server $server, $worker_id)
    {
        // laravel swoole room facades injection
        $app = app('app');
        $app->singleton(RoomContract::class, function () {
            $roomHandler = new TableRoom([
                'room_rows' => 4096,
                'room_size' => 2048,
                'client_rows' => 8192,
                'client_size' => 2048,
            ]);

            return $roomHandler->prepare();
        });

        $app->alias(RoomContract::class, 'swoole.room');

        go(function () {
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
                            ChatMessageController::viewed($payload);
                        } elseif ($payload['action'] == 'notification') {
                            NotificationController::notifyNotification($payload['target']);
                        } elseif ($payload['action'] == 'sub-find-dudes') {
                            FindDudesController::pushToRoom($payload);
                        } elseif ($payload['action'] == 'unsub-find-dudes') {
                            FindDudesController::popFromRoom($payload);
                        }  elseif ($payload['action'] == 'user-subscribed') {
                            NotificationController::notifyUserSubscription($payload);
                        } // end if // end if
                    } 
                }
            }
        });
    }
}