<?php declare(strict_types=1);

use App\Console\Commands\PruneOldRevisionsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune old revisions and expired preview tokens weekly
Schedule::command(PruneOldRevisionsCommand::class)->weekly();
