<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_endpoint_returns_expected_payload(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'app' => 'slmp_01',
            ]);
    }
}
