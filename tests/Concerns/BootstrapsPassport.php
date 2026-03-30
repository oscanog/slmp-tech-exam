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

        if (! DB::table('oauth_clients')->where('personal_access_client', true)->exists()) {
            app(ClientRepository::class)->createPersonalAccessClient(
                null,
                'Test Personal Access Client',
                'http://localhost'
            );
        }
    }
}
