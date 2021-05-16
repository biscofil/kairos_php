<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;

/**
 * Class P2PMessageResponse
 * @package App\P2P\Messages
 * @property \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response  $rawHttpResponse;
 * @property PeerServer $requestDestination
 * @property PeerServer $requestSender
 */
abstract class P2PMessageResponse extends P2PMessage
{

    public $rawHttpResponse = null;
    public PeerServer $requestDestination;
    public PeerServer $requestSender;

    /**
     * @param PeerServer $requestDestination
     * @param PeerServer $requestSender
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender)
    {
        $this->requestDestination = $requestDestination;
        $this->requestSender = $requestSender;
    }

    // #####################################################################

    /**
     * Serialize response to be sent back to the request sender
     * this code is executed by the request destination
     * @return array
     */
    abstract public function serialize(): array;

    /**
     * Unserialize the response sent back from the request destination
     * this code is executed by the request sender
     * @param PeerServer $requestDestination
     * @param array $messageData
     * @param $requestMessage
     * @return static
     */
    abstract public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): self;

    // #####################################################################

    /**
     * Code executed by the request sender when the response is received
     * @param \App\Models\PeerServer $destPeerServer
     * @param $request
     * @return void
     */
    abstract public function onResponseReceived(PeerServer $destPeerServer, $request): void;

    /**
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $httpJsonResponse
     */
    public function setRawHttpResponse($httpJsonResponse)
    {
        $this->rawHttpResponse = $httpJsonResponse;
    }

}
