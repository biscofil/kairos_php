<?php

namespace App\Http\Controllers;

use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Trustee;
use App\Models\User;
use App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection;
use App\Rules\ExistingPeerServer;
use App\Rules\ValidPublicKey;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            'email' => ['nullable', 'required_if:url,null', 'email', 'exists:users,email'],
            'url' => ['nullable', 'required_if:email,null', 'active_url', new ExistingPeerServer()] // TODO check valid url
        ]);

        if ($data['email']) { // user

            /** @var User $user */
            $user = User::query()->where('email', '=', $data['email'])->firstOrFail();
            $election->createUserTrustee($user);

        } else { // peer server

            $host = parse_url($data['url'], PHP_URL_HOST);

            /** @var PeerServer $server */
            $server = PeerServer::query()->where('ip', '=', $host)->firstOrFail();
            $election->createPeerServerTrustee($server);

            (new WillYouBeAElectionTrusteeForMyElection($election->toArray(), config('app.url'), $host))->sendAsync();

        }

        return response()->json([
            'election' => [
                'trustees' => $election->trustees()->with(['user', 'peerServer'])->get(),
                'has_system_trustee' => $election->has_system_trustee
            ]
        ]);
    }

    /**
     * @param Election $election
     * @return JsonResponse
     */
    public function store_system_trustee(Election $election): JsonResponse
    {

        $election->createSystemTrustee();

        return response()->json([
            'election' => [
                'trustees' => $election->trustees()->with(['user', 'peerServer'])->get(),
                'has_system_trustee' => $election->has_system_trustee
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Trustee $trustee
     * @return Response
     */
    public function update(Request $request, Trustee $trustee)
    {
        //
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
                'has_system_trustee' => $election->has_system_trustee
            ]
        ]);
    }
}
