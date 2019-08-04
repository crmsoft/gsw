<?php

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use SwooleTW\Http\Websocket\Facades\Websocket;
use App\Websocket\SocketPool;
use App\User;

class StatusController implements Controller {
    
    /**
     * User went offline
     * 
     * @param User
     * @return void
     */
    public static function userOffline($user)
    {
        if (count(SocketPool::connections($user)) == 0) {
            print_r("no connections found $user");
            // set user offline
            $user = User::find($user);
            $user->user_communication_id = null;
            $user->save();
            // notify online friends about user leave
            $user->friend->map(function ($friend) use ($user) {
                if ($friend->user_communication_id > 0) {
                    SocketPool::to($friend, [
                        'target' => $user->username,
                        'action' => 'offline'
                    ]);
                } // end if
            });
        } // end if
    }

    /**
     * User went online
     * 
     * @param User
     * @return void
     */
    public static function userOnline($user)
    {
        if ($user->user_communication_id == null) {
            
            $user->user_communication_id = 1;
            $user->save();

            $user->friend->map(function ($friend) use ($user) {
                if ($friend->user_communication_id > 0) {
                    SocketPool::to($friend, [
                        'target' => $user->username,
                        'action' => 'online'
                    ]);
                } // end if
            });

        } // end if
    }
}