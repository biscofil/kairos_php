<?php

namespace App\Console\Commands;

use App\Voting\CryptoSystems\RSA\RSAKeyPair;
use Illuminate\Console\Command;

class GenerateJwtKeypair extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:jwt-keypair';

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

        $keyPair = RSAKeyPair::generate();

        $result = $keyPair->toPemFiles(
            config('jwt.keys.private'),
            config('jwt.keys.public')
        );

        if ($result) {
            $this->info("KeyPair exported");
            return 0;
        } else {
            $this->error("Error exporting KeyPair");
            return 1;
        }

    }
}
