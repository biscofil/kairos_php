<?php


namespace App\P2P\Messages;


use App\Jobs\RunP2PTask;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Tasks\GenerateShadowMixes;
use Exception;
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
     * @param array $mixSet
     * @param string $from
     * @param string $to
     * @throws Exception
     */
    public function __construct(array $mixSet, string $from, string $to)
    {
        parent::__construct($from, $to);
        $this->mixSet = $mixSet;
    }

    /**
     * @param string $sender
     * @param array $messageData
     * @return static
     * @throws Exception
     */
    public static function fromRequest(string $sender, array $messageData): P2PMessage
    {
        $data = Validator::make($messageData, [
            'mix_set' => ['required', 'json']
        ])->validate();

        return new static(
            $data['mix_set'],
            $sender,
            config('app.url')
        );
    }

    /**
     * @param string $to
     * @return array
     */
    public function getRequestData(string $to): array
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
     * @param string $destPeerServer
     * @param array $data
     * @throws Exception
     */
    public function onResponseReceived(string $destPeerServer, array $data): void
    {

        $data = Validator::make($data, [
            'challenge_bits' => ['required', 'string']
        ])->validate();

        Log::error("Challenge bits received: " . $data['challenge_bits']);

        // TODO generate proof in task

        $server = PeerServer::withDomain($destPeerServer)->firstOrFail();
        $election = Election::findOrFail($this->mixSet['id']);

        RunP2PTask::dispatch(new GenerateShadowMixes(
            $this->from,
            [$destPeerServer],
            $data['challenge_bits']
        ));

    }

}
