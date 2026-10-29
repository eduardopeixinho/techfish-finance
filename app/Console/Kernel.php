<?php

namespace App\Console;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Demonstração pública: a conta demo volta aos dados fictícios todo dia (desfaz o que visitantes mexeram).
        // Precisa do cron da hospedagem chamando `php artisan schedule:run` a cada minuto.
        $schedule->command('db:seed', ['--class' => DemoSeeder::class, '--force' => true])
            ->dailyAt('04:00')
            ->when(fn (): bool => (bool) config('demo.enabled'))
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
