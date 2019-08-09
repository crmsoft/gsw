<?php 

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;

class FindDudesController implements Controller {

    /**
     * Add user to dude room
     * 
     * @param array $details
     * 
     * @return void
     */
    public static function pushToRoom($details)
    {
        $fds = SocketPool::connections($details['user']);

        foreach ($fds as $fd) {
            if ($fd[1] == $details['page']) {
                
                // reset prev subscriptions
                foreach (Room::getRooms($fd[0]) as $room) {
                    Room::delete($fd[0], $room);
                } // end foreach

                // subscribe
                Room::add($fd[0], $details['target']);

                break;
            } // end if
        } // end foreach
    }

    /**
     * Check out from room
     * 
     * @param array
     * 
     * @return void
     */
    public static function popFromRoom($details)
    {
        $fds = SocketPool::connections($details['user']);

        foreach ($fds as $fd) {
            if ($fd[1] == $details['page']) {
                
                // reset prev subscriptions
                foreach (Room::getRooms($fd[0]) as $room) {
                    Room::delete($fd[0], $room);
                } // end foreach

                break;
            } // end if
        } // end foreach
    }

    /**
     * New staff on channel
     * 
     * @param array
     */
    public static function notifyRoom($data)
    {
        Websocket::broadcast()->to($data['requestData'])->emit('message', json_encode([
            'action' => 'channel-update',
            'target' => $data['requestData']
        ]));
    }

}
