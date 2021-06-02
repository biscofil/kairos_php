<?php

namespace App\Console\Commands;

use App\Jobs\SendP2PMessage;
use App\Models\PeerServer;
use App\P2P\Messages\Heartbeat\HeartBeatRequest;
use Illuminate\Console\Command;

class HeartBeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heartbeat';

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

        $messagesToSend = PeerServer::ignoreMyself()->get()->map(function (PeerServer $server) {
            return new HeartBeatRequest(getCurrentServer(), $server);
        });
        SendP2PMessage::dispatch($messagesToSend->toArray());

        return 0;
    }
}
