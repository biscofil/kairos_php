<?php


namespace App\P2P\Messages\Heartbeat;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;

class HeartBeatRequest extends P2PMessageRequest
{

    public function __construct(PeerServer $requestSender, PeerServer $requestDestinations)
    {
        parent::__construct($requestSender, [$requestDestinations]);
    }

    public static function getRequestMessageName(): string
    {
        return 'heart_beat_request';
    }

    public function serialize(PeerServer $to): array
    {
        return [];
    }

    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        return new self($sender, getCurrentServer());
    }

    public function onRequestReceived(): P2PMessageResponse
    {
        return new HeartBeatResponse(
            getCurrentServer(),
            $this->requestSender
        );
    }

    public static function getResponseClass(): string
    {
        return HeartBeatResponse::class;
    }
}
