<?php 

namespace App\Websocket\Controllers;


use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;
use App\User;

class NotificationController implements Controller {
    
    /**
     * Notification trigger like, like, share 
     * 
     * @param SwooleWebsocket $server
     * @param int $user_id
     * 
     * @return void
     */
    public static function notifyNotification($user_id)
    {
        $user = User::find($user_id);

        if ($user) {
            SocketPool::to($user, [
                'target' => null,
                'action' => 'notification'
            ]);
        } // end if
    }

    /**
     * User want to add a user to the friends
     * 
     * @param $data
     * 
     * @return void
     */
    public static function notifyUserSubscription($data)
    {
        $user = User::find($data['target']);

        if ($user) {
            SocketPool::to($user, [
                'target' => null,
                'action' => 'subscription'
            ]);
        } // end if
    }
} 
