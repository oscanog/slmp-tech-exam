<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

trait BootstrapsPassport
{
    protected function bootstrapPassport(): void
    {
        Artisan::call('passport:keys', ['--force' => true]);

        if (! DB::table('oauth_clients')->exists()) {
            app(ClientRepository::class)->createPersonalAccessGrantClient(
                'Test Personal Access Client',
                'users'
            );
        }
    }
}
