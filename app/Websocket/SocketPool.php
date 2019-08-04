<?php 


namespace App\Websocket;

use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Table\Facades\SwooleTable;

/**
 * Associate users with fd 
 * 
 * @method push - add connection to the user
 * @method pop - remove user connection
 * @method to - send to the user data
 */
final class SocketPool {

    /**
     * Create swoole table
     */
    public static function table()
    {
        return SwooleTable::get('users');
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

        return $cons;
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
        foreach (self::connections($user->id) as $fd) {
            print_r("\n $user->id , $fd \n");
            print_r(Websocket::broadcast()->to(
                $fd
            )->emit(
                'message',
                json_encode($message)
            ) ? 'true':'false');
        }
    }

    /**
     * Add user connection
     * 
     * @param User
     * @param int
     * 
     * @return void
     */
    final static public function push($user, $fd): void
    {
        print_r("\n new fd: $fd \n");
        $connections = self::connections($user->id);

        $connections[] = $fd;
        print_r("user: $user->id, cons: " . json_encode($connections) . "\n");

        $data = self::table()->get($user->id);
        $data['id'] = $user->id;
        $data['connections'] = implode(',', $connections);
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
        // get user current connections
        $connections = self::connections($user_id);
        // pick live connections
        $connections = collect($connections)->filter(function ($_fd) use ($fd) {
            return $_fd != $fd;
        });

        // get user current connections
        $data = self::table()->get($user_id);
        // set user new connections info
        $data['connections'] = implode(',', $connections->toArray());
        // remove from storage or store updated
        if (empty($data['connections'])) {
            self::table()->del($user_id);
            return true;
        } else {
            self::table()->set($user_id, $data);
        } // end if

        return false;
    }

}