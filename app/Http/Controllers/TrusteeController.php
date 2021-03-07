<?php

namespace App\Http\Controllers;

use App\Crypto\EGPublicKey;
use App\Models\Election;
use App\Models\Trustee;
use App\Models\User;
use App\Rules\ValidPublicKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

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
     * @throws \Exception
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
        $election->load('trustees.user');
        return $election;
    }

    /**
     * @param Election $election
     * @param Request $request
     * @return Trustee|Trustee[]|Response|Collection
     */
    public function store(Election $election, Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ]);

        /** @var User $user */
        $user = User::query()->where('email', '=', $data['email'])->firstOrFail();

        $election->createTrustee($user);

        return $election->trustees;
    }

    /**
     * @param Election $election
     * @return Trustee|Trustee[]|Response
     */
    public function store_system_trustee(Election $election)
    {

        $election->createSystemTrustee();

        return $election->trustees;
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
     * @return Response
     */
    public function destroy(Election $election, Trustee $trustee)
    {

        $trustee->delete();

        return $election->trustees;
    }
}
