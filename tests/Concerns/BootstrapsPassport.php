<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

trait BootstrapsPassport
{
    protected function bootstrapPassport(): void
    {
        Artisan::call('passport:keys', ['--force' => true]);

        if (! DB::table('oauth_clients')->where('personal_access_client', true)->exists()) {
            app(ClientRepository::class)->createPersonalAccessGrantClient(
                'Test Personal Access Client',
                'users'
            );
        }
    }

    protected function actingAsPassport(array $scopes, ?User $user = null): User
    {
        $user ??= User::factory()->create();

        Passport::actingAs($user, $scopes);

        return $user;
    }
}
