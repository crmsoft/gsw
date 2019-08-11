<?php 

namespace App\Websocket;

class SocketAuth {

    /**
     * Access storage
     * 
     * 
     * @return Swoole\Table $table
     */
    private static function table()
    {
        return app('swoole')->uidsTable;
    }

    /**
     * Associate user with fd
     * 
     * @param int $fd
     * @param \App\User $user
     * 
     * @return void 
     */
    public static function storeUser($fd, $user)
    {
        self::table()->set(self::key($fd), ['id' => $user->id]);
    }

    /**
     * 
     * 
     * @param int $fd
     * 
     * @return bool|int
     */
    public static function getUser($fd)
    {
        $data = self::table()->get(
            self::key($fd)
        );


        return isset($data) && isset(
            $data['id']
        ) ? $data['id'] : false;
    }

    /**
     * Destroy user fd association
     * 
     * @param int $fd
     * 
     * @return bool
     */
    public static function popUser($fd)
    {
        return self::table()->del(self::key($fd));
    }

    /**
     * Generate unique key 
     * 
     * @param int $fd
     * 
     * @return string $key
     */
    private static function key($fd)
    {
        return "key.{$fd}";
    }
}