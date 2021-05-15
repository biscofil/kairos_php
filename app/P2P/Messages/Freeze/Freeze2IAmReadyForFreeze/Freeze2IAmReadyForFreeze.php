<?php


namespace App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze;


use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze3CommitFail\Freeze3CommitFailRequest;
use Illuminate\Support\Facades\Log;

/**
 * Class Freeze2IAmReadyForFreeze
 * @package App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze
 */
class Freeze2IAmReadyForFreeze
{

    /**
     * @param \App\Models\Election $election
     * @return bool
     */
    public static function areAllPeersReady(Election $election): bool
    {
        // the count of peer server trustees with freeze_ready = false has to be zero
        return $election->trustees()
                ->whereNotNull('peer_server_id')
                ->where('peer_server_id', '<>', PeerServer::meID)
                ->where('freeze_ready', '=', false)
                ->count() === 0;
    }

    /**
     * @param \App\Models\Election $election
     */
    public static function onAllPeersReady(Election $election)
    {
        Log::debug('Freeze2IAmReadyForFreeze::onAllPeersReady');

//        SendP2PMessage::dispatch(
//            new Freeze3CommitFailRequest(
//                PeerServer::me(),
//                $election->peerServerAuthor,
//                $election,
//                $trustees
//            )
//        );

        // TODO send all received broadcast to coordinator, he will check they are the same for all peers
//           TODO RunP2PTask::dispatch(new ReplyToCoordinator($this->broadcast));
    }

}
