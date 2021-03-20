<?php


namespace App\P2P\Messages;


/**
 * Class MessageReceived
 * @package App\P2P\Messages
 */
class MessageReceived extends P2PMessage
{

    protected $name = 'message_received';

    /**
     * @return array
     */
    public function getRequestData(): array
    {
       return [
           "result" => 1
       ];
    }

    /**
     * @return P2PMessage|void
     */
    public function onMessageReceived() : ?P2PMessage
    {
        return null;
    }
}
