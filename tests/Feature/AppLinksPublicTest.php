<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLinksPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_app_links_returns_scheme_hosts_and_paths(): void
    {
        config([
            'mobile.scheme' => 'healthassist',
            'mobile.hosts' => ['localhost', 'healthassist.app'],
            'mobile.paths' => [
                '/login',
                '/guide',
                '/medications',
                '/appointments/:uuid',
            ],
        ]);

        $response = $this->getJson('/api/v1/public/mobile/app-links');

        $response->assertOk()
            ->assertJsonPath('scheme', 'healthassist')
            ->assertJsonPath('hosts.0', 'localhost')
            ->assertJsonPath('hosts.1', 'healthassist.app')
            ->assertJsonPath('paths.0', '/login')
            ->assertJsonPath('paths.3', '/appointments/:uuid');
    }
}
