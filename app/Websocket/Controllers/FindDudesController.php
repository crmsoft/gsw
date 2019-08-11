<?php 

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Websocket\Rooms\TableRoom;

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
     * @param Swoole\WebSocket\Server
     * @param string channel
     * @param int fd
     * 
     * @return void
     */
    public static function notifyRoom($server, $data, $emit_fd)
    {
        foreach (Room::getClients($data) as $fd) {
            
            if ($fd == $emit_fd) {
                continue;
            } // end if
            
            $server->push($fd, json_encode([
                'action' => 'channel-update',
                'target' => $data
            ]));
        } // end foreach
    }

}
