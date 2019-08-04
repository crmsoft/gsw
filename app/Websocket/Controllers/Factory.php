<?php 


namespace App\Websocket\Controllers;

use Controller;

class Factory {

    /**
     * Map actions to the controllers
     * 
     * @param $action
     * @return Controller
     */
    public static function getController(string $action) : Controller
    {
        switch($action) {
            case 'message' : return new ChatMessageController;
        }

        return new NotFoundController;
    }
}