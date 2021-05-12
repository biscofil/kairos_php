<?php


namespace App\Http\Controllers;

use App\Models\PeerServer;
use App\P2P\Messages\P2PMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class P2PController
 * @package App\Http\Controllers
 */
class P2PController extends Controller
{

    /**
     * @return array
     */
    public function list_peers(): array
    {
        return PeerServer::all()->map(function (PeerServer $server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'gps' => $server->gps,
            ];
        })->toArray();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function add_peer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'active_url']
        ]);
        $peerServer = PeerServer::addPeer($data['domain']);
        return response()->json(['peer' => $peerServer]);
    }

    /**
     * @param Request $request
     * @param string $message
     * @return JsonResponse
     */
    public function receive(Request $request, string $message): JsonResponse
    {
        try {

            // get message class
            $instance = P2PMessage::getNewMessageObject($message);

            // check auth / get user
            $senderPeer = $instance->getAuthPeer($request);

            websocketLog("$message request received from $senderPeer->domain");

            // instanciate it
            $messageObj = $instance->fromRequest($senderPeer, $request->all());

            // call onRequestReceived method
            return $messageObj->getRequestResponse();

        } catch (\Exception $e) {

            Log::debug('Responding with error');
            Log::error($e->getMessage());

            return response()->json([
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);

        }
    }

}
