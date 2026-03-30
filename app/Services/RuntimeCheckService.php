<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RuntimeCheckService
{
    /**
     * @return array{
     *     base_url: string,
     *     registered_email: string,
     *     counts: array<string, array{expected: int, actual: int}>,
     *     checks: list<string>
     * }
     */
    public function run(): array
    {
        $counts = $this->verifyImportedCounts();
        $baseUrl = rtrim((string) config('services.runtime_check.base_url', 'http://nginx'), '/');
        $password = 'secret12345';
        $email = 'runtime-check-'.Str::ulid().'@example.com';

        $healthResponse = $this->request($baseUrl)->get('/api/health');
        $this->assertStatus($healthResponse, 200, 'Health check');

        if (data_get($healthResponse->json(), 'status') !== 'ok') {
            throw new RuntimeException('Health check did not return an ok status.');
        }

        $registerResponse = $this->request($baseUrl)->post('/api/auth/register', [
            'name' => 'Runtime Check User',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
        $this->assertStatus($registerResponse, 201, 'Register check');

        $loginResponse = $this->request($baseUrl)->post('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $this->assertStatus($loginResponse, 200, 'Login check');

        $token = (string) data_get($loginResponse->json(), 'access_token', '');

        if ($token === '') {
            throw new RuntimeException('Login check did not return an access token.');
        }

        $authorizedRequest = $this->request($baseUrl)->withToken($token);

        $meResponse = $authorizedRequest->get('/api/auth/me');
        $this->assertStatus($meResponse, 200, 'Me check');

        if (data_get($meResponse->json(), 'email') !== $email) {
            throw new RuntimeException('Me check returned an unexpected user payload.');
        }

        $postsResponse = $authorizedRequest->get('/api/posts', [
            'per_page' => 1,
        ]);
        $this->assertStatus($postsResponse, 200, 'Protected posts index check');

        if (count((array) data_get($postsResponse->json(), 'data', [])) === 0) {
            throw new RuntimeException('Protected posts index check returned no data.');
        }

        $logoutResponse = $authorizedRequest->post('/api/auth/logout');
        $this->assertStatus($logoutResponse, 200, 'Logout check');

        $revokedResponse = $authorizedRequest->get('/api/auth/me');

        if ($revokedResponse->status() !== 401) {
            throw new RuntimeException(sprintf(
                'Revoked-token check expected status 401 but received %d.',
                $revokedResponse->status()
            ));
        }

        return [
            'base_url' => $baseUrl,
            'registered_email' => $email,
            'counts' => $counts,
            'checks' => [
                'health',
                'register',
                'login',
                'me',
                'posts index',
                'logout',
                'revoked token',
            ],
        ];
    }

    /**
     * @return array<string, array{expected: int, actual: int}>
     */
    protected function verifyImportedCounts(): array
    {
        $summary = [];

        foreach ((array) config('services.runtime_check.expected_counts', []) as $resource => $expectedCount) {
            $actualCount = DB::table($resource)->whereNotNull('source_id')->count();
            $expectedCount = (int) $expectedCount;

            if ($actualCount < $expectedCount) {
                throw new RuntimeException(sprintf(
                    'Imported %s rows are incomplete. Expected at least %d but found %d.',
                    $resource,
                    $expectedCount,
                    $actualCount,
                ));
            }

            $summary[$resource] = [
                'expected' => $expectedCount,
                'actual' => $actualCount,
            ];
        }

        return $summary;
    }

    protected function request(string $baseUrl): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl($baseUrl)
            ->timeout((int) config('services.runtime_check.timeout', 10));
    }

    protected function assertStatus(Response $response, int $expectedStatus, string $step): void
    {
        if ($response->status() === $expectedStatus) {
            return;
        }

        throw new RuntimeException(sprintf(
            '%s expected status %d but received %d. Body: %s',
            $step,
            $expectedStatus,
            $response->status(),
            $this->responseBody($response),
        ));
    }

    protected function responseBody(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            return (string) json_encode($json, JSON_UNESCAPED_SLASHES);
        }

        return trim($response->body());
    }
}
