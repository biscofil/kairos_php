<?php

namespace App\Console\Commands;

use App\P2P\Messages\AddMeToYourPeers;
use Illuminate\Console\Command;

class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:message {to}';

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
        $myHost = config('app.url');
        dump("I AM " . $myHost);

        $to = $this->argument('to');

        dump("Sending message to " . $to);

//        (new WillYouBeAElectionTrusteeForMyElection(Election::first(), $myHost, $to))->sendSync();
//        (new AddMeToYourPeers($myHost, $to))->sendAsync();
        (new AddMeToYourPeers($myHost, $to))->sendSync();

        return 0;
    }
}
