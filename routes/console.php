<?php

use App\Services\JsonPlaceholderImportService;
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
