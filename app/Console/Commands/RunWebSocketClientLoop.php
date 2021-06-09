<?php

namespace App\Console\Commands;

use App\P2P\P2PWebSocket;
use Illuminate\Console\Command;

class RunWebSocketClientLoop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run_web_socket_client_loop';

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
        P2PWebSocket::loop();
        return 0;
    }
}
