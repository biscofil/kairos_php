<?php /** @noinspection PhpUnused */

namespace App\Console\Commands;

use App\Crypto\EGKeyPair;
use Illuminate\Console\Command;

/**
 * Class GeneratePrivateKey
 * @package App\Console\Commands
 */
class GeneratePrivateKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:secret_key';

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
        $keyPair = EGKeyPair::generate();
        file_put_contents(base_path('private_key.json'),
            json_encode($keyPair->sk->toArray(), JSON_PRETTY_PRINT));

        file_put_contents(base_path('public_key.json'),
            json_encode($keyPair->pk->toArray()), JSON_PRETTY_PRINT);
        return 0;
    }
}
