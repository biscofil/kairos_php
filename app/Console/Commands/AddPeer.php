<?php

namespace App\Console\Commands;

use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers;
use Illuminate\Console\Command;

class AddPeer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:peer {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a peer server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $me = PeerServer::me();

        $this->info('I AM ' . $me->domain);

        $toDomain = $this->argument('domain');
        $toDomain = extractDomain($toDomain);

        $peerServer = PeerServer::withDomain($toDomain)->first();
        if (is_null($peerServer)) {
            $peerServer = PeerServer::newPeerServer($toDomain);
            $peerServer->save();
        } else {
            $this->warn('Already present');
        }

        $this->info('Sending message to ' . $toDomain);

        (new AddMeToYourPeers(
            $me,
            [$peerServer],
            getJwtRSAKeyPair()->pk,
            $peerServer->getNewToken()
        ))->sendSync();

        $this->info('Done');
        return 0;
    }
}
