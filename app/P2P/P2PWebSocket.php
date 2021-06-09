<?php


namespace App\P2P;


use ApiClients\Client\Pusher\AsyncClient;
use ApiClients\Client\Pusher\Event;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use Illuminate\Support\Facades\Log;
use React\EventLoop\Factory;

class P2PWebSocket extends P2PTransport
{

    public static function loop(): void
    {
        $loop = Factory::create();

        $client = AsyncClient::create($loop,
            config('broadcasting.connections.pusher.key'),
            null,
            config('broadcasting.connections.pusher.options.cluster'));

        $client->channel('my-channel')->subscribe(
            function (Event $event) use ($client) { // Gets called for each incoming event
//                Log::debug('WS -> Channel: ' . $event->getChannel() . ' Event: ' . $event->getEvent());
//                Log::info('WS -> Data: ' . json_encode($event->getData()));
                self::onRequestReceived($event->getData(), $event->getEvent());
            },
            function ($e) { // Gets called on errors
                Log::error('WS -> ' . $e);
            },
            function () { // Gets called when the end of the stream is reached
                Log::warning('WS -> Done!');
            }
        );

        $loop->run();
    }

    public static function sendRequest(PeerServer $destPeerServer, P2PMessageRequest $requestMessage)
    {

        $loop = Factory::create();

        $client = AsyncClient::create($loop,
            config('broadcasting.connections.pusher.key'),
            null,
            config('broadcasting.connections.pusher.options.cluster'));

        /** @var string|P2PMessageRequest $requestClass */
        $requestClass = get_class($requestMessage);

        $client->send([
            'channel' => 'my-channel',
            'event' => $requestClass::getRequestMessageName(),
            'data' => $requestMessage->serialize($destPeerServer)
        ]);
    }

    public static function getClientPeer($request)
    {
        // TODO: Implement getClientPeer() method.
    }

    /**
     * @param array $request
     * @param string $messageName
     */
    public static function onRequestReceived($request, string $messageName)
    {

    }

}
