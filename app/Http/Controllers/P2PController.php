<?php


namespace App\Http\Controllers;

use App\Models\PeerServer;
use App\P2P\P2PHttp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'country_code' => $server->country_code,
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
        return P2PHttp::onRequestReceived($request, $message);
    }

}
