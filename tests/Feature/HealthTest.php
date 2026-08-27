<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_wishme_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/wishme/health')
            ->assertOk()
            ->assertJson([
                'product' => 'WISHME',
                'brand' => 'LIWAAS',
                'status' => 'ok',
            ]);
    }
}
