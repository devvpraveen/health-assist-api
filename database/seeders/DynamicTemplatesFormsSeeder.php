<?php

namespace Database\Seeders;

use App\Actions\Forms\PublishFormVersionAction;
use App\Actions\Templates\PublishTemplateVersionAction;
use App\Models\ContentTemplate;
use App\Models\FormDefinition;
use App\Models\FormVersion;
use App\Models\TemplateVersion;
use Illuminate\Database\Seeder;

class DynamicTemplatesFormsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAssessmentForm('mobility', 'Mobility assessment', [
            ['key' => 'pain_score', 'type' => 'number', 'label' => 'Pain score', 'required' => true, 'min' => 0, 'max' => 10],
            ['key' => 'rom', 'type' => 'number', 'label' => 'Range of motion (degrees)', 'required' => false, 'min' => 0, 'max' => 180],
            ['key' => 'notes', 'type' => 'textarea', 'label' => 'Notes', 'required' => false],
        ]);

        $this->seedAssessmentForm('gait', 'Gait assessment', [
            ['key' => 'assistive_device', 'type' => 'select', 'label' => 'Assistive device', 'required' => false, 'options' => [
                ['value' => 'none', 'label' => 'None'],
                ['value' => 'cane', 'label' => 'Cane'],
                ['value' => 'walker', 'label' => 'Walker'],
            ]],
            ['key' => 'distance_meters', 'type' => 'number', 'label' => 'Distance (m)', 'required' => false, 'min' => 0],
            ['key' => 'notes', 'type' => 'textarea', 'label' => 'Notes', 'required' => false],
        ]);

        $this->seedAssessmentForm('functional', 'Functional assessment', [
            ['key' => 'adl_score', 'type' => 'rating', 'label' => 'ADL independence', 'required' => true, 'min' => 1, 'max' => 5],
            ['key' => 'notes', 'type' => 'textarea', 'label' => 'Notes', 'required' => false],
        ]);

        $template = ContentTemplate::query()->updateOrCreate(
            ['owner_key' => 'platform', 'key' => 'assessment_summary'],
            [
                'tenant_id' => null,
                'type' => 'assessment',
                'module_key' => 'clinical',
                'name' => 'Assessment summary',
                'description' => 'Patient-facing summary of a clinical assessment.',
                'status' => ContentTemplate::STATUS_DRAFT,
            ],
        );

        $version = TemplateVersion::query()->updateOrCreate(
            ['template_id' => $template->id, 'version' => 1],
            [
                'label' => 'v1',
                'status' => TemplateVersion::STATUS_DRAFT,
                'schema' => [
                    'body' => "Assessment for {{patient.name}}\nChief complaint: {{assessment.chief_complaint}}\nSummary: {{assessment.summary}}",
                    'sections' => [
                        [
                            'key' => 'header',
                            'title' => 'Assessment',
                            'body' => '{{patient.name}} — {{assessment.template_key}}',
                        ],
                    ],
                ],
            ],
        );

        if ($template->active_version_id === null) {
            app(PublishTemplateVersionAction::class)->handle($template, $version);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function seedAssessmentForm(string $key, string $name, array $fields): void
    {
        $form = FormDefinition::query()->updateOrCreate(
            ['owner_key' => 'platform', 'key' => $key],
            [
                'tenant_id' => null,
                'type' => 'assessment',
                'module_key' => 'clinical',
                'name' => $name,
                'description' => "Platform {$name} form.",
                'status' => FormDefinition::STATUS_DRAFT,
            ],
        );

        $version = FormVersion::query()->updateOrCreate(
            ['form_definition_id' => $form->id, 'version' => 1],
            [
                'label' => 'v1',
                'status' => FormVersion::STATUS_DRAFT,
                'schema' => ['fields' => $fields],
            ],
        );

        if ($form->active_version_id === null) {
            app(PublishFormVersionAction::class)->handle($form, $version);
        }
    }
}
