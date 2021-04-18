<?php

namespace App\Console\Commands;

use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers;
use App\P2P\Messages\P2PMessage;
use Illuminate\Console\Command;

class AddPeer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:peer {to}';

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
        $me = P2PMessage::me();

        $this->info("I AM " . $me->ip);

        $to = $this->argument('to');
        $to = extractDomain($to);

        if (PeerServer::withIPAddress($to)->count()) {
            $this->warn("Already present");
        }

        $this->info("Sending message to " . $to);

        $peerServer = new PeerServer();
        $peerServer->name = $to;
        $peerServer->ip = gethostbyname($to);

        (new AddMeToYourPeers($me, [$peerServer], getJwtRSAKeyPair()->pk))->sendSync();

        $this->info("Done");
        return 0;
    }
}
