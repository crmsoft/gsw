<?php 

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use App\Websocket\SocketPool;
use SwooleTW\Http\Websocket\Facades\Room;

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
        $server = app('swoole');
        // update on channel of participants
        foreach(Room::getClients($details['target']) as $fd) {
            $server->push($fd, json_encode([
                'channel' => $details['target'],
                'action' => 'new-participant'
            ]));
        } // end foreach

        $fds = SocketPool::connections($details['user']);

        foreach ($fds as $fd) {
            if ($fd[1] == $details['page']) {
                
                // reset prev subscriptions
                foreach (Room::getRooms($fd[0]) as $room) {
                    Room::delete($fd[0], $room);

                    // update on channel of participants
                    foreach(Room::getClients($room) as $room_member_fd) {
                        $server->push($room_member_fd, json_encode([
                            'channel' => $details['target'],
                            'action' => 'pop-participant'
                        ]));
                    } // end foreach
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
        $server = app('swoole');
        // update on channel of participants
        foreach(Room::getClients($details['target']) as $fd) {
            $server->push($fd, json_encode([
                'channel' => $details['target'],
                'action' => 'pop-participant'
            ]));
        } // end foreach

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
     * @param string channel
     * @param int fd
     * 
     * @return void
     */
    public static function notifyRoom($data, $emit_fd)
    {
        $server = app('swoole');
        foreach (Room::getClients($data) as $fd) {
            
            if ($fd == $emit_fd) {
                continue;
            } // end if
            
            try {
                $server->push($fd, json_encode([
                    'action' => 'channel-update',
                    'target' => $data
                ]));
            } catch (\Trowable $e) {
                Room::delete($fd, [$data]);
                echo "failed to send packet to $fd", PHP_EOL;
                echo $e->getMessage();
                echo PHP_EOL;
            }
            
        } // end foreach
    }

}
