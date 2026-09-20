<?php

namespace Tests\Unit;

use App\Services\AI\AIOrchestrator;
use App\Services\Safety\SafetyEngine;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SafetyEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_emergency_keyword_returns_emergency_without_llm(): void
    {
        $orchestrator = Mockery::mock(AIOrchestrator::class);
        $orchestrator->shouldNotReceive('run');
        $orchestrator->shouldNotReceive('generate');
        $this->app->instance(AIOrchestrator::class, $orchestrator);

        $result = app(SafetyEngine::class)->assess('I have severe chest pain right now');

        $this->assertSame('emergency', $result->level);
        $this->assertSame('escalate_emergency', $result->action);
        $this->assertContains('emergency_chest_pain', $result->matchedRuleCodes);
        $this->assertTrue($result->isEscalation());
        $this->assertFalse($result->allowsProviderRecommendations());
    }

    public function test_mild_knee_pain_is_routine_or_insufficient(): void
    {
        $result = app(SafetyEngine::class)->assess('I have mild chronic knee pain for months');

        $this->assertContains($result->level, ['routine', 'insufficient_information']);
        $this->assertFalse($result->isEscalation());
    }

    public function test_short_text_is_insufficient_information(): void
    {
        $result = app(SafetyEngine::class)->assess('hi');

        $this->assertSame('insufficient_information', $result->level);
    }

    public function test_suicidal_ideation_escalates_emergency(): void
    {
        $result = app(SafetyEngine::class)->assess('I want to kill myself tonight');

        $this->assertSame('emergency', $result->level);
        $this->assertContains('emergency_suicidal_ideation', $result->matchedRuleCodes);
    }
}
