<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\Models\User;
use App\Rules\ValidPublicKey;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Class TrusteeController
 * @package App\Http\Controllers
 */
class TrusteeController extends Controller
{

    /**
     * @param Election $election
     * @return array
     */
    public function trustee_home(Election $election): array
    {
        return [
            'election' => $election,
            'trustee' => $election->getAuthTrustee(),
        ];
    }

    /**
     * @param Election $election
     * @param Request $request
     * @throws Exception
     */
    public function upload_public_key(Election $election, Request $request)
    {

        $data = $request->validate([
            // validate public key with Proof
            'public_key_pok' => ['required', 'json', new ValidPublicKey()],
        ]);

        $public_key = EGPublicKey::fromArray($data['public_key_pok']['public_key']);

        $trustee = $election->getAuthTrustee();
        $trustee->setPublicKey($public_key);
    }

    /**
     * @param Election $election
     * @return Election
     */
    public function index(Election $election): Election
    {
        if (isLogged() && getAuthUser()->is_admin) {
            $election->load('trustees.user');
            $election->load('trustees.peerServer');
        }
        return $election;
    }

    /**
     * @param Election $election
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Election $election, Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['user', 'server'])],
            'email' => ['nullable', 'required_if:type,user', 'email', 'exists:users,email'],
            'peer_server_id' => ['nullable', 'required_if:type,server', 'numeric', 'exists:peer_servers,id']
        ]);

        if ($data['type'] === 'user') { // user

            /** @var User $user */
            $user = User::query()->where('email', '=', $data['email'])->firstOrFail();
            $election->createUserTrustee($user);

        } else { // peer server

            $server = PeerServer::findOrFail(intval($data['peer_server_id']));
            $election->createPeerServerTrustee($server);

        }

        return response()->json([
            'election' => [
                'trustees' => $election->trustees()->with(['user', 'peerServer'])->get(),
            ]
        ]);
    }

    /**
     * @param \App\Models\Election $election
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function threshold(Election $election, Request $request): JsonResponse
    {
        $data = $request->validate([
            'min_peer_count_t' => [
                'required',
                'integer',
                'min:1',
                'max:' . $election->peerServers()->count()
            ]
        ]);
        $election->min_peer_count_t = $data['min_peer_count_t'];
        $election->save();
        return response()->json([
            'election' => $election
        ]);

    }

    /**
     * @param Election $election
     * @param Trustee $trustee
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Election $election, Trustee $trustee): JsonResponse
    {

        $trustee->delete();

        return response()->json([
            'election' => [
                'trustees' => $election->trustees()->with(['user', 'peerServer'])->get(),
            ]
        ]);
    }
}
