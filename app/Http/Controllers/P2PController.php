<?php


namespace App\Http\Controllers;

use App\P2P\Messages\P2PMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class P2PController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function receive(Request $request): JsonResponse
    {

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $result = P2PMessage::parse($data['message'], $request);

        return response()->json([
            "server" => config('app.url'),
            "message" => $data['message'],
            "data_sent" => $data,
            "result" => $result
        ]);

    }

}
