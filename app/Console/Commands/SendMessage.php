<?php

namespace App\Console\Commands;

use App\P2P\Messages\SendMeBackNInMSeconds;
use Illuminate\Console\Command;

class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:message';

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
        dump("I AM " . config('app.url'));

        if (str_contains(config('app.url'), "0")) {
            $host = str_replace('0', '1', config('app.url'));
        } else {
            $host = str_replace('1', '0', config('app.url'));
        }

        dump("Sending message to " . $host);

        (new SendMeBackNInMSeconds(rand(999, 9999), 5, config('app.url'), $host))->sendAsync();

        return 0;
    }
}
