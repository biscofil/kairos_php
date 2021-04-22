<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use App\Models\PeerServer;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
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
    use SerializesModels;

    protected PeerServer $from;
    protected array $to;

    // register here all the messages
    private static array $messageClasses = [
        AddMeToYourPeers::class,
        WillYouBeAElectionTrusteeForMyElection::class,
        IFrozeMyElection::class,
        IReceivedTheseVotes::class,
        GiveMeYourMixSet::class,
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
    private static function getClass(string $message): string
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
    abstract public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage;

    /**
     * Sends the message in a blocking way, should only be called by queues and not during requests
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        return [];
    }

    /**
     * @return
     */
    abstract public function getRequestResponse();

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     * @return void
     */
    abstract protected function onResponseReceived(PeerServer $destPeerServer, $response): void;

    /**
     * All messages have been sent
     */
    protected function afterMessagesSent(){

    }

    // #############################################################

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     * @throws ReflectionException
     */
    final public function run(): void
    {

        Log::debug('class : ' . get_called_class());

        $messageName = (new ReflectionClass(get_called_class()))->getConstant('name');

        foreach ($this->to as $destPeerServer) { // foreach destination server

            $url = 'https://' . $destPeerServer->domain . '/api/p2p/' . $messageName;

            if ($destPeerServer->domain == PeerServer::me()->domain) {
                Log::error('CANT SENT A MESSAGE TO YOURSELF : ' . $url);
                continue;
            }

            Log::debug('Sending a message to ' . $url);
            websocketLog('Sending a message to ' . $url);

            $data = $this->getRequestData($destPeerServer);

            try {

                $response = Http::withOptions([
                    'verify' => false, // TODO remove
                ])
                    ->withToken($destPeerServer->token ?? '')
                    ->post($url, $data);

                // ############################################## Response

                Log::debug('I received a response with status ' . $response->status());

                $this->onResponseReceived($destPeerServer, $response);

            } catch (Exception $e) {
                Log::error("Error sending  $url : " . $e->getMessage());
                Log::debug($e->getFile() . ' @ line ' . $e->getLine());
                Log::debug($e->getTraceAsString());
            }

        }

        $this->afterMessagesSent();

    }

    /**
     * @return mixed
     */
    final public function sendSync(): void
    {
        SendP2PMessage::dispatchSync($this);
    }

    /**
     * @return mixed
     */
    final public function sendAsync(int $seconds = 0): void
    {
        SendP2PMessage::dispatch($this)
            ->delay(now()->addSeconds($seconds));
    }
}
