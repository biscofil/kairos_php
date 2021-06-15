<?php


namespace App\P2P\Messages\Freeze\Freeze3CommitFail;


use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeResponse;
use App\P2P\Messages\P2PMessageRequest;
use App\P2P\Messages\P2PMessageResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Describes the third message of the three-phase-commit procedure for election freeze
 * Class FreezeCommitFail
 * @package App\P2P\Messages\Freeze\Freeze3CommitFail
 * @property Election $election
 * @property bool $commit
 * @property Collection|Trustee[] $trustees
 */
class Freeze3CommitFailRequest extends P2PMessageRequest
{

    public bool $commit;
    public Election $election;
    public Collection $trustees;

    /**
     * Freeze3CommitFailRequest constructor.
     * @param \App\Models\PeerServer $requestSender
     * @param \App\Models\PeerServer $requestDestinations
     * @param \App\Models\Election $election
     * @param bool $commit
     * @param \Illuminate\Support\Collection $trustees
     * @throws \Exception
     */
    public function __construct(PeerServer $requestSender, PeerServer $requestDestinations, Election $election, bool $commit, Collection $trustees)
    {
        parent::__construct($requestSender, [$requestDestinations]);
        $this->commit = $commit;
        $this->election = $election;
        $this->trustees = $trustees;
    }

    /**
     * @return string
     */
    public static function getRequestMessageName(): string
    {
        return 'freeze3_commit_fail_request';
    }

    /**
     * @return string
     */
    public static function getResponseClass(): string
    {
        return Freeze3CommitFailResponse::class;
    }

    // ###################################################################

    /**
     * @param \App\Models\PeerServer $to
     * @return bool[]
     */
    public function serialize(PeerServer $to): array
    {
        return [
            'election_uuid' => $this->election->uuid,
            'trustees' => $this->trustees->map(function (Trustee $trustee) {
                return [
                    'uuid' => $trustee->uuid,
                    'public_key' => $trustee->public_key->toArray(),
                ];
            })->toArray(),
            'commit' => $this->commit
        ];
    }

    /**
     * @param \App\Models\PeerServer $sender
     * @param array $messageData
     * @return \App\P2P\Messages\P2PMessageRequest
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public static function unserialize(PeerServer $sender, array $messageData): P2PMessageRequest
    {
        $data = Validator::make($messageData, [
            'election_uuid' => ['required', 'uuid'],

            'trustees' => ['required', 'array'],
            'trustees.*.uuid' => ['required', 'uuid'],
            'trustees.*.public_key' => ['required', 'array'],

            'commit' => ['required', 'bool'],
        ])->validate();

        $election = Election::findFromUuid($data['election_uuid']);

        $pkClass = $election->cryptosystem->getClass()::getPublicKeyClass();

        $commit = boolval($data['commit']);

        $trustees = $election->trustees->keyBy('uuid');
        $trustees = collect($data['trustees'])->map(function (array $trusteeData) use ($pkClass, $trustees) {
            /** @var Trustee $trustee */
            $trustee = $trustees->get($trusteeData['uuid']);
            $trustee->public_key = $pkClass::fromArray($trusteeData['public_key']);
            return $trustee;
        });

        return new static($sender, getCurrentServer(), $election, $commit, $trustees);
    }

    // ###################################################################

    /**
     * @return \App\P2P\Messages\P2PMessageResponse
     */
    public function onRequestReceived(): P2PMessageResponse
    {
        if ($this->commit) {

            $this->trustees->each(function (Trustee $trustee) {
                $trustee->save();
            });

            // do actual freeze
            $this->election->actualFreeze();
        } else {
            // undo freezing stuff
            $this->election->trustees()->each(function (Trustee $trustee) {
                $trustee->polynomial = null;
                $trustee->broadcast = null;
                $trustee->share_sent = null;
                $trustee->share_received = null;
                $trustee->save();
            });
        }

        return new Freeze2IAmReadyForFreezeResponse(getCurrentServer(), $this->requestSender);
    }
}
