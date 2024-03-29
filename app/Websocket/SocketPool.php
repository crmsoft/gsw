<?php 


namespace App\Websocket;

use SwooleTW\Http\Websocket\Facades\Room;

/**
 * Associate users with fd 
 * 
 * @method push - add connection to the user
 * @method pop - remove user connection
 * @method to - send to the user data
 * 
 * 0 => int fd
 * 1 => string page-id
 */
final class SocketPool {

    /**
     * Create swoole table
     */
    public static function table()
    {
        return app('swoole')->usersTable;
    }

    /**
     * Search user by fd
     * 
     * @param int $fd
     * 
     * @return int $user_id
     */
    public static function find($fd)
    {
        foreach (self::table() as $id => $data) {
            $conns = explode(',', $data['connections']);
            if (in_array($fd, $conns)) {
                return $id;
            } // end if
        } // end foreach

        return false;
    }

    /**
     * Get the server
     * 
     * @return SwooleWebsocket $server
     */
    public static function server()
    {
        return app('swoole');
    }

    /**
     * Get all available connections of the user
     * 
     * @param int
     * 
     * @return array
     */
    final public static function connections(int $user_id): array
    {
        // retrieve user information
        $data = self::table()->get($user_id);

        $cons = ($data ? explode(',', $data['connections']) : []);

        return array_map(function($conn) {
            return explode('|', $conn);
        }, $cons);
    }

    /**
     * Broadcast to user open socket connections
     * 
     * @param User
     * @param Array 
     * @return void 
     */
    final public static function to($user, array $message): void
    {
        // get server instance
        $server = self::server();
        // loop user connections
        foreach (self::connections($user->id) as $fd) {
            print_r("\n $user->id , {$fd[0]} \n");
            $server->push(
                $fd[0],
                json_encode($message)
            );
        }
    }

    /**
     * Add user connection
     * 
     * @param User $user
     * @param int $fd
     * @param string $token
     * 
     * @return void
     */
    final static public function push($user, $fd, string $token): void
    {
        print_r("\n new fd: $fd \n");
        $connections = self::connections($user->id);

        $connections[] = [$fd, $token];
        print_r("user: $user->id, cons: " . json_encode($connections) . "\n");

        $data = self::table()->get($user->id);
        $data['id'] = $user->id;
        $data['connections'] = implode(',', array_map(function($i){return "{$i[0]}|{$i[1]}";}, $connections));
        self::table()->set($user->id, $data);
    }

    /**
     * Remove user connection
     * 
     * @param int
     * @param int
     * 
     * @return bool
     */
    final static public function pop($user_id, $fd): bool
    {
        // pop user from rooms
        $rooms = Room::getRooms($fd);
        if (!empty($rooms)) {
            Room::delete($fd, $rooms);
        } // end if

        // get user current connections
        $connections = self::connections($user_id);
        // pick live connections
        $connections = collect($connections)->filter(function ($_fd) use ($fd) {
            return $_fd[0] != $fd;
        });

        // get user current connections
        $data = self::table()->get($user_id);
        // set user new connections info
        $data['connections'] = $connections->map(function($i) {return "{$i[0]}|$i[1]";})->implode(',');
        // remove from storage or store updated
        if (empty($data['connections'])) {
            self::table()->del($user_id);
            return true;
        } else {
            self::table()->set($user_id, $data);
        } // end if

        return false;
    }

    /**
     * Stop any continues requests to the server
     * Allows only one hit per second
     * 
     * @param int 
     * 
     * @return bool
     */
    public static function checkThrottle($fd)
    {
        $userHit = app('swoole')->userhitTable;
        $data = $userHit->get($fd);

        if ((time() - ($data['time'] ?? 0)) == 0) {
            $userHit->del($fd);
            return true;
        } // end if

        // update last hit
        $userHit->set($fd, ['time' => time()]);

        return false;
    }

}