<?php

use App\Services\JsonPlaceholderImportService;
use App\Services\RuntimeCheckService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('slmp:import-jsonplaceholder {--mode= : upsert or reset} {--yes : Skip the reset confirmation prompt}', function (JsonPlaceholderImportService $importService) {
    $modeLabels = [
        'upsert' => 'Import new or changed rows only',
        'reset' => 'Full reset database, migrate, then import everything again',
    ];

    $mode = $this->option('mode');

    if ($mode === null && $this->input->isInteractive() && ! app()->runningUnitTests()) {
        $selectedLabel = $this->choice(
            'How do you want to run the import?',
            array_values($modeLabels),
            $modeLabels['upsert'],
        );

        $mode = array_search($selectedLabel, $modeLabels, true);
    }

    $mode ??= 'upsert';

    if (! array_key_exists($mode, $modeLabels)) {
        $this->error('Invalid --mode value. Use "upsert" or "reset".');

        return self::FAILURE;
    }

    if ($mode === 'reset') {
        if (! $this->option('yes') && $this->input->isInteractive()) {
            $confirmed = $this->confirm(
                'This will wipe the current database, rerun migrations, and recreate Passport clients before importing. Continue?',
                false,
            );

            if (! $confirmed) {
                $this->warn('Import cancelled.');

                return self::INVALID;
            }
        }

        $this->warn('Reset mode selected. Wiping the current database...');
        if (DB::getDriverName() === 'sqlite' && DB::connection()->getDatabaseName() === ':memory:') {
            DB::statement('PRAGMA foreign_keys = OFF');

            $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->all();

            foreach ($tables as $table) {
                Schema::drop($table);
            }

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::dropAllTables();
        }

        $this->line('Re-running migrations...');
        Artisan::call('migrate', ['--force' => true]);

        if (! file_exists(storage_path('oauth-private.key')) || ! file_exists(storage_path('oauth-public.key'))) {
            $this->line('Generating Passport keys...');
            Artisan::call('passport:keys', ['--force' => true]);
        }

        if (! DB::table('oauth_clients')->exists()) {
            $this->line('Recreating Passport clients...');
            app(ClientRepository::class)->createPersonalAccessGrantClient(
                'SLMP Personal Access Client',
                'users',
            );
            app(ClientRepository::class)->createPasswordGrantClient(
                'SLMP Password Grant Client',
                'users',
                true,
            );
        }
    } else {
        $this->line('Upsert mode selected. Existing imported rows will be updated only if upstream data changed.');
    }

    $importService->import(fn (string $message) => $this->line($message));
})->purpose('Fetch JSONPlaceholder data with either upsert mode or a full reset-and-import flow.');

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
