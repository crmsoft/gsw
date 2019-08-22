<?php 

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;
use App\Websocket\SocketAuth;

class ChatMessageController implements Controller {
    
    /**
     * 
     * @param Websocket $websocket
     * @param array $data
     * 
     * @return void
     */
    final public static function handle($data, $fd) {
        $user_id = SocketAuth::getUser($fd);
        
        $conversation = Conversation::where('hash_id', $data)->first();
        
        if ($conversation && $conversation->isMember($user_id)) {
            $conversation->members->map(function ($member) use ($conversation, $user_id) {
                if ($member->user_communication_id > 0 && ($user_id != $member->id)) {
                    SocketPool::to($member, [
                        'target' => $conversation->hash_id,
                        'action' => 'message'
                    ]);
                } // end if
            });
        } // end if
    }

    final public static function viewed(array $payload): void {
        $conversation = Conversation::where('hash_id', $payload['target'])->first();

        if ($conversation) {
            $conversation->members()->whereIn('users.id', $payload['involved'])->get()->map(function ($user) use ($conversation) {
                SocketPool::to($user, [
                    'target' => $conversation->hash_id,
                    'action' => 'messages-viewed'
                ]);
            });
        } // end if
    }
} 
