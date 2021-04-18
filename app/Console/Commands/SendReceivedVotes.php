<?php

namespace App\Console\Commands;

use App\Models\Election;
use App\P2P\Messages\IReceivedTheseVotes;
use App\P2P\Messages\P2PMessage;
use Illuminate\Console\Command;

class SendReceivedVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:votes';

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
     */
    public function handle()
    {

        $me = P2PMessage::me();

        /** @var Election $election */
        $election = Election::find(149); // TODO

        $to = $election->peerServers->all();

        $votes = $election->votes->all();

        $this->info("Sending votes");

        (new IReceivedTheseVotes($me, $to, $votes))->sendSync();

        $this->info("Done");

        return 0;
    }
}
