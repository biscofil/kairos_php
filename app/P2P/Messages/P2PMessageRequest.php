<?php


namespace App\P2P\Messages;

use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers\AddMeToYourPeersRequest;
use App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeRequest;
use App\P2P\Messages\Freeze\Freeze3CommitFail\Freeze3CommitFailRequest;
use App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcastRequest;
use App\P2P\Messages\GiveMeYourMixSet\GiveMeYourMixSetRequest;
use App\P2P\Messages\IReceivedTheseVotes\IReceivedTheseVotesRequest;
use App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection\WillYouBeAElectionTrusteeForMyElectionRequest;
use App\P2P\P2PHttp;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Class P2PMessageRequest
 * @package App\P2P\Messages
 * @property PeerServer $requestSender
 * @property PeerServer[] $requestDestinations
 */
abstract class P2PMessageRequest extends P2PMessage
{
    use SerializesModels;

    public PeerServer $requestSender;
    public array $requestDestinations;

    // register here all the messages
    public static array $requestMessages = [
        AddMeToYourPeersRequest::class,
        //
        WillYouBeAElectionTrusteeForMyElectionRequest::class,
        // freeze
        Freeze1IAmFreezingElectionRequest::class,
        ThisIsMyThresholdBroadcastRequest::class,
        Freeze2IAmReadyForFreezeRequest::class,
        Freeze3CommitFailRequest::class,
        //
        IReceivedTheseVotesRequest::class,
        GiveMeYourMixSetRequest::class,
    ];

    /**
     * @param PeerServer $requestSender
     * @param PeerServer[] $requestDestinations
     * @throws Exception
     */
    public function __construct(PeerServer $requestSender, array $requestDestinations)
    {
        $this->requestSender = $requestSender;
        $this->requestDestinations = $requestDestinations;
    }

    /**
     * @return string
     */
    abstract public static function getRequestMessageName(): string;

    /**
     * @param string $message
     * @return \App\P2P\Messages\P2PMessageRequest|object
     * @throws \ReflectionException
     * @throws \Exception
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    final public static function getRequestObject(string $message): P2PMessageRequest
    {
        $className = self::getRequestMessageClass($message);

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
     * TODO cache
     * Returns the class given its name
     * @param string $message
     * @return string
     * @throws Exception
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    private static function getRequestMessageClass(string $message): string
    {
        /** @var \App\P2P\Messages\P2PMessageRequest $messageClass */
        foreach (self::$requestMessages as $messageClass) {
            if ($messageClass::getRequestMessageName() === $message) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $messageClass;
            }
        }
        Log::error("Unknown message name $message");
        throw new Exception("Unknown message name $message");
    }

    // #############################################################

    /**
     * Serialize request to be sent to the request destination
     * this code is executed by the request sender
     * @param \App\Models\PeerServer $to
     * @return array
     */
    abstract public function serialize(PeerServer $to): array;

    /**
     * Unserialize the request sent from the request sender
     * this code is executed by the request destination
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    abstract public static function unserialize(PeerServer $sender, array $messageData): self;

    // #############################################################

    /**
     * Code excecuted by the request destination when the request arrives
     * @return \App\P2P\Messages\P2PMessageResponse
     */
    abstract public function onRequestReceived(): P2PMessageResponse;

    // #############################################################

    /**
     * @return string|\App\P2P\Messages\P2PMessageResponse
     */
    abstract public static function getResponseClass() : string;

    // #############################################################

    /**
     * Sends the message in a blocking way, should only be called by queues (and tasks) and not during requests
     */
    final public function send(): void
    {
        foreach ($this->requestDestinations as $destPeerServer) { // foreach destination server
            try {
                $response = P2PHttp::sendRequest($destPeerServer, $this);
                $response->onResponseReceived($destPeerServer, $this);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

}
