<?php 

namespace App\Websocket\Controllers;

use App\Websocket\Controllers\Controller;
use App\Entities\Conversation;
use App\Websocket\SocketPool;

class ChatMessageController implements Controller {
    public function __construct($data)
    {
        $user = auth()->user();

        $conversation = Conversation::where('hash_id', $data['requestData'])->first();
        
        if ($conversation && $conversation->isMember($user)) {
            $conversation->members->map(function ($member) use ($conversation, $user) {
                if ($member->user_communication_id > 0 && ($user->id != $member->id)) {
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
