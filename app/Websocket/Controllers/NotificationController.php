<?php 

namespace App\Websocket\Controllers;


use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;
use App\User;

class NotificationController implements Controller {
    
    public static function notifyNotification($server, $user_id)
    {
        $user = User::find($user_id);

        if ($user) {
            SocketPool::to($server, $user, [
                'target' => null,
                'action' => 'notification'
            ]);
        } // end if
    }
} 
