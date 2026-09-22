<?php

namespace Database\Seeders;

use App\Actions\Roles\AssignRoleAction;
use App\Actions\Tenants\CreateTenantAction;
use App\Models\Appointment;
use App\Models\BillingPackage;
use App\Models\BillingTaxRate;
use App\Models\Branch;
use App\Models\Clinic;
use App\Models\HealthRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Medication;
use App\Models\MobileUserPreference;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\PatientHealthProfile;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    /**
     * Seed a full local demo tenant: every role login + clinics, providers,
     * patients, schedules, appointments, billing, medications, WhatsApp, etc.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $assign = app(AssignRoleAction::class);

        $platformTenant = Tenant::query()->updateOrCreate(
            ['slug' => 'healthassist-platform'],
            [
                'name' => 'Health Assist Platform',
                'plan_code' => 'platform',
                'status' => 'active',
            ],
        );

        $tenant = Tenant::query()
            ->whereIn('slug', ['healthassist-demo', 'health-assist-demo'])
            ->first();

        if ($tenant === null) {
            $tenant = app(CreateTenantAction::class)->handle([
                'name' => 'Health Assist Demo',
                'slug' => 'healthassist-demo',
                'plan_code' => 'professional',
            ]);
        } else {
            $tenant->update([
                'name' => 'Health Assist Demo',
                'slug' => 'healthassist-demo',
                'plan_code' => 'professional',
            ]);
        }

        $organization = Organization::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'demo-org'],
            [
                'name' => 'Demo Organization',
                'status' => 'active',
            ],
        );

        $branch = Branch::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'MAIN'],
            [
                'organization_id' => $organization->id,
                'name' => 'Main Branch',
                'timezone' => 'Asia/Kolkata',
                'status' => 'active',
            ],
        );

        $clinic = Clinic::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'demo-clinic'],
            [
                'organization_id' => $organization->id,
                'primary_branch_id' => $branch->id,
                'name' => 'Health Assist Demo Clinic',
                'description' => 'Physician-led care for chronic and acute conditions with online booking, shorter waits, and assistive digital support.',
                'type' => 'clinic',
                'phone' => '+919457546588',
                'email' => 'clinic@healthassist.test',
                'whatsapp_number' => '+919457546588',
                'website' => 'https://healthassist.test',
                'address_line1' => 'Residency Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560025',
                'country' => 'IN',
                'is_public' => true,
                'status' => 'active',
                'experience_years' => 12,
                'default_locale' => 'en',
                'supported_locales' => ['en', 'hi'],
                'meta' => [
                    'profile' => [
                        'tagline' => 'Modern care · Traditional trust',
                        'hero_headline' => "Your family's trusted partner in modern healthcare.",
                        'hero_subheadline' => 'Physician-led care for diabetes, hypertension, musculoskeletal pain, and general medicine — with online booking, shorter waits, and clear visit prep.',
                        'about' => "Welcome to Health Assist Demo Clinic. We combine physician-led clinical care with modern physiotherapy and digital follow-up so patients spend less time waiting and more time recovering.\n\nThis demo profile mirrors a real clinic marketing site: editable from the clinic staff panel and the platform super-admin console.",
                        'booking_benefits' => [
                            'Priority consultation over walk-in patients',
                            'Minimal waiting time with reserved slots',
                            'Faster registration from your phone',
                            'Clear follow-up reminders after visits',
                        ],
                        'highlights' => [
                            ['label' => 'Years clinic experience', 'value' => '12+'],
                            ['label' => 'Patients served', 'value' => '25,000+'],
                            ['label' => 'Patient feedback', 'value' => '98.4%'],
                            ['label' => 'Avg. wait time', 'value' => '< 10 min'],
                        ],
                        'features' => [
                            [
                                'title' => 'Guided consults',
                                'description' => 'Clear visit prep and clinician-led advice for everyday health questions.',
                            ],
                            [
                                'title' => 'Integrated diagnostics',
                                'description' => 'Coordinate tests and follow-ups from one clinic workflow.',
                            ],
                            [
                                'title' => 'Digital health records',
                                'description' => 'Keep reports and visit notes easier to find between appointments.',
                            ],
                            [
                                'title' => 'Calm check-in',
                                'description' => 'Reserved slots and assistive reminders to reduce waiting stress.',
                            ],
                        ],
                        'booking_steps' => [
                            [
                                'title' => 'Select center & time',
                                'description' => 'Choose a specialty or clinician and pick a reserved slot that fits your day.',
                            ],
                            [
                                'title' => 'Update your profile',
                                'description' => 'Share basic patient details so check-in is faster when you arrive.',
                            ],
                            [
                                'title' => 'Confirm & walk in ready',
                                'description' => 'Get confirmation details and arrive with your visit queue already set.',
                            ],
                        ],
                        'testimonials' => [
                            [
                                'quote' => 'Booking online was quick and easy. Staff were professional and the wait was short.',
                                'author' => 'Ravi K.',
                                'role' => 'Bengaluru',
                            ],
                            [
                                'quote' => 'The slot system reduced waiting time significantly. The clinician explained everything clearly.',
                                'author' => 'Anita S.',
                                'role' => 'Bengaluru',
                            ],
                            [
                                'quote' => 'Follow-up reminders helped me stay on track after physiotherapy. The process felt calm and organized.',
                                'author' => 'Meera P.',
                                'role' => 'Bengaluru',
                            ],
                        ],
                        'cta_label' => 'Book Appointment',
                        'cta_href' => '/login?next=/app/appointments/book',
                        'seo_title' => 'Health Assist Demo Clinic | Bengaluru',
                        'seo_description' => 'Book physician-led care in Bengaluru. Physiotherapy, orthopedics, and general medicine with online appointments.',
                    ],
                ],
            ],
        );

        $organization->update([
            'description' => 'Demo care network operating the Health Assist Demo Clinic.',
            'phone' => '+919457546588',
            'email' => 'admin@healthassist.test',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'country' => 'IN',
            'meta' => [
                'profile' => [
                    'tagline' => 'Trusted care network',
                    'hero_headline' => 'Care that respects your time',
                    'hero_subheadline' => 'Multi-clinic network powered by Health Assist — bookable online with transparent schedules.',
                    'about' => 'Health Assist Demo Organization operates public clinics with shared standards for booking, billing, and assistive digital care.',
                    'highlights' => [
                        ['label' => 'Clinics', 'value' => '1+'],
                        ['label' => 'Cities', 'value' => 'Bengaluru'],
                    ],
                    'cta_label' => 'View clinics',
                    'seo_title' => 'Health Assist Demo Organization',
                    'seo_description' => 'Public organization profile for the Health Assist demo tenant.',
                ],
            ],
        ]);

        $branch->update(['clinic_id' => $clinic->id]);

        $specialties = Specialty::query()
            ->where('tenant_key', 'system')
            ->whereIn('slug', ['physiotherapy', 'orthopedics', 'general_medicine', 'wellness'])
            ->get()
            ->keyBy('slug');

        if ($specialties->isNotEmpty()) {
            $clinic->specialties()->syncWithoutDetaching($specialties->pluck('id')->all());
        }

        $physio = $specialties->get('physiotherapy');

        $services = [
            [
                'slug' => 'physio-consult',
                'name' => 'Physiotherapy Initial Consultation',
                'price_cents' => 150000,
                'specialty' => 'physiotherapy',
                'description' => 'Assessment-led physiotherapy consult for back, knee, and mobility concerns with a clear home plan.',
            ],
            [
                'slug' => 'physio-session',
                'name' => 'Advanced Physiotherapy Session',
                'price_cents' => 120000,
                'specialty' => 'physiotherapy',
                'description' => 'Hands-on therapy session focused on recovery progress, mobility, and ergonomic guidance.',
            ],
            [
                'slug' => 'ortho-consult',
                'name' => 'Orthopedics Consultation',
                'price_cents' => 200000,
                'specialty' => 'orthopedics',
                'description' => 'Physician review for joint pain, sports injuries, and musculoskeletal concerns with next-step planning.',
            ],
            [
                'slug' => 'wellness-check',
                'name' => 'Wellness Check-in',
                'price_cents' => 80000,
                'specialty' => 'wellness',
                'description' => 'Preventive wellness visit covering lifestyle, vitals review, and practical follow-up recommendations.',
            ],
            [
                'slug' => 'diabetes-check',
                'name' => 'Diabetes Check-up',
                'price_cents' => 99900,
                'specialty' => 'general_medicine',
                'description' => 'Focused diabetes review with medication discussion, lifestyle guidance, and monitoring tips.',
            ],
            [
                'slug' => 'general-consult',
                'name' => 'General Medicine Consultation',
                'price_cents' => 90000,
                'specialty' => 'general_medicine',
                'description' => 'Primary care consult for fever, infections, hypertension follow-up, and everyday health concerns.',
            ],
        ];

        $serviceModels = [];
        foreach ($services as $svc) {
            $specialtyId = $specialties->get($svc['specialty'])?->id;
            $serviceModels[$svc['slug']] = Service::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'clinic_id' => $clinic->id, 'slug' => $svc['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'specialty_id' => $specialtyId,
                    'name' => $svc['name'],
                    'description' => $svc['description'],
                    'duration_minutes' => 30,
                    'price_cents' => $svc['price_cents'],
                    'currency' => 'INR',
                    'is_public' => true,
                    'status' => 'active',
                ],
            );
        }

        $superAdmin = $this->upsertUser([
            'email' => 'super@healthassist.test',
            'name' => 'Platform Super Admin',
            'tenant_id' => null,
        ]);
        $assign->handle(
            $superAdmin,
            Role::query()->where('slug', 'super_admin')->whereNull('tenant_id')->firstOrFail(),
            $platformTenant->id,
            null,
            $superAdmin,
        );
        $this->setMobilePreference($superAdmin, 'clinic_owner');

        $admin = $this->upsertUser([
            'email' => 'admin@healthassist.test',
            'name' => 'Demo Admin',
            'tenant_id' => $tenant->id,
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $admin,
            'organization_admin',
            $tenant->id,
            $branch->id,
            $admin,
        );
        $this->setMobilePreference($admin, 'clinic_owner');

        $manager = $this->upsertUser([
            'email' => 'manager@healthassist.test',
            'name' => 'Demo Branch Manager',
            'tenant_id' => $tenant->id,
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $manager,
            'branch_manager',
            $tenant->id,
            $branch->id,
            $admin,
        );
        $this->setMobilePreference($manager, 'clinic_staff');

        $providerUser = $this->upsertUser([
            'email' => 'provider@healthassist.test',
            'name' => 'Dr. Priya Sharma',
            'tenant_id' => $tenant->id,
            'phone' => '+15550002222',
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $providerUser,
            'provider',
            $tenant->id,
            $branch->id,
            $admin,
        );
        $this->setMobilePreference($providerUser, 'healthcare_professional', 'physiotherapist');

        $provider = Provider::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'clinic_id' => $clinic->id,
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
            ],
            [
                'user_id' => $providerUser->id,
                'display_name' => 'Dr. Priya Sharma',
                'type' => 'physiotherapist',
                'bio' => 'Demo physiotherapist for Health Assist.',
                'years_experience' => 8,
                'languages' => ['en', 'hi'],
                'verification_status' => 'verified',
                'is_public' => true,
                'status' => 'active',
            ],
        );

        if ($physio) {
            $provider->specialties()->syncWithoutDetaching([
                $physio->id => ['is_primary' => true],
            ]);
        }
        $provider->branches()->syncWithoutDetaching([$branch->id]);

        $provider2User = $this->upsertUser([
            'email' => 'doctor@healthassist.test',
            'name' => 'Dr. Arjun Mehta',
            'tenant_id' => $tenant->id,
            'phone' => '+15550003333',
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $provider2User,
            'provider',
            $tenant->id,
            $branch->id,
            $admin,
        );
        $this->setMobilePreference($provider2User, 'healthcare_professional', 'doctor');

        $provider2 = Provider::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'clinic_id' => $clinic->id,
                'first_name' => 'Arjun',
                'last_name' => 'Mehta',
            ],
            [
                'user_id' => $provider2User->id,
                'display_name' => 'Dr. Arjun Mehta',
                'type' => 'doctor',
                'bio' => 'Demo orthopedics / general medicine provider.',
                'years_experience' => 12,
                'languages' => ['en', 'hi'],
                'verification_status' => 'verified',
                'is_public' => true,
                'status' => 'active',
            ],
        );

        foreach (['orthopedics', 'general_medicine'] as $slug) {
            $spec = $specialties->get($slug);
            if ($spec) {
                $provider2->specialties()->syncWithoutDetaching([
                    $spec->id => ['is_primary' => $slug === 'orthopedics'],
                ]);
            }
        }
        $provider2->branches()->syncWithoutDetaching([$branch->id]);

        foreach ([$provider, $provider2] as $p) {
            foreach ([1, 2, 3, 4, 5] as $day) {
                Schedule::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'provider_id' => $p->id,
                        'day_of_week' => $day,
                        'start_time' => '09:00:00',
                    ],
                    [
                        'clinic_id' => $clinic->id,
                        'branch_id' => $branch->id,
                        'end_time' => '17:00:00',
                        'slot_duration_minutes' => 30,
                        'is_active' => true,
                    ],
                );
            }
        }

        $patientUser = $this->upsertUser([
            'email' => 'patient@healthassist.test',
            'name' => 'Demo Patient',
            'tenant_id' => $tenant->id,
            'phone' => '+15550004444',
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $patientUser,
            'patient',
            $tenant->id,
            null,
            $admin,
        );
        $this->setMobilePreference($patientUser, 'patient');

        $patient = Patient::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'patient@healthassist.test'],
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $patientUser->id,
                'first_name' => 'Asha',
                'last_name' => 'Patel',
                'date_of_birth' => '1992-04-15',
                'gender' => 'female',
                'phone' => '+15550004444',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'IN',
                'preferred_language' => 'en',
                'consent_status' => 'granted',
                'consent_at' => now(),
                'status' => 'active',
            ],
        );

        $patient2User = $this->upsertUser([
            'email' => 'patient2@healthassist.test',
            'name' => 'Demo Patient Two',
            'tenant_id' => $tenant->id,
            'phone' => '+15550005555',
        ]);
        $this->assignExclusiveDemoRole(
            $assign,
            $patient2User,
            'patient',
            $tenant->id,
            null,
            $admin,
        );
        $this->setMobilePreference($patient2User, 'patient');

        $patient2 = Patient::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'patient2@healthassist.test'],
            [
                'uuid' => (string) Str::uuid(),
                'user_id' => $patient2User->id,
                'first_name' => 'Rohan',
                'last_name' => 'Singh',
                'date_of_birth' => '1988-11-02',
                'gender' => 'male',
                'phone' => '+15550005555',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'IN',
                'preferred_language' => 'en',
                'consent_status' => 'granted',
                'consent_at' => now(),
                'status' => 'active',
            ],
        );

        foreach ([$patient, $patient2] as $p) {
            PatientHealthProfile::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'patient_id' => $p->id],
                [
                    'medical_history' => 'Demo medical history for local testing.',
                    'conditions' => ['hypertension'],
                    'allergies' => ['penicillin'],
                    'lifestyle' => 'Moderate activity; seeded demo profile.',
                ],
            );

            PatientEmergencyContact::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'patient_id' => $p->id,
                    'name' => 'Emergency Contact',
                ],
                [
                    'relationship' => 'spouse',
                    'phone' => '+15550009999',
                    'is_primary' => true,
                ],
            );

            HealthRecord::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'patient_id' => $p->id,
                    'title' => 'Demo intake note',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'category' => 'consultation_note',
                    'description' => 'Seeded health record for demo browsing.',
                    'recorded_at' => now()->subDays(7),
                    'status' => 'active',
                ],
            );
        }

        Medication::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'name' => 'Ibuprofen',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'provider_id' => $provider->id,
                'dosage' => '400mg',
                'frequency_label' => 'twice daily as needed',
                'route' => 'oral',
                'instructions' => 'Take after food. Demo medication only.',
                'start_date' => now()->subDays(3)->toDateString(),
                'status' => Medication::STATUS_ACTIVE,
                'created_by_user_id' => $providerUser->id,
            ],
        );

        $starts = now()->next('Monday')->setTime(10, 0);
        if ($starts->isPast()) {
            $starts = $starts->addWeek();
        }

        Appointment::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
                'starts_at' => $starts,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'clinic_id' => $clinic->id,
                'branch_id' => $branch->id,
                'service_id' => $serviceModels['physio-consult']->id ?? null,
                'status' => Appointment::STATUS_CONFIRMED,
                'ends_at' => $starts->copy()->addMinutes(30),
                'duration_minutes' => 30,
                'reason' => 'Lower back discomfort (demo)',
                'booked_by_user_id' => $patientUser->id,
            ],
        );

        Appointment::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'patient_id' => $patient2->id,
                'provider_id' => $provider2->id,
                'starts_at' => $starts->copy()->addHours(2),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'clinic_id' => $clinic->id,
                'branch_id' => $branch->id,
                'service_id' => $serviceModels['ortho-consult']->id ?? null,
                'status' => Appointment::STATUS_CONFIRMED,
                'ends_at' => $starts->copy()->addHours(2)->addMinutes(30),
                'duration_minutes' => 30,
                'reason' => 'Knee pain follow-up (demo)',
                'booked_by_user_id' => $admin->id,
            ],
        );

        BillingTaxRate::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'GST18'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'GST 18%',
                'rate_bps' => 1800,
                'is_inclusive' => false,
                'is_active' => true,
            ],
        );

        BillingPackage::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'physio-10-pack'],
            [
                'uuid' => (string) Str::uuid(),
                'clinic_id' => $clinic->id,
                'name' => 'Physio 10-Session Pack',
                'description' => 'Demo treatment package.',
                'session_count' => 10,
                'validity_days' => 90,
                'price_cents' => 999000,
                'currency' => 'INR',
                'is_active' => true,
            ],
        );

        $invoice = Invoice::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'number' => 'INV-DEMO-0001',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'clinic_id' => $clinic->id,
                'issued_by_user_id' => $admin->id,
                'status' => Invoice::STATUS_ISSUED,
                'currency' => 'INR',
                'subtotal_cents' => 150000,
                'discount_cents' => 0,
                'tax_cents' => 27000,
                'total_cents' => 177000,
                'amount_paid_cents' => 0,
                'amount_due_cents' => 177000,
                'issued_at' => now(),
                'due_at' => now()->addDays(14),
                'notes' => 'Demo invoice for patient@healthassist.test',
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'description' => 'Physiotherapy Consultation',
            ],
            [
                'tenant_id' => $tenant->id,
                'type' => InvoiceItem::TYPE_CONSULTATION,
                'quantity' => 1,
                'unit_price_cents' => 150000,
                'discount_cents' => 0,
                'tax_rate_bps' => 1800,
                'tax_cents' => 27000,
                'line_total_cents' => 177000,
                'sort_order' => 0,
            ],
        );

        WhatsAppAccount::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'instance_name' => 'healthassist-demo',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'clinic_id' => $clinic->id,
                'name' => 'Demo WhatsApp',
                'phone_number' => '919999000111',
                'status' => WhatsAppAccount::STATUS_CONNECTED,
                'webhook_secret' => 'demo-webhook-secret',
                'is_active' => true,
            ],
        );

        if ($this->command) {
            $this->command->info('Demo logins (password for all: '.self::DEMO_PASSWORD.')');
            $this->command->table(
                ['Email', 'Role / persona', 'Tenant'],
                [
                    ['super@healthassist.test', 'super_admin (platform)', 'platform'],
                    ['admin@healthassist.test', 'organization_admin / clinic_owner', 'healthassist-demo'],
                    ['manager@healthassist.test', 'branch_manager / clinic_staff', 'healthassist-demo'],
                    ['provider@healthassist.test', 'provider (Priya Sharma)', 'healthassist-demo'],
                    ['doctor@healthassist.test', 'provider (Arjun Mehta)', 'healthassist-demo'],
                    ['patient@healthassist.test', 'patient (Asha Patel)', 'healthassist-demo'],
                    ['patient2@healthassist.test', 'patient (Rohan Singh)', 'healthassist-demo'],
                ],
            );
        }
    }

    /**
     * @param  array{email: string, name: string, tenant_id: int|null, phone?: string|null}  $data
     */
    private function upsertUser(array $data): User
    {
        return User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make(self::DEMO_PASSWORD),
                'tenant_id' => $data['tenant_id'],
                'phone' => $data['phone'] ?? null,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Demo accounts get exactly one persona role so re-seeds correct bad data
     * (e.g. providers that were previously attached as branch_manager).
     */
    private function assignExclusiveDemoRole(
        AssignRoleAction $assign,
        User $user,
        string $slug,
        int $tenantId,
        ?int $branchId,
        User $actor,
    ): void {
        $personaSlugs = [
            'patient',
            'provider',
            'clinic_admin',
            'organization_admin',
            'branch_manager',
            'super_admin',
        ];
        $roleIds = Role::query()
            ->whereNull('tenant_id')
            ->whereIn('slug', $personaSlugs)
            ->pluck('id');
        $user->roles()->detach($roleIds);

        $role = Role::query()->where('slug', $slug)->whereNull('tenant_id')->firstOrFail();
        $assign->handle($user, $role, $tenantId, $branchId, $actor);
    }

    private function setMobilePreference(User $user, string $accountType, ?string $professionalType = null): void
    {
        MobileUserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'account_type' => $accountType,
                'professional_type' => $professionalType,
                'active_workspace' => null,
                'meta' => ['seeded' => true],
            ],
        );
    }
}
