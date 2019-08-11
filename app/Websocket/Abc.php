<?php 

namespace App\Websocket;

class Abc {

    /**
     * Allowed actions list
     * 
     * @var array
     */
    private static $allowed = [
        'message', 'find-dudes-message'
    ];

    /**
     * Validate request
     * 
     * @return boolean|RequestData
     */
    public static function validate(string $payload)
    {
        $data = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($data['action']) && in_array($data['action'], self::$allowed)) {
                
                $requestData = new RequestData;
                $requestData->setAction($data['action']);
                $requestData->setPayload($data['data']);

                return $requestData;
            } // end if
        } // end if


        return false;
    }

} // end Request


class RequestData {

    /** @var string */
    private $action;
    /** @var string */
    private $payload;

    /**
     * Set action param
     * 
     * @param string
     * @return void
     */
    public function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * Set payload param
     * 
     * @param string
     * @return void
     */
    public function setPayload(string $payload)
    {       
        $this->payload = $payload;
    }

    /**
     * Get action
     * 
     * @return string
     */
    public function getAction() : string    
    {
        return $this->action;
    }

    /**
     * Get payload
     * 
     * @return string
     */
    public function getPayload() : string
    {
        return $this->payload;
    }
}
