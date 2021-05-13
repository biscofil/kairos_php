<?php


namespace App\P2P\Messages;

use App\Jobs\SendP2PMessage;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze1IAmFreezingElection;
use App\P2P\Messages\Freeze\Freeze3CommitFail;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze;
use App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;
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
 */
abstract class P2PMessage
{
    use SerializesModels;

    public PeerServer $from;
    public array $to;

    /**
     * @return string
     */
    abstract public static function getMessageName() : string;

    // register here all the messages
    public static array $messageClasses = [
        AddMeToYourPeers::class,
        //
        WillYouBeAElectionTrusteeForMyElection::class,
        // freeze
        Freeze1IAmFreezingElection::class,
        ThisIsMyThresholdBroadcast::class,
        Freeze2IAmReadyForFreeze::class,
        Freeze3CommitFail::class,
        //
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
     * @param string $message
     * @return \App\P2P\Messages\P2PMessage|object
     * @throws \ReflectionException
     * @throws \Exception
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    final public static function getNewMessageObject(string $message): P2PMessage
    {
        $className = self::getClass($message);

        $r = new ReflectionClass($className);
        /** @var static $instance */
        return $r->newInstanceWithoutConstructor();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\PeerServer
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function getAuthPeer(Request $request): PeerServer
    {
        if (!auth('peer_api')->check()) {
            throw new AuthenticationException('Unauthenticated.');
        }
        return $request->user('peer_api'); // todo check
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
            if ($messageClass::getMessageName() === $message) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $messageClass;
            }
        }
        Log::error("Unknown message name $message");
        throw new Exception("Unknown message name $message");
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
     */
    final public function run(): void
    {

        Log::debug('class : ' . get_called_class());

        $messageName = static::getMessageName();

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
