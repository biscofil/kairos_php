<?php


namespace App\P2P;


use App\Exceptions\SendingMessageToSelf;
use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Class P2PHttp
 * @package App\P2P
 */
class P2PHttp
{

    /**
     * @throws \App\Exceptions\SendingMessageToSelf
     * @throws \Exception
     */
    public static function sendRequest(PeerServer $destPeerServer, P2PMessageRequest $requestMessage): P2PMessageResponse
    {

        $messageName = $requestMessage::getRequestMessageName();

        $url = 'https://' . $destPeerServer->domain . '/api/p2p/' . $messageName;

        if ($destPeerServer->domain == getCurrentServer()->domain) {

            Log::error('CANNOT SEND A MESSAGE TO YOURSELF : ' . $url);
            return $requestMessage->onRequestReceived($destPeerServer, $requestMessage); // TODO check!!!!
//            throw new SendingMessageToSelf();

        }

        websocketLog('Sending a message to ' . $url, $destPeerServer);

        $serializedRequest = $requestMessage->serialize($destPeerServer);

        try {

            $httpJsonResponse = Http::withOptions(
                ['verify' => false] // TODO remove
            )
                ->withToken($destPeerServer->token ?? '')
                ->post($url, $serializedRequest);

            if (!$httpJsonResponse->ok()) {
                Log::error($httpJsonResponse->json());
//                throw new \Exception('Expecting 200 response');
            }

            Log::debug('I received a response with status ' . $httpJsonResponse->status());

            // unserialize
            $responseType = $requestMessage::getResponseClass();
            $responseObject = $responseType::unserialize($destPeerServer, $httpJsonResponse->json(), $requestMessage);
            $responseObject->setRawHttpResponse($httpJsonResponse);
            return $responseObject;

        } catch (\Exception $e) {

            Log::error("Error sending  $e-> : " . $e->getMessage());
            Log::debug($e->getFile() . ' @ line ' . $e->getLine());
            Log::debug($e->getTraceAsString());

            throw $e;

        }

    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $messageName
     * @return \Illuminate\Http\JsonResponse
     */
    public static function onRequestReceived(Request $request, string $messageName): JsonResponse
    {

        $requestSigned = null;
        if ($request->has(self::MessageUUIDKey)) {
            // extract the UUID of the message
            $messageID = $request->get(self::MessageUUIDKey);
            $requestSigned = [
                'message_name' => $messageName,
                'data' => $request->all(),
                'message_uuid' => $messageID,
                'received_at' => Carbon::now()->toDateTimeString(),
            ];
            $requestSigned['signature'] = base64_encode(getCurrentServer()->jwt_secret_key->sign(json_encode($requestSigned)));
//            Log::debug($requestSigned);
        }

        try {

            // get message class
            $instance = P2PMessageRequest::getRequestObject($messageName);

            // check auth / get user
            $senderPeer = $instance->getAuthPeer($request);

            websocketLog("$messageName request received from $senderPeer->domain");

            // instanciate it
            $messageObj = $instance::unserialize($senderPeer, $request->all()); // TODO remove

            // TODO ($senderPeer, $request->all())

            // call onRequestReceived method
            $out = $messageObj->onRequestReceived()->serialize();

            websocketLog("responding to $messageName", $senderPeer);

            return response()->json($out);

        } catch (ValidationException $e) {

            Log::debug('ValidationException > Responding with error');
            Log::error($e->getMessage());

            return response()->json([
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'errors' => $e->errors()
            ], 402);

        } catch (Exception $e) {

            Log::debug('Exception > Responding with error');
            Log::error($e->getMessage());
            Log::error($e->getFile());
            Log::error($e->getLine());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace()
            ], 500);

        }
    }

}
