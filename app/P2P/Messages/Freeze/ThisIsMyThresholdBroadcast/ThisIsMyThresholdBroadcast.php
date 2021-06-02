<?php


namespace App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;


use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ThisIsMyThresholdBroadcast
{

    /**
     * @param \App\Models\Election $election
     * @return bool
     */
    public static function areAllSharesReceived(Election $election): bool
    {
        // the count of peer server trustees with missing share has to be zero
        return $election->trustees()
                ->whereNotNull('peer_server_id')
                ->where('peer_server_id', '<>', PeerServer::meID)
                ->where(function (Builder $query) {
                    return $query->whereNull('share_sent')
                        ->orWhereNull('share_received');
                })
                ->count() === 0;
    }

    /**
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public static function onAllSharesReceived(Election $election)
    {
        Log::debug('ThisIsMyThresholdBroadcast::onAllSharesReceived');

//        if ($this->broadcast->isValid($trusteeI->share_received, $j)) { // TODO
//            return new JsonResponse(['msg' => 'Great, valid polynomial']);
//        } else {
////            RunP2PTask::dispatch(new SendBroadcastComplaint($this->broadcast));
//            return new JsonResponse(['error' => 'I am about to broadcast complaint'], 400);
//        }

        SendP2PMessage::dispatch(
            new Freeze2IAmReadyForFreezeRequest(
                getCurrentServer(),
                $election->peerServerAuthor,
                $election,
                $election->trustees()->get()->toArray()
            )
        );

        // TODO if all requests received -> reply to coordinator
        //  if all shares are valid reply "OK" to coordinator
        //  else reply "FAIL" to coordinator
    }
}
