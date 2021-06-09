<?php


namespace App\P2P;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;

abstract class P2PTransport
{

    public const MessageUUIDKey = '__message_uuid__';
    public const RequestSignature = '__request_signature__';

    abstract public static function loop(): void;


    /**
     * @throws \App\Exceptions\SendingMessageToSelf
     * @throws \Exception
     */
    abstract public static function sendRequest(PeerServer $destPeerServer, P2PMessageRequest $requestMessage);

    /**
     * @param $request
     * @return mixed
     */
    abstract public static function getClientPeer($request);

    abstract public static function onRequestReceived($request, string $messageName);

//    abstract public static function sendResponse($request);
}
