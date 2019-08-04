<?php

namespace App\Websocket;

use SwooleTW\Http\Websocket\Parser;


class SocketParser extends Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [
        // optional
    ];

    /**
     * Encode output message for websocket push.
     *
     * @return mixed
     */
    public function encode($event, $data)
    {
        print_r("encoding {$data} \n");
        return $data;
    }

    /**
     * Decode message from websocket client.
     * Define and return payload here.
     *
     * @param \Swoole\Websocket\Frame $frame
     * @return array
     */
    public function decode($frame)
    {
        if ($requestData = Request::validate($frame->data)) {
            return [
                'event' => $requestData->getAction(),
                'data' => [
                    'requestData' => $requestData->getPayload()
                ]
            ];
        } // end if

        print_r("invalid data received: {$frame->data}");
    }
}