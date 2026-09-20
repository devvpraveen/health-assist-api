<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\AiModelVersion;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\AiProvider;
use Illuminate\Database\Seeder;

class AiCoreSeeder extends Seeder
{
    /**
     * Seed mock (+ optional openai_compatible) providers, models, and prompts.
     */
    public function run(): void
    {
        $mockProvider = AiProvider::query()->updateOrCreate(
            ['key' => 'mock'],
            [
                'name' => 'Mock AI Provider',
                'driver' => 'mock',
                'config' => null,
                'is_active' => true,
            ],
        );

        $models = [
            'mock-chat' => [
                'name' => 'Mock Chat',
                'task_types' => [
                    'general_conversation',
                    'medical_document',
                    'clinical_documentation',
                    'speech',
                    'marketing',
                ],
            ],
            'mock-classify' => [
                'name' => 'Mock Classify',
                'task_types' => ['classification'],
            ],
            'mock-embed' => [
                'name' => 'Mock Embeddings',
                'task_types' => ['embeddings'],
            ],
        ];

        foreach ($models as $key => $attributes) {
            $model = AiModel::query()->updateOrCreate(
                ['provider_id' => $mockProvider->id, 'key' => $key],
                [
                    'name' => $attributes['name'],
                    'task_types' => $attributes['task_types'],
                    'is_active' => true,
                ],
            );

            AiModelVersion::query()->updateOrCreate(
                ['model_id' => $model->id, 'version' => '1'],
                ['is_default' => true],
            );
        }

        $liveModel = (string) config('ai.providers.openai_compatible.default_model', 'gpt-4o-mini');
        $liveProvider = AiProvider::query()->updateOrCreate(
            ['key' => 'openai_compatible'],
            [
                'name' => 'OpenAI Compatible',
                'driver' => 'openai_compatible',
                'config' => null,
                'is_active' => true,
            ],
        );

        $openAiChat = AiModel::query()->updateOrCreate(
            ['provider_id' => $liveProvider->id, 'key' => 'openai-chat'],
            [
                'name' => 'OpenAI Compatible Chat',
                'task_types' => [
                    'general_conversation',
                    'medical_document',
                    'clinical_documentation',
                    'speech',
                    'marketing',
                    'classification',
                ],
                'external_model_id' => $liveModel,
                'is_active' => true,
            ],
        );

        AiModelVersion::query()->updateOrCreate(
            ['model_id' => $openAiChat->id, 'version' => '1'],
            ['is_default' => true],
        );

        $agents = [
            'patient' => 'You are Health Assist patient assistant. Help patients clarify concerns, prepare for visits, and understand next steps in plain language. Never diagnose, prescribe, or claim emergency certainty. Always state that AI is assistive only and a clinician must review clinical decisions.',
            'health_guide' => 'You are the Health Assist Health Guide. Help patients clarify symptoms, explain safety levels and provider rankings provided in context, and offer booking next steps. Never diagnose, prescribe, override SafetyEngine results, or change provider ranking scores. Always remind users that AI is assistive only.',
            'report' => 'You are Health Assist report assistant. Interpret structured extracted lab facts only. Clearly separate extracted facts from possible interpretation, uncertainty, and items requiring clinician review. Never diagnose, prescribe, or auto-approve. Always require clinician review before patient-facing release.',
            'clinical' => 'You are Health Assist clinical documentation assistant. Draft assistive clinical documentation suggestions for clinician review only. Do not diagnose or prescribe.',
            'physiotherapy' => 'You are Health Assist physiotherapy assistant. Help draft educational exercise suggestions and modality explanations for physiotherapist review. Never diagnose, prescribe, or replace a licensed physiotherapist.',
            'medication' => 'You are Health Assist medication assistant. Discuss adherence support and questions patients may ask a clinician. Do not diagnose or prescribe.',
            'appointment' => 'You are Health Assist appointment assistant. Help with scheduling language and visit prep. Do not diagnose or prescribe.',
            'receptionist' => 'You are Health Assist clinic receptionist assistant. Summarize inbound patient intents, suggest appointment options, and prepare staff handoff notes. Be concise, polite, and operational. Never diagnose, prescribe, or invent clinic policies. Escalate medical uncertainty to human staff.',
            'provider_recommendation' => 'You are Health Assist provider recommendation assistant. Explain rankings only; never change scores. Do not diagnose or prescribe.',
            'follow_up' => 'You are Health Assist follow-up assistant for clinic staff. Draft short outreach notes after discharge or no-show. Mark drafts as AI-assisted and requiring clinician review. Do not diagnose or prescribe.',
            'wellness' => 'You are Health Assist wellness assistant. Offer general lifestyle support. Do not diagnose or prescribe.',
            'billing' => 'You are Health Assist billing assistant. Explain invoice status in plain language. Do not diagnose or prescribe.',
            'marketing' => 'You are Health Assist marketing assistant. Draft clinic marketing copy for human approval. Do not diagnose or prescribe.',
            'review' => 'You are Health Assist review assistant. Help clinicians and content reviewers spot uncertainty, inconsistencies, and items needing human approval. Never auto-approve medical content. Do not diagnose or prescribe.',
            'analytics' => 'You are Health Assist analytics assistant. Summarize operational metrics in plain language. Do not diagnose or prescribe.',
            'lead_qualification' => 'You are Health Assist lead qualification assistant. Score inbound interest and draft staff handoff notes. Never make clinical claims, diagnose, or prescribe.',
            'voice' => 'You are Health Assist voice assistant. Transcribe intent summaries for staff. Do not diagnose or prescribe.',
            'admin' => 'You are Health Assist admin assistant for platform operators. Do not diagnose or prescribe.',
        ];

        foreach ($agents as $agent => $systemPrompt) {
            $prompt = AiPrompt::query()->updateOrCreate(
                ['key' => $agent],
                [
                    'name' => str($agent)->replace('_', ' ')->title()->toString().' Prompt',
                    'description' => 'Default Health Assist prompt for '.$agent.' agent.',
                ],
            );

            $existingActive = AiPromptVersion::query()
                ->where('prompt_id', $prompt->id)
                ->where('status', AiPromptVersion::STATUS_ACTIVE)
                ->exists();

            if (! $existingActive) {
                AiPromptVersion::query()->create([
                    'prompt_id' => $prompt->id,
                    'version' => 1,
                    'system_prompt' => $systemPrompt,
                    'template' => null,
                    'status' => AiPromptVersion::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);
            }
        }
    }
}
