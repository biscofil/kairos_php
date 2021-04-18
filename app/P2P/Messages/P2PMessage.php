<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use App\Models\PeerServer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionException;

/**
 * Class P2PMessage
 * @package App\P2P\Messages
 * @property PeerServer $from
 * @property PeerServer[] $to
 * @property string $name
 */
abstract class P2PMessage
{

    protected PeerServer $from;
    protected array $to;

    // register here all the messages
    private static array $messageClasses = [
        AddMeToYourPeers::class,
        WillYouBeAElectionTrusteeForMyElection::class,
        IFrozeMyElection::class,
        IReceivedTheseVotes::class,
        ThisIsMyMixSet::class,
    ];

    /**
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @throws Exception
     */
    public function __construct(PeerServer $from, array $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @param \App\Models\PeerServer $senderPeer
     * @param string $message
     * @param array $messageData
     * @return JsonResponse
     * @throws \ReflectionException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static final function fromRequestData(PeerServer $senderPeer, string $message, array $messageData): JsonResponse
    {

        $className = self::getClass($message);

        $r = new ReflectionClass($className);
        /** @var static $instance */
        $instance = $r->newInstanceWithoutConstructor();

        $messageObj = $instance->fromRequest($senderPeer, $messageData);
        return $messageObj->onRequestReceived();
    }

    /**
     * Returns the class given its name
     * @param string $message
     * @return string
     * @throws Exception
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    protected static function getClass(string $message): string
    {
        /** @var \App\P2P\Messages\P2PMessage $messageClass */
        foreach (self::$messageClasses as $messageClass) {
            if ($messageClass::name === $message) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $messageClass;
            }
        }
        Log::error("Unknown message name $message");
        throw new Exception("Unknown message name $message");
    }

    /**
     * TODO move to PeerServer class
     * @return PeerServer
     */
    public static function me(): PeerServer
    {
        return PeerServer::first(); // TODO check
    }

    /**
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public abstract static function fromRequest(PeerServer $sender, array $messageData): P2PMessage;

    /**
     * Sends the message in a blocking way, should only be called by queues and not during requests
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public abstract function getRequestData(PeerServer $to): array;

    // #############################################################

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     * @throws ReflectionException
     */
    public final function sendSync(): void
    {

        $messageName = (new ReflectionClass(get_class($this)))->getConstant('name');

//        $result = [];

        foreach ($this->to as $destPeerServer) { // foreach destination server

            $url = "https://" . $destPeerServer->ip . '/api/p2p/' . $messageName;

            Log::debug("Sending a message to " . $url);

            $data = $this->getRequestData($destPeerServer);

            /** @noinspection PhpParamsInspection */
            Log::debug($data);

            try {

                $response = Http::withOptions([
                    'verify' => false, // TODO remove
                ])->post($url, $data);  // ############################################## Response

                Log::debug("I received a response with status " . $response->status());

                $this->onResponseReceived($destPeerServer, $response);

            } catch (\Exception $e) {
                Log::error("Error sending  $url : " . $e->getMessage());
            }

        }

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
     * @param \App\Models\PeerServer $destPeerServer
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     */
    public function onResponseReceived(PeerServer $destPeerServer, \Illuminate\Http\Client\Response $response): void
    {
    }

}
