<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\UiTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WebsiteTranslationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class UiTranslationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
        $this->seed(WebsiteTranslationSeeder::class);
    }

    public function test_public_translations_return_locale_dictionary_with_fallback(): void
    {
        $response = $this->getJson('/api/v1/translations?locale=hi&group=website')
            ->assertOk()
            ->assertJsonPath('data.locale', 'hi');

        $strings = $response->json('data.strings');
        $this->assertIsArray($strings);
        $this->assertSame('आपका एआई-संचालित स्वास्थ्य साथी', $strings['home.hero.title']);
        $this->assertSame('लॉगिन', $strings['nav.login']);
    }

    public function test_admin_can_upsert_and_list_translations(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/translations', [
            'translations' => [
                [
                    'group' => 'website',
                    'key' => 'home.hero.title',
                    'locale' => 'es',
                    'value' => 'Tu salud. Conectada. Comprendida.',
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.0.key', 'home.hero.title');

        Language::query()->where('code', 'es')->update(['is_enabled' => true]);
        Language::query()->where('code', 'es')->firstOrFail()
            ->scopeAssignments()->updateOrCreate(
                ['scope' => 'public_content'],
                ['is_enabled' => true],
            );

        $list = $this->getJson('/api/v1/admin/translations?group=website')
            ->assertOk()
            ->assertJsonFragment(['key' => 'home.hero.title'])
            ->assertJsonFragment(['key' => 'app.nav.home'])
            ->assertJsonFragment(['key' => 'mobile.tab.guide']);

        $this->assertGreaterThan(40, (int) $list->json('meta.catalog_key_count'));

        $this->getJson('/api/v1/admin/translations?locale=es&group=website')
            ->assertOk()
            ->assertJsonFragment(['value' => 'Tu salud. Conectada. Comprendida.']);

        $this->assertDatabaseHas('ui_translations', [
            'key' => 'home.hero.title',
            'locale' => 'es',
        ]);
    }

    public function test_org_admin_cannot_manage_translations(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/admin/translations', [
            'translations' => [
                ['key' => 'x', 'locale' => 'en', 'value' => 'y'],
            ],
        ])->assertForbidden();

        $row = UiTranslation::query()->firstOrFail();
        $this->deleteJson('/api/v1/admin/translations/'.$row->id)->assertForbidden();
    }
}
