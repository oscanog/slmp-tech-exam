<?php

use App\Services\JsonPlaceholderImportService;
use App\Services\RuntimeCheckService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('slmp:import-jsonplaceholder', function (JsonPlaceholderImportService $importService) {
    $summary = $importService->import();

    foreach ($summary as $resource => $stats) {
        $this->info(sprintf(
            '%s: %d inserted, %d updated',
            $resource,
            $stats['inserted'],
            $stats['updated'],
        ));
    }

    $this->newLine();
    $this->info('JSONPlaceholder import completed successfully.');
})->purpose('Fetch JSONPlaceholder data and upsert it into the local database.');

Artisan::command('slmp:runtime-check', function (RuntimeCheckService $runtimeCheckService) {
    $summary = $runtimeCheckService->run();

    $this->info('Imported resource counts verified.');

    foreach ($summary['counts'] as $resource => $stats) {
        $this->line(sprintf(
            '%s: %d imported rows verified (expected at least %d)',
            $resource,
            $stats['actual'],
            $stats['expected'],
        ));
    }

    $this->newLine();
    $this->info(sprintf('API runtime checks passed against %s.', $summary['base_url']));
    $this->line('Checks: '.implode(', ', $summary['checks']));
    $this->line('Runtime-check user: '.$summary['registered_email']);

    $this->newLine();
    $this->info('Runtime check completed successfully.');
})->purpose('Verify imported counts and the live Docker API runtime.');
