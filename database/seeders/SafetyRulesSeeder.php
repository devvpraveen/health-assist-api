<?php

namespace Database\Seeders;

use App\Models\SafetyRule;
use Illuminate\Database\Seeder;

class SafetyRulesSeeder extends Seeder
{
    /**
     * Seed deterministic SafetyEngine rules.
     */
    public function run(): void
    {
        $rules = [
            [
                'code' => 'emergency_chest_pain',
                'name' => 'Chest pain emergency',
                'category' => 'cardiac',
                'severity' => SafetyRule::SEVERITY_EMERGENCY,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'chest pain,pain in chest,crushing chest',
                'action' => SafetyRule::ACTION_ESCALATE_EMERGENCY,
                'message_template' => 'Chest pain can be a medical emergency. Seek emergency care immediately.',
                'sort_order' => 10,
            ],
            [
                'code' => 'emergency_breathing',
                'name' => 'Difficulty breathing emergency',
                'category' => 'respiratory',
                'severity' => SafetyRule::SEVERITY_EMERGENCY,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'difficulty breathing,shortness of breath,cant breathe,can\'t breathe,unable to breathe',
                'action' => SafetyRule::ACTION_ESCALATE_EMERGENCY,
                'message_template' => 'Difficulty breathing can be a medical emergency. Seek emergency care immediately.',
                'sort_order' => 20,
            ],
            [
                'code' => 'emergency_stroke_signs',
                'name' => 'Stroke warning signs',
                'category' => 'neuro',
                'severity' => SafetyRule::SEVERITY_EMERGENCY,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'stroke,face drooping,arm weakness,slurred speech,sudden numbness',
                'action' => SafetyRule::ACTION_ESCALATE_EMERGENCY,
                'message_template' => 'Possible stroke signs require emergency care now.',
                'sort_order' => 30,
            ],
            [
                'code' => 'emergency_severe_bleeding',
                'name' => 'Severe uncontrolled bleeding',
                'category' => 'trauma',
                'severity' => SafetyRule::SEVERITY_EMERGENCY,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'uncontrolled bleeding,severe bleeding,bleeding heavily,bleeding won\'t stop',
                'action' => SafetyRule::ACTION_ESCALATE_EMERGENCY,
                'message_template' => 'Severe uncontrolled bleeding requires emergency care.',
                'sort_order' => 40,
            ],
            [
                'code' => 'emergency_suicidal_ideation',
                'name' => 'Suicidal ideation',
                'category' => 'mental_health',
                'severity' => SafetyRule::SEVERITY_EMERGENCY,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'kill myself,suicide,suicidal,end my life,want to die',
                'action' => SafetyRule::ACTION_ESCALATE_EMERGENCY,
                'message_template' => 'If you are in crisis, seek emergency help or contact local crisis services immediately.',
                'sort_order' => 5,
            ],
            [
                'code' => 'urgent_severe_pain_red_flags',
                'name' => 'Severe pain with red flags',
                'category' => 'pain',
                'severity' => SafetyRule::SEVERITY_URGENT,
                'pattern_type' => SafetyRule::PATTERN_REGEX,
                'pattern' => 'severe\\s+(?:pain|ache).*(?:numbness|weakness|fever|swelling|night\\s+pain)|(?:numbness|weakness|fever).*(?:severe\\s+(?:pain|ache))',
                'action' => SafetyRule::ACTION_ESCALATE_URGENT,
                'message_template' => 'Severe pain with red-flag symptoms needs urgent clinical attention.',
                'sort_order' => 50,
            ],
            [
                'code' => 'clinician_review_severe_pain',
                'name' => 'Severe pain clinician review',
                'category' => 'pain',
                'severity' => SafetyRule::SEVERITY_CLINICIAN_REVIEW,
                'pattern_type' => SafetyRule::PATTERN_KEYWORD,
                'pattern' => 'severe pain,severe knee pain,excruciating pain,unbearable pain',
                'action' => SafetyRule::ACTION_REQUIRE_CLINICIAN_REVIEW,
                'message_template' => 'Severe pain should be reviewed by a clinician. Health Assist can help find an appropriate provider.',
                'sort_order' => 60,
            ],
            [
                'code' => 'routine_mild_chronic_pain',
                'name' => 'Mild chronic pain routine',
                'category' => 'pain',
                'severity' => SafetyRule::SEVERITY_ROUTINE,
                'pattern_type' => SafetyRule::PATTERN_REGEX,
                'pattern' => 'mild\\s+(?:chronic\\s+)?(?:knee\\s+)?pain|chronic\\s+mild\\s+pain',
                'action' => SafetyRule::ACTION_CONTINUE,
                'message_template' => 'Mild chronic pain without red flags can usually follow routine care pathways.',
                'sort_order' => 100,
            ],
        ];

        foreach ($rules as $rule) {
            SafetyRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                array_merge($rule, [
                    'version' => 1,
                    'is_active' => true,
                ]),
            );
        }
    }
}
