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
    public function list(): array
    {
        $nodes = PeerServer::all()->map(function (PeerServer $server) {
            return [
                "id" => $server->id,
                "name" => $server->name,
                "gps" => $server->gps,
            ];
        })->toArray();
        $nodes[] = [
            "id" => "_me",
            "name" => parse_url(config('app.url'), PHP_URL_HOST),
            "gps" => [
                'coordinates' => [12.3155, 45.4408] // TODO
            ]
        ];
        return $nodes;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function receive(Request $request): JsonResponse
    {

        try {

            return P2PMessage::fromRequestData($request->all());

        } catch (\Exception $e) {

            Log::debug("Responding with error");
            Log::error($e->getMessage());
            return response()->json([
//                "request" => $data,
                "error_message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ], 500);

        }

    }

}
