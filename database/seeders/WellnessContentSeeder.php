<?php

namespace Database\Seeders;

use App\Models\WellnessCategory;
use App\Models\WellnessContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WellnessContentSeeder extends Seeder
{
    /**
     * Seed sample platform wellness articles (non-clinical).
     */
    public function run(): void
    {
        $samples = [
            'sleep' => [
                'title' => 'Build a consistent bedtime wind-down',
                'summary' => 'Simple evening routines that support healthier sleep patterns.',
                'body' => "Wellness guidance only — not medical advice.\n\nDim lights an hour before bed, keep screens out of reach, and aim for a consistent sleep and wake time on most days.",
                'tags' => ['sleep', 'routine'],
            ],
            'hydration' => [
                'title' => 'Sip water throughout the day',
                'summary' => 'Easy hydration habits for desk workers and busy days.',
                'body' => "Wellness guidance only — not medical advice.\n\nKeep a refillable bottle nearby and take a few sips between meetings. Pair drinks with meals if you forget otherwise.",
                'tags' => ['hydration', 'desk_worker'],
            ],
            'movement' => [
                'title' => 'Break up long sitting blocks',
                'summary' => 'Short movement breaks help you feel less stiff during sedentary days.',
                'body' => "Wellness guidance only — not medical advice.\n\nStand, stretch, or walk for a few minutes each hour. Even brief movement counts toward daily activity.",
                'tags' => ['movement', 'desk_worker'],
            ],
            'nutrition' => [
                'title' => 'Add color to everyday meals',
                'summary' => 'Practical non-clinical ideas for more fruits and vegetables.',
                'body' => "Wellness guidance only — not medical advice.\n\nAdd one fruit or vegetable to meals you already enjoy. Small, repeatable upgrades beat all-or-nothing plans.",
                'tags' => ['nutrition', 'habits'],
            ],
            'stress_management' => [
                'title' => 'Try a two-minute breathing reset',
                'summary' => 'A short breathing pause you can use during stressful moments.',
                'body' => "Wellness guidance only — not medical advice.\n\nInhale slowly for four counts, exhale for six, and repeat for two minutes. Use it before tough calls or after rushing.",
                'tags' => ['stress_management', 'breathing'],
            ],
            'exercise' => [
                'title' => 'Start with walks you can finish',
                'summary' => 'Build exercise consistency with approachable walking goals.',
                'body' => "Wellness guidance only — not medical advice.\n\nChoose a walk length you can complete most days. Consistency matters more than intensity when restarting.",
                'tags' => ['exercise', 'walking'],
            ],
            'preventive_health' => [
                'title' => 'Keep preventive check-in reminders',
                'summary' => 'Lifestyle reminders for staying on top of routine health appointments.',
                'body' => "Wellness guidance only — not medical advice.\n\nNote when your next routine visit is due and set a calendar reminder. Ask your clinician which screenings fit your situation.",
                'tags' => ['preventive_health', 'reminders'],
            ],
            'general_wellness' => [
                'title' => 'Protect one recovery block each week',
                'summary' => 'Schedule downtime so rest is intentional, not leftover.',
                'body' => "Wellness guidance only — not medical advice.\n\nBlock a short weekly window for rest, hobbies, or quiet time. Treat it like any other important appointment.",
                'tags' => ['general_wellness', 'recovery'],
            ],
        ];

        foreach ($samples as $categorySlug => $sample) {
            $category = WellnessCategory::query()->where('slug', $categorySlug)->first();

            if ($category === null) {
                continue;
            }

            $slug = Str::slug($sample['title']);

            WellnessContent::query()->firstOrCreate(
                [
                    'tenant_key' => 'system',
                    'slug' => $slug,
                    'locale' => 'en',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => null,
                    'category_id' => $category->id,
                    'title' => $sample['title'],
                    'summary' => $sample['summary'],
                    'body' => $sample['body'],
                    'is_clinical_advice' => false,
                    'status' => WellnessContent::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'personalization_tags' => $sample['tags'],
                ],
            );
        }
    }
}
