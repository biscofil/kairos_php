<?php

namespace App\Console;

use App\Console\Commands\AddPeer;
use App\Console\Commands\CloseElectionPhase;
use App\Console\Commands\HeartBeat;
use App\Console\Commands\OpenElectionPhase;
use App\Console\Commands\RunWebSocketClientLoop;
use App\Console\Commands\SendReceivedVotes;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        RunWebSocketClientLoop::class,
        //
        AddPeer::class,
        HeartBeat::class,
        //
        OpenElectionPhase::class,
        CloseElectionPhase::class,
        //
        SendReceivedVotes::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function schedule(Schedule $schedule)
    {

        $logFile = getDailyLogFilename();

        $schedule->command('heartbeat')
            ->everyTwoHours()
            ->appendOutputTo($logFile);

        $schedule->command('open_election_phase')
            ->everyMinute()
            ->appendOutputTo($logFile);

        $schedule->command('close_election_phase')
            ->everyMinute()
            ->appendOutputTo($logFile);

        $me = getCurrentServer();

        $minute = hexdec(substr(sha1($me->domain), 0, 5)) % 60;

        foreach ($me->elections as $election) { // TODO open election only
            $schedule->command('send:votes ' . $election->id)
                ->cron("$minute * * * *")
                ->appendOutputTo(config('logging.channels.single.path'));
        }

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        /** @noinspection PhpIncludeInspection */
        require base_path('routes/console.php');
    }
}
