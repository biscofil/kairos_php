<?php

namespace App\Console\Commands;

use App\Models\PeerServer;
use App\Voting\CryptoSystems\RSA\RSAKeyPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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

        $me = getCurrentServer();
        // copy jwt token to the record corresponding to this server
        $keyPair = RSAKeyPair::generate();
        $me->jwt_public_key = $keyPair->pk;
        $me->jwt_secret_key = $keyPair->sk;
        Cache::forget(PeerServer::PeerServerMeCacheKey);

        if ($me->save()) {
            $this->info('KeyPair exported');
            return 0;
        } else {
            $this->error('Error exporting KeyPair');
            return 1;
        }

    }
}
