<?php


namespace App\P2P\Messages;


use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Tasks\GenerateShadowMixes;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;

/**
 * Class ThisIsMyMixSet
 * @package App\P2P\Messages
 * @property array $mixSet
 */
class ThisIsMyMixSet extends P2PMessage
{

    public const name = 'this_is_my_mix_set';

    public $mixSet;

    // #######################################################################################
    // ##################################### REQUEST #########################################
    // #######################################################################################

    /**
     * ThisIsMyMixSet constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param array $ballots
     * @throws Exception
     */
    public function __construct(PeerServer $from, array $to, array $ballots)
    {
        parent::__construct($from, $to);
        $this->mixSet = $ballots;
    }

    /**
     * @param PeerServer $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(PeerServer $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'mix_set' => ['required', 'json']
        ])->validate();

        return new static(
            $sender,
            [self::me()],
            $data['mix_set']
        );
    }

    /**
     * @param \App\Models\PeerServer $to
     * @return array
     */
    public function getRequestData(PeerServer $to): array
    {
        return [
            'mix_set' => $this->mixSet,
        ];
    }

    // #######################################################################################
    // ##################################### RESPONSE ########################################
    // #######################################################################################

    /**
     * This code is for the server to which we are sending the request to
     * he has to respond with its public key
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        Log::debug("ThisIsMyMixSet message received");

        // TODO store received mixset

        /**
         * The destination server generates 80 challenge bits and returns it to the sender
         */

        return new JsonResponse([
            'challenge_bits' => BigInteger::random(80)->toBits()
        ]);

    }

    /**
     * We parse the public key and we assign it to the trustee
     * @param \App\Models\PeerServer $destPeerServer
     * @param \Illuminate\Http\Client\Response $response
     * @throws Exception
     */
    public function onResponseReceived(PeerServer $destPeerServer, Response $response): void
    {

        $response = Validator::make($response->json(), [
            'challenge_bits' => ['required', 'string']
        ])->validate();

        Log::error("Challenge bits received: " . $response['challenge_bits']);

        // TODO generate proof in task

        $election = Election::findOrFail($this->mixSet['id']);

        RunP2PTask::dispatch(new GenerateShadowMixes(
            $this->from,
            [$destPeerServer],
            $response['challenge_bits']
        ));

    }

}
