<?php

namespace App\Console\Commands;

use App\Models\PeerServer;
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
//        $me = PeerServer::me();
//        $this->info('I AM ' . $me->domain);
        $toDomain = $this->argument('domain');
        PeerServer::addPeer($toDomain);
        return 0;
    }
}
