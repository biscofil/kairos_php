<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionException;

/**
 * Class P2PMessage
 * @package App\P2P\Messages
 * @property string $from
 * @property string[] $to // TODO allow multicast
 * @property string $name
 */
abstract class P2PMessage
{

    protected string $from;
    protected array $to;

    /**
     * @param string $from
     * @param string[]|string $to
     * @throws Exception
     */
    public function __construct(string $from, $to)
    {
        $this->from = extractDomain($from);
        $this->to = array_map(function (string $domain) {
            return extractDomain($domain);
        }, (array)$to);
    }

    /**
     * @param array $messageData
     * @return JsonResponse
     * @throws ReflectionException
     * @throws ValidationException
     * @throws Exception
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static final function fromRequestData(array $messageData): JsonResponse
    {

        $data = Validator::make($messageData, [
            'message' => ['required', 'string'], // TODO check not self
            'sender' => ['required', 'string']
        ])->validate();

        $message = $data['message'];
        $className = self::getClass($message);

        $sender = extractDomain($data['sender']);

        $r = new ReflectionClass($className);
        /** @var static $instance */
        $instance = $r->newInstanceWithoutConstructor();
        $messageObj = $instance->fromRequest($sender, $messageData);
        return $messageObj->onRequestReceived();
    }

    /**
     * Returns the class given its name
     * @param string $message
     * @return string
     * @throws Exception
     */
    protected static function getClass(string $message): string
    {
        switch ($message) {
            case AddMeToYourPeers::name:
                return AddMeToYourPeers::class;
            // ###########################
            case WillYouBeAElectionTrusteeForMyElection::name:
                return WillYouBeAElectionTrusteeForMyElection::class;
            case ThisIsMyMixSet::name:
                return ThisIsMyMixSet::class;
            default:
                Log::error("Unknown message name $message");
                throw new Exception("Unknown message name $message");
        }
    }

    /**
     * @return string
     */
    protected static function me(): string
    {
        return config('app.url');
    }

    /**
     * @param string $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(string $sender, array $messageData): P2PMessage
    {
        return new static($sender, self::me());
    }

    /**
     * Sends the message in a blocking way, should only be called by queues and not during requests
     * @param string $to
     * @return array
     */
    public function getRequestData(string $to): array
    {
        return [];
    }

    // #############################################################

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     * @throws ReflectionException
     */
    public final function sendSync(): void
    {

        $messageName = (new ReflectionClass(get_class($this)))->getConstant('name');

        $result = [];

        foreach ($this->to as $destPeerServer) { // foreach destination server

            $url = "https://" . $destPeerServer . '/api/p2p';

            Log::debug("Sending a message to " . $url);

            $data = array_merge_recursive($this->getRequestData($destPeerServer), [
                'sender' => "https://" . $this->from,
                'message' => $messageName // TODO check
            ]);

            $response = Http::withOptions([
                'verify' => false, // TODO remove
            ])->post($url, $data);  // ############################################## Response

            Log::debug("I received a response with status " . $response->status());

            $result[$destPeerServer] = $response->status();

            if ($response->status() >= 200 && $response->status() < 300) {

                $json = $response->json();
                Log::debug($json);

                try {
                    $this->onResponseReceived($destPeerServer, $response->json());
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                    Log::error($e->getFile());
                    Log::error($e->getLine());
                }

            } else {
                Log::error($response->body());
            }
        }

        /** @noinspection PhpParamsInspection */
        Log::debug($result);

    }

    /**
     * @return mixed
     */
    public final function sendAsync(): void
    {
        SendP2PMessage::dispatch($this);
    }

    // #############################################################
    // #############################################################

    // The output of onRequestReceived() should match the input of onResponseReceived();

    /**
     * @return JsonResponse
     */
    public abstract function onRequestReceived(): JsonResponse;

    /**
     * @param string $destPeerServer
     * @param array $data
     * @return void
     */
    public function onResponseReceived(string $destPeerServer, array $data): void
    {
    }

}
