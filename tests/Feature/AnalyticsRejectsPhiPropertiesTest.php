<?php

namespace Tests\Feature;

use App\Services\Marketing\Analytics\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AnalyticsRejectsPhiPropertiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_forbidden_property_keys_are_rejected(): void
    {
        foreach (['symptom', 'diagnosis', 'medication_name', 'phi_flag', 'report_id', 'chat_message'] as $key) {
            try {
                new AnalyticsEvent(
                    name: 'page_view',
                    anonymousId: 'anon-1',
                    properties: [$key => 'x'],
                );
                $this->fail("Expected InvalidArgumentException for key {$key}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('forbidden', $e->getMessage());
            }
        }
    }

    public function test_public_endpoint_rejects_phi_property_keys(): void
    {
        $this->postJson('/api/v1/public/analytics/events', [
            'name' => 'page_view',
            'anonymous_id' => 'anon-1',
            'properties' => [
                'path' => '/en',
                'diagnosis' => 'should-not-pass',
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['properties']);
    }

    public function test_allowlisted_properties_are_accepted(): void
    {
        $event = new AnalyticsEvent(
            name: 'page_view',
            anonymousId: 'anon-1',
            properties: [
                'path' => '/en/conditions/back-pain',
                'locale' => 'en',
                'utm_source' => 'google',
                'entity_type' => 'condition',
                'entity_id' => (string) fake()->uuid(),
            ],
        );

        $this->assertSame('page_view', $event->name);
        $this->assertArrayHasKey('utm_source', $event->properties);
    }
}
