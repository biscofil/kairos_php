<?php
/*
 * Copyright (c) 2021.
 * Filippo Bisconcin
 * filippo.bisconcin@gmail.com
 */

namespace Database\Seeders;

use App\Models\PeerServer;
use Illuminate\Database\Seeder;

/**
 * Class CustomEventsTableSeeder
 */
class PeerServersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $peerServer = new PeerServer();
        $peerServer->id = PeerServer::meID;
        $peerServer->name = 'This server';
        $peerServer->domain = extractDomain(config('app.url'));
        $peerServer->fetchServerInfo();
        $peerServer->save();
    }
}
