<?php

namespace App\Commands;

use App\Generator\DockerComposeManager;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GetServicesFromCompose extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'services:emit';

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
     */
    public function handle()
    {
        $this->info("We are ready to parse docker-compose.yml of laradock to discover services you need.");
        $this->info("Use empty string to stop");

        shell_exec('rm -rf '.app_path('../output/*'));

        $manager = new DockerComposeManager(
            config('app.usableComposes')
        );


        while ($service = $this->ask("Type service name: ", null)) {

            if ($manager->locateService($service)) {
                $manager->collectService($service);
                $this->info("Service collected!");
            } else {
                $this->alert("No such service");
            }
        }

        $manager->emitNewComposeAndDockerfiles();

        $manager->collectEnvVariables();

        foreach ($manager->getCollectedServices() as $collectedService) {
            if ($addons = $manager->showAddons($collectedService)) {
                $this->info("There are addons for ".$collectedService.'. Would you like to pick any of them?');
                foreach ($addons as $addon) {
                    if ('y' == $this->ask('Install '.$addon.' for '.$collectedService.'? [n]')) {
                        $manager->installAddon($collectedService, $addon);
                        $this->info("Installed ".$addon);
                    }
                }
            }
        }

        $manager->emitEnvFile();


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
