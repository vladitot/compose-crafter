<?php

namespace App\Commands;

use App\Generator\LaradockDownloader;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class LoadLaradock extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'laradock:load';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        shell_exec('rm -rf tmp/laradock');
        $version = $this->ask("Which commit of Laradock to use?", "master");
        /** @var LaradockDownloader $downloader */
        $downloader = app()->make(LaradockDownloader::class);
        $downloader->download($version);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
