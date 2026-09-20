<?php

namespace Tests\Feature;

use App\Models\ClinicalAssessment;
use App\Models\ClinicalDischargeSummary;
use App\Models\ClinicalExercisePlan;
use App\Models\ClinicalProgressNote;
use App\Models\ClinicalSoapNote;
use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ClinicalTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cross_tenant_clinical_records_return_404(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($tenantB)->create();
        $providerB = Provider::factory()->forTenant($tenantB)->create();
        $userB = User::factory()->forTenant($tenantB)->create();

        $assessment = ClinicalAssessment::factory()->forPatient($patientB)->create([
            'authored_by_user_id' => $userB->id,
        ]);
        $soap = ClinicalSoapNote::factory()->forPatient($patientB)->create([
            'provider_id' => $providerB->id,
            'authored_by_user_id' => $userB->id,
        ]);
        $plan = ClinicalTreatmentPlan::factory()->forPatient($patientB)->create([
            'authored_by_user_id' => $userB->id,
        ]);
        $progress = ClinicalProgressNote::factory()->forPatient($patientB)->create([
            'authored_by_user_id' => $userB->id,
        ]);
        $discharge = ClinicalDischargeSummary::factory()->forPatient($patientB)->create([
            'authored_by_user_id' => $userB->id,
        ]);
        $exercisePlan = ClinicalExercisePlan::factory()->forPatient($patientB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $paths = [
            '/api/v1/patients/'.$patientB->id.'/assessments/'.$assessment->id,
            '/api/v1/patients/'.$patientB->id.'/soap-notes/'.$soap->id,
            '/api/v1/patients/'.$patientB->id.'/treatment-plans/'.$plan->id,
            '/api/v1/patients/'.$patientB->id.'/progress-notes/'.$progress->id,
            '/api/v1/patients/'.$patientB->id.'/discharge-summaries/'.$discharge->id,
            '/api/v1/patients/'.$patientB->id.'/exercise-plans/'.$exercisePlan->id,
        ];

        foreach ($paths as $path) {
            $response = $this->getJson($path);
            $this->assertTrue(
                in_array($response->status(), [403, 404], true),
                "Expected 403/404 for {$path}, got {$response->status()}"
            );
        }
    }
}
