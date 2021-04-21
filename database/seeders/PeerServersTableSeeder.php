<?php
/*
 * Copyright (c) 2021.
 * Filippo Bisconcin
 * filippo.bisconcin@gmail.com
 */

namespace Database\Seeders;

use App\Models\CustomEvent;
use App\Models\PeerServer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class CustomEventsTableSeeder
 */
class PeerServersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $peerServer = new PeerServer();
        $peerServer->name = 'This server';
        $peerServer->domain = extractDomain(config('app.url'));
        $peerServer->fetchServerInfo();
        $peerServer->save();
    }
}
