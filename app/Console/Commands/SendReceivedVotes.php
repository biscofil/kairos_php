<?php

namespace App\Console\Commands;

use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\IReceivedTheseVotes;
use Illuminate\Console\Command;

class SendReceivedVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:votes {election}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $election = Election::find($this->argument('election'));

        $me = getCurrentServer();

        $to = $election->peerServers->all();

        $votes = $election->votes->all();

        $this->info("Sending votes of election $election->uuid");

        SendP2PMessage::dispatchSync(
            new IReceivedTheseVotes\IReceivedTheseVotesRequest($me, $to, $votes)
        );

        $this->info('Done');

        return 0;
    }
}
