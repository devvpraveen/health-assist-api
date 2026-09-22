<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Api\V1\Admin\PlatformIntegrationController as AdminPlatformIntegrationController;
use App\Http\Controllers\Api\V1\Admin\PlatformSettingController as AdminPlatformSettingController;
use App\Http\Controllers\Api\V1\Admin\PlatformRoleController as AdminPlatformRoleController;
use App\Http\Controllers\Api\V1\Admin\PlatformUserController as AdminPlatformUserController;
use App\Http\Controllers\Api\V1\Admin\SecurityConsoleController as AdminSecurityConsoleController;
use App\Http\Controllers\Api\V1\Admin\UiTranslationController as AdminUiTranslationController;
use App\Http\Controllers\Api\V1\Ai\AiController;
use App\Http\Controllers\Api\V1\Ai\AiLearningController;
use App\Http\Controllers\Api\V1\Ai\AiModelManageController;
use App\Http\Controllers\Api\V1\Ai\AiProviderController;
use App\Http\Controllers\Api\V1\AnalyticsEventController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AttributionController;
use App\Http\Controllers\Api\V1\AudienceSegmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Automation\AutomationWorkflowController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BillingPackageController;
use App\Http\Controllers\Api\V1\BillingTaxRateController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\ClinicalAssessmentController;
use App\Http\Controllers\Api\V1\ClinicalDischargeSummaryController;
use App\Http\Controllers\Api\V1\ClinicalExerciseLogController;
use App\Http\Controllers\Api\V1\ClinicalExercisePlanController;
use App\Http\Controllers\Api\V1\ClinicalProgressNoteController;
use App\Http\Controllers\Api\V1\ClinicalSoapNoteController;
use App\Http\Controllers\Api\V1\ClinicalTreatmentPlanController;
use App\Http\Controllers\Api\V1\ClinicalTreatmentSessionController;
use App\Http\Controllers\Api\V1\ClinicController;
use App\Http\Controllers\Api\V1\EmailMarketingController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\ExperimentController;
use App\Http\Controllers\Api\V1\Forms\FormDefinitionController;
use App\Http\Controllers\Api\V1\HealthGuideController;
use App\Http\Controllers\Api\V1\HealthRecordController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\V1\MarketingLeadController;
use App\Http\Controllers\Api\V1\MedicationController;
use App\Http\Controllers\Api\V1\MedicationLogController;
use App\Http\Controllers\Api\V1\MedicationScheduleController;
use App\Http\Controllers\Api\V1\Mobile\MobileExperienceController;
use App\Http\Controllers\Api\V1\Mobile\PushDeviceController;
use App\Http\Controllers\Api\V1\Modules\ModulePlatformController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\PatientDocumentController;
use App\Http\Controllers\Api\V1\PatientEmergencyContactController;
use App\Http\Controllers\Api\V1\PatientHealthProfileController;
use App\Http\Controllers\Api\V1\PatientPackageController;
use App\Http\Controllers\Api\V1\PatientTimelineController;
use App\Http\Controllers\Api\V1\PatientWellnessPreferenceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProgressiveAuthController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\PublicAvailabilityController;
use App\Http\Controllers\Api\V1\PublicClinicController;
use App\Http\Controllers\Api\V1\PublicHealthGuideController;
use App\Http\Controllers\Api\V1\PublicMobileAppLinksController;
use App\Http\Controllers\Api\V1\PublicOrganizationController;
use App\Http\Controllers\Api\V1\PublicProviderController;
use App\Http\Controllers\Api\V1\ClinicNoticeController;
use App\Http\Controllers\Api\V1\OrganizationReviewController;
use App\Http\Controllers\Api\V1\StaffInviteController;
use App\Http\Controllers\Api\V1\TenantSetupController;
use App\Http\Controllers\Api\V1\WorkingHourController;
use App\Http\Controllers\Api\V1\AiReceptionistController;
use App\Http\Controllers\Api\V1\PublicSeoEntityController;
use App\Http\Controllers\Api\V1\PublicSeoFaqController;
use App\Http\Controllers\Api\V1\PublicShareMetaController;
use App\Http\Controllers\Api\V1\PublicSpecialtyController;
use App\Http\Controllers\Api\V1\PublicWellnessContentController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\ReceiptController;
use App\Http\Controllers\Api\V1\ReferralController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\ReportAnalysisController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\SeoEntityController;
use App\Http\Controllers\Api\V1\SeoFaqController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use App\Http\Controllers\Api\V1\Templates\TemplateController;
use App\Http\Controllers\Api\V1\TenantAiModelSettingsController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantLanguageSettingsController;
use App\Http\Controllers\Api\V1\UiTranslationController;
use App\Http\Controllers\Api\V1\WaitlistController;
use App\Http\Controllers\Api\V1\WellnessCategoryController;
use App\Http\Controllers\Api\V1\WellnessContentController;
use App\Http\Controllers\Api\V1\WellnessRecommendationController;
use App\Http\Controllers\Api\V1\WhatsApp\EvolutionWebhookController;
use App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController;
use App\Http\Controllers\Api\V1\WhatsApp\WhatsAppConversationController;
use App\Http\Controllers\Api\V1\WhatsApp\WhatsAppHandoffController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['public.tenant', 'throttle:20,1'])->group(function (): void {
        Route::post('auth/otp/mobile/request', [ProgressiveAuthController::class, 'requestMobileOtp']);
        Route::post('auth/otp/mobile/verify', [ProgressiveAuthController::class, 'verifyMobileOtp']);
        Route::post('auth/otp/email/request', [ProgressiveAuthController::class, 'requestEmailOtp']);
        Route::post('auth/otp/email/verify', [ProgressiveAuthController::class, 'verifyEmailOtp']);
        Route::post('auth/google', [ProgressiveAuthController::class, 'google']);
        Route::post('auth/firebase', [ProgressiveAuthController::class, 'firebase']);
    });

    Route::get('languages', [LanguageController::class, 'index'])->middleware('throttle:60,1');
    Route::get('translations', [UiTranslationController::class, 'index'])->middleware('throttle:60,1');

    // Evolution API inbound webhook (no Sanctum; verified via account webhook_secret).
    Route::post('webhooks/evolution/{accountUuid}', EvolutionWebhookController::class)
        ->middleware('throttle:120,1');

    Route::prefix('public')->middleware('throttle:60,1')->group(function (): void {
        Route::get('clinics', [PublicClinicController::class, 'index']);
        Route::get('clinics/{uuidOrSlug}', [PublicClinicController::class, 'show']);
        Route::get('provider/{uuidOrSlug}', [PublicClinicController::class, 'show']);
        Route::post('provider/{uuidOrSlug}/reviews', [OrganizationReviewController::class, 'storePublic']);
        Route::get('organizations', [PublicOrganizationController::class, 'index']);
        Route::get('organizations/{uuidOrSlug}', [PublicOrganizationController::class, 'show']);
        Route::get('providers', [PublicProviderController::class, 'index']);
        Route::get('providers/{uuid}', [PublicProviderController::class, 'show']);
        Route::get('specialties', [PublicSpecialtyController::class, 'index']);
        Route::get('availability', [PublicAvailabilityController::class, 'index']);
        Route::get('seo/entities', [PublicSeoEntityController::class, 'index']);
        Route::get('seo/entities/{type}/{slug}', [PublicSeoEntityController::class, 'show']);
        Route::get('seo/faqs', [PublicSeoFaqController::class, 'index']);
        Route::get('wellness/contents', [PublicWellnessContentController::class, 'index']);
        Route::get('wellness/contents/{slug}', [PublicWellnessContentController::class, 'show']);
        Route::get('packages', [ModulePlatformController::class, 'publicPackages']);
        Route::get('modules', [ModulePlatformController::class, 'publicModules']);

        Route::post('analytics/events', [AnalyticsEventController::class, 'storePublic'])
            ->middleware('throttle:120,1');
        Route::post('attribution', [AttributionController::class, 'storePublic'])
            ->middleware('throttle:60,1');
        Route::post('leads', [MarketingLeadController::class, 'storePublic'])
            ->middleware('throttle:20,1');
        Route::post('referrals/capture', [ReferralController::class, 'capturePublic'])
            ->middleware('throttle:60,1');
        Route::post('experiments/{key}/assign', [ExperimentController::class, 'assignPublic'])
            ->middleware('throttle:60,1');
        Route::post('email/unsubscribe', [EmailMarketingController::class, 'unsubscribe'])
            ->middleware('throttle:30,1');
        Route::get('share-meta', [PublicShareMetaController::class, 'show']);
        Route::get('mobile/app-links', [PublicMobileAppLinksController::class, 'show']);

        Route::middleware('public.tenant')->prefix('health-guide')->group(function (): void {
            Route::post('sessions', [PublicHealthGuideController::class, 'storeSession']);
            Route::get('sessions/{guest}', [PublicHealthGuideController::class, 'showSession']);
            Route::post('sessions/{guest}/messages', [PublicHealthGuideController::class, 'storeMessage'])
                ->middleware('throttle:30,1');
        });
    });

    // Authenticated analytics enrichment (optional auth not required for public; hashed user uuid only when present).
    Route::post('analytics/events', [AnalyticsEventController::class, 'store'])
        ->middleware(['auth:sanctum', 'tenant', 'throttle:120,1']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/persona', [AuthController::class, 'choosePersona']);
        Route::post('auth/guest/claim', [ProgressiveAuthController::class, 'claimGuest']);

        Route::post('tenants/provision', [TenantController::class, 'provision']);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('organizations', OrganizationController::class);
        Route::apiResource('branches', BranchController::class);

        Route::get('tenant/language-settings', [TenantLanguageSettingsController::class, 'show']);
        Route::put('tenant/language-settings', [TenantLanguageSettingsController::class, 'update']);

        Route::get('tenant/ai-model-settings', [TenantAiModelSettingsController::class, 'show']);
        Route::put('tenant/ai-model-settings', [TenantAiModelSettingsController::class, 'update']);

        Route::get('modules', [ModulePlatformController::class, 'catalog']);
        Route::patch('modules/{moduleKey}', [ModulePlatformController::class, 'updateModule']);
        Route::get('packages', [ModulePlatformController::class, 'packages']);
        Route::post('packages', [ModulePlatformController::class, 'storePackage']);
        Route::patch('packages/{package}', [ModulePlatformController::class, 'updatePackage']);
        Route::get('tenants/current/modules', [ModulePlatformController::class, 'tenantModules']);
        Route::post('tenants/current/modules/{moduleKey}/activate', [ModulePlatformController::class, 'activate']);
        Route::post('tenants/current/modules/{moduleKey}/deactivate', [ModulePlatformController::class, 'deactivate']);
        Route::post('tenants/current/modules/purchase', [ModulePlatformController::class, 'purchaseModules']);
        Route::get('tenants/current/entitlements', [ModulePlatformController::class, 'entitlements']);
        Route::post('tenants/current/subscription', [ModulePlatformController::class, 'assignSubscription']);
        Route::get('tenants/current/setup-status', [TenantSetupController::class, 'show']);
        Route::post('tenants/current/setup-status/dismiss-getting-started', [TenantSetupController::class, 'dismissGettingStarted']);

        Route::get('working-hours', [WorkingHourController::class, 'index']);
        Route::put('working-hours', [WorkingHourController::class, 'sync']);
        Route::post('working-hours/exceptions', [WorkingHourController::class, 'storeException']);

        Route::get('clinic-notices', [ClinicNoticeController::class, 'index']);
        Route::post('clinic-notices', [ClinicNoticeController::class, 'store']);
        Route::patch('clinic-notices/{notice}', [ClinicNoticeController::class, 'update']);
        Route::delete('clinic-notices/{notice}', [ClinicNoticeController::class, 'destroy']);

        Route::get('staff/invites', [StaffInviteController::class, 'index']);
        Route::post('staff/invites', [StaffInviteController::class, 'store']);
        Route::delete('staff/invites/{invite}', [StaffInviteController::class, 'destroy']);

        Route::get('organization-reviews', [OrganizationReviewController::class, 'index']);
        Route::patch('organization-reviews/{review}', [OrganizationReviewController::class, 'update']);

        Route::post('ai/receptionist', [AiReceptionistController::class, 'chat']);

        Route::get('templates', [TemplateController::class, 'index']);
        Route::post('templates', [TemplateController::class, 'store']);
        Route::get('templates/{template}', [TemplateController::class, 'show']);
        Route::post('templates/{template}/versions', [TemplateController::class, 'storeVersion']);
        Route::post('templates/{template}/versions/{version}/publish', [TemplateController::class, 'publish']);
        Route::post('templates/{template}/preview', [TemplateController::class, 'preview']);

        Route::get('forms', [FormDefinitionController::class, 'index']);
        Route::post('forms', [FormDefinitionController::class, 'store']);
        Route::get('forms/{form}', [FormDefinitionController::class, 'show']);
        Route::post('forms/{form}/versions', [FormDefinitionController::class, 'storeVersion']);
        Route::post('forms/{form}/versions/{version}/publish', [FormDefinitionController::class, 'publish']);
        Route::post('forms/{form}/submissions', [FormDefinitionController::class, 'submit']);

        Route::get('workflows', [AutomationWorkflowController::class, 'index']);
        Route::post('workflows', [AutomationWorkflowController::class, 'store']);
        Route::get('workflows/{workflow}', [AutomationWorkflowController::class, 'show']);
        Route::post('workflows/{workflow}/versions', [AutomationWorkflowController::class, 'storeVersion']);
        Route::post('workflows/{workflow}/versions/{workflowVersion}/publish', [AutomationWorkflowController::class, 'publish']);
        Route::post('workflows/{workflow}/trigger', [AutomationWorkflowController::class, 'trigger']);
        Route::get('workflow-runs', [AutomationWorkflowController::class, 'runs']);
        Route::get('workflow-runs/{workflowRun}', [AutomationWorkflowController::class, 'showRun']);

        Route::prefix('admin')->group(function (): void {
            Route::get('languages', [AdminLanguageController::class, 'index']);
            Route::post('languages', [AdminLanguageController::class, 'store']);
            Route::patch('languages/{language}', [AdminLanguageController::class, 'update']);
            Route::put('languages/{language}/scopes', [AdminLanguageController::class, 'updateScopes']);

            Route::get('translations', [AdminUiTranslationController::class, 'index']);
            Route::post('translations', [AdminUiTranslationController::class, 'upsert']);
            Route::delete('translations/{translation}', [AdminUiTranslationController::class, 'destroy']);

            Route::get('users', [AdminPlatformUserController::class, 'index']);
            Route::post('users', [AdminPlatformUserController::class, 'store']);
            Route::patch('users/{user}', [AdminPlatformUserController::class, 'update']);
            Route::delete('users/{user}', [AdminPlatformUserController::class, 'destroy']);

            Route::get('permissions', [AdminPlatformRoleController::class, 'permissions']);
            Route::get('roles', [AdminPlatformRoleController::class, 'index']);
            Route::post('roles', [AdminPlatformRoleController::class, 'store']);
            Route::patch('roles/{role}', [AdminPlatformRoleController::class, 'update']);
            Route::delete('roles/{role}', [AdminPlatformRoleController::class, 'destroy']);

            Route::get('audit-logs', [AdminAuditLogController::class, 'index']);

            Route::get('settings', [AdminPlatformSettingController::class, 'index']);
            Route::post('settings', [AdminPlatformSettingController::class, 'store']);
            Route::patch('settings/{setting}', [AdminPlatformSettingController::class, 'update']);
            Route::delete('settings/{setting}', [AdminPlatformSettingController::class, 'destroy']);

            Route::get('integrations', [AdminPlatformIntegrationController::class, 'index']);
            Route::post('integrations', [AdminPlatformIntegrationController::class, 'store']);
            Route::patch('integrations/{integration}', [AdminPlatformIntegrationController::class, 'update']);
            Route::delete('integrations/{integration}', [AdminPlatformIntegrationController::class, 'destroy']);

            Route::get('security', [AdminSecurityConsoleController::class, 'show']);
            Route::post('security/alerts', [AdminSecurityConsoleController::class, 'storeAlert']);
            Route::patch('security/alerts/{alert}', [AdminSecurityConsoleController::class, 'updateAlert']);
            Route::delete('security/alerts/{alert}', [AdminSecurityConsoleController::class, 'destroyAlert']);
        });

        Route::middleware('module:providers')->group(function (): void {
            Route::apiResource('clinics', ClinicController::class);
            Route::put('clinics/{clinic}/specialties', [ClinicController::class, 'syncSpecialties']);

            Route::apiResource('clinics.services', ServiceController::class)->scoped();

            Route::apiResource('providers', ProviderController::class);
            Route::put('providers/{provider}/specialties', [ProviderController::class, 'syncSpecialties']);
            Route::put('providers/{provider}/branches', [ProviderController::class, 'syncBranches']);

            Route::apiResource('providers.schedules', ScheduleController::class)->scoped();

            Route::apiResource('specialties', SpecialtyController::class);
        });

        Route::apiResource('patients', PatientController::class)->middleware('module:patients');

        Route::middleware('module:patients')->group(function (): void {
            Route::get('patients/{patient}/health-profile', [PatientHealthProfileController::class, 'show']);
            Route::put('patients/{patient}/health-profile', [PatientHealthProfileController::class, 'update']);

            Route::apiResource('patients.emergency-contacts', PatientEmergencyContactController::class)
                ->parameters(['emergency-contacts' => 'emergency_contact'])
                ->scoped();

            Route::apiResource('patients.health-records', HealthRecordController::class)
                ->parameters(['health-records' => 'health_record'])
                ->scoped();

            Route::get('patients/{patient}/timeline', [PatientTimelineController::class, 'index']);

            Route::scopeBindings()->group(function (): void {
                Route::get('patients/{patient}/documents', [PatientDocumentController::class, 'index']);
                Route::post('patients/{patient}/documents', [PatientDocumentController::class, 'store']);
                Route::get('patients/{patient}/documents/{document}', [PatientDocumentController::class, 'show']);
                Route::get('patients/{patient}/documents/{document}/download', [PatientDocumentController::class, 'download']);
                Route::delete('patients/{patient}/documents/{document}', [PatientDocumentController::class, 'destroy']);
            });
        });

        Route::middleware('module:reports')->group(function (): void {
            Route::scopeBindings()->group(function (): void {
                Route::post('patients/{patient}/documents/{document}/analyze', [ReportAnalysisController::class, 'analyze'])
                    ->middleware('throttle:30,1');

                Route::get('patients/{patient}/report-analyses', [ReportAnalysisController::class, 'indexForPatient']);
                Route::get('patients/{patient}/report-analyses/{analysis}', [ReportAnalysisController::class, 'show']);
                Route::post('patients/{patient}/report-analyses/{analysis}/approve', [ReportAnalysisController::class, 'approve']);
                Route::post('patients/{patient}/report-analyses/{analysis}/reject', [ReportAnalysisController::class, 'reject']);
                Route::post('patients/{patient}/report-analyses/{analysis}/regenerate-explanation', [ReportAnalysisController::class, 'regenerateExplanation'])
                    ->middleware('throttle:20,1');
            });

            Route::get('report-analyses', [ReportAnalysisController::class, 'indexTenant']);
        });

        Route::middleware('module:clinical')->group(function (): void {
            Route::apiResource('exercises', ExerciseController::class);

            Route::scopeBindings()->group(function (): void {
                Route::apiResource('patients.assessments', ClinicalAssessmentController::class)->scoped();
                Route::post('patients/{patient}/assessments/{assessment}/transition', [ClinicalAssessmentController::class, 'transition']);

                Route::apiResource('patients.soap-notes', ClinicalSoapNoteController::class)
                    ->parameters(['soap-notes' => 'soap_note'])
                    ->scoped();
                Route::post('patients/{patient}/soap-notes/{soap_note}/transition', [ClinicalSoapNoteController::class, 'transition']);

                Route::apiResource('patients.treatment-plans', ClinicalTreatmentPlanController::class)
                    ->parameters(['treatment-plans' => 'treatment_plan'])
                    ->scoped();
                Route::post('patients/{patient}/treatment-plans/{treatment_plan}/transition', [ClinicalTreatmentPlanController::class, 'transition']);
                Route::post('patients/{patient}/treatment-plans/{treatment_plan}/complete', [ClinicalTreatmentPlanController::class, 'complete']);

                Route::get('patients/{patient}/treatment-plans/{treatment_plan}/sessions', [ClinicalTreatmentSessionController::class, 'indexForPlan']);
                Route::post('patients/{patient}/treatment-plans/{treatment_plan}/sessions', [ClinicalTreatmentSessionController::class, 'storeForPlan']);

                Route::apiResource('patients.treatment-sessions', ClinicalTreatmentSessionController::class)
                    ->parameters(['treatment-sessions' => 'treatment_session'])
                    ->scoped();

                Route::apiResource('patients.progress-notes', ClinicalProgressNoteController::class)
                    ->parameters(['progress-notes' => 'progress_note'])
                    ->scoped();
                Route::post('patients/{patient}/progress-notes/{progress_note}/transition', [ClinicalProgressNoteController::class, 'transition']);

                Route::apiResource('patients.discharge-summaries', ClinicalDischargeSummaryController::class)
                    ->parameters(['discharge-summaries' => 'discharge_summary'])
                    ->scoped();
                Route::post('patients/{patient}/discharge-summaries/{discharge_summary}/transition', [ClinicalDischargeSummaryController::class, 'transition']);

                Route::apiResource('patients.exercise-plans', ClinicalExercisePlanController::class)
                    ->parameters(['exercise-plans' => 'exercise_plan'])
                    ->scoped();
                Route::put('patients/{patient}/exercise-plans/{exercise_plan}/items', [ClinicalExercisePlanController::class, 'syncItems']);
                Route::get('patients/{patient}/exercise-plans/{exercise_plan}/logs', [ClinicalExerciseLogController::class, 'index']);
                Route::post('patients/{patient}/exercise-plans/{exercise_plan}/items/{item}/logs', [ClinicalExerciseLogController::class, 'store']);
            });
        });

        Route::middleware('module:appointments')->group(function (): void {
            Route::get('availability', [AvailabilityController::class, 'index']);
            Route::get('queue', [QueueController::class, 'index']);

            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::post('appointments', [AppointmentController::class, 'store']);
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
            Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
            Route::post('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule']);
            Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
            Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

            Route::get('waitlist', [WaitlistController::class, 'index']);
            Route::post('waitlist', [WaitlistController::class, 'store']);
            Route::post('waitlist/{entry}/cancel', [WaitlistController::class, 'cancel']);
        });

        Route::middleware('module:billing')->group(function (): void {
            Route::apiResource('tax-rates', BillingTaxRateController::class)
                ->parameters(['tax-rates' => 'tax_rate']);

            Route::apiResource('billing-packages', BillingPackageController::class);

            Route::get('patients/{patient}/packages', [PatientPackageController::class, 'index']);
            Route::post('patients/{patient}/packages', [PatientPackageController::class, 'store']);
            Route::post('patients/{patient}/packages/{patient_package}/consume', [PatientPackageController::class, 'consume'])
                ->scopeBindings();

            Route::get('invoices', [InvoiceController::class, 'index']);
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::patch('invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::post('invoices/{invoice}/items', [InvoiceController::class, 'storeItem']);
            Route::delete('invoices/{invoice}/items/{item}', [InvoiceController::class, 'destroyItem']);
            Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
            Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);

            Route::get('payments', [PaymentController::class, 'index']);
            Route::post('payments', [PaymentController::class, 'store']);
            Route::get('payments/{payment}', [PaymentController::class, 'show']);

            Route::get('receipts', [ReceiptController::class, 'index']);
            Route::get('receipts/{receipt}', [ReceiptController::class, 'show']);

            Route::post('refunds', [RefundController::class, 'store']);
            Route::get('refunds/{refund}', [RefundController::class, 'show']);
        });

        Route::prefix('ai')->middleware('module:ai')->group(function (): void {
            Route::get('agents', [AiController::class, 'agents']);
            Route::post('agents/{agent}/run', [AiController::class, 'run'])
                ->middleware('throttle:30,1');
            Route::get('usage/summary', [AiController::class, 'usageSummary']);
            Route::get('usage', [AiController::class, 'usage']);
            Route::get('audit-logs', [AiController::class, 'auditLogs']);
            Route::patch('audit-logs/{auditLog}/review', [AiLearningController::class, 'reviewAuditLog']);
            Route::get('prompts', [AiController::class, 'prompts']);
            Route::get('prompts/{prompt}/versions', [AiController::class, 'promptVersions']);
            Route::post('prompts/{prompt}/versions', [AiController::class, 'storePromptVersion']);
            Route::post('prompt-versions/{version}/activate', [AiController::class, 'activatePromptVersion']);
            Route::get('providers', [AiProviderController::class, 'index']);
            Route::post('providers', [AiProviderController::class, 'store']);
            Route::patch('providers/{provider}', [AiProviderController::class, 'update']);
            Route::get('models', [AiController::class, 'models']);
            Route::post('models', [AiModelManageController::class, 'store']);
            Route::patch('models/{model}', [AiModelManageController::class, 'update']);
            Route::post('models/{model}/versions', [AiModelManageController::class, 'storeVersion']);
            Route::patch('model-versions/{version}', [AiModelManageController::class, 'updateVersion']);

            Route::post('feedback', [AiLearningController::class, 'storeFeedback'])
                ->middleware('throttle:60,1');
            Route::post('learning-signals', [AiLearningController::class, 'storeSignal'])
                ->middleware('throttle:60,1');
            Route::get('learning/summary', [AiLearningController::class, 'summary']);
            Route::get('learning/candidates', [AiLearningController::class, 'candidates']);
            Route::patch('learning/candidates/{candidate}', [AiLearningController::class, 'reviewCandidate']);
            Route::post('learning/datasets', [AiLearningController::class, 'buildDataset']);
            Route::get('learning/training-jobs', [AiLearningController::class, 'trainingJobs']);
            Route::post('learning/training-jobs', [AiLearningController::class, 'queueTrainingJob'])
                ->middleware('throttle:10,1');
            Route::get('learning/training-jobs/{trainingJob}', [AiLearningController::class, 'showTrainingJob']);
            Route::get('evaluation', [AiLearningController::class, 'evaluation']);
            Route::get('knowledge/retrieve', [AiLearningController::class, 'knowledgeRetrieve']);
            Route::post('knowledge', [AiLearningController::class, 'storeKnowledge']);
        });

        Route::prefix('health-guide')->middleware('module:health_guide')->group(function (): void {
            Route::get('conversations', [HealthGuideController::class, 'index']);
            Route::post('conversations', [HealthGuideController::class, 'store']);
            Route::get('conversations/{conversation}', [HealthGuideController::class, 'show']);
            Route::post('conversations/{conversation}/messages', [HealthGuideController::class, 'storeMessage'])
                ->middleware('throttle:60,1');
            Route::post('conversations/{conversation}/recommendations', [HealthGuideController::class, 'recommendations']);
            Route::post('conversations/{conversation}/book', [HealthGuideController::class, 'book']);
            Route::get('safety-rules', [HealthGuideController::class, 'safetyRules']);
            Route::post('safety/assess', [HealthGuideController::class, 'assessSafety'])
                ->middleware('throttle:30,1');
        });

        Route::prefix('whatsapp')->middleware('module:whatsapp')->group(function (): void {
            Route::get('accounts', [WhatsAppAccountController::class, 'index']);
            Route::post('accounts', [WhatsAppAccountController::class, 'store']);
            Route::get('accounts/{account}', [WhatsAppAccountController::class, 'show']);
            Route::patch('accounts/{account}', [WhatsAppAccountController::class, 'update']);
            Route::delete('accounts/{account}', [WhatsAppAccountController::class, 'destroy']);
            Route::post('accounts/{account}/configure-webhook', [WhatsAppAccountController::class, 'configureWebhook']);

            Route::get('conversations', [WhatsAppConversationController::class, 'index']);
            Route::get('conversations/{conversation}', [WhatsAppConversationController::class, 'show']);
            Route::post('conversations/{conversation}/messages', [WhatsAppConversationController::class, 'storeMessage']);
            Route::post('conversations/{conversation}/handoff', [WhatsAppConversationController::class, 'storeHandoff']);
            Route::post('conversations/{conversation}/handoff/accept', [WhatsAppConversationController::class, 'acceptHandoff']);
            Route::post('conversations/{conversation}/handoff/resolve', [WhatsAppConversationController::class, 'resolveHandoff']);
            Route::post('conversations/{conversation}/ai-enable', [WhatsAppConversationController::class, 'enableAi']);

            Route::get('handoffs', [WhatsAppHandoffController::class, 'index']);
        });

        Route::middleware('module:medications')->group(function (): void {
            Route::scopeBindings()->group(function (): void {
                Route::apiResource('patients.medications', MedicationController::class)->scoped();
                Route::get('patients/{patient}/medications/{medication}/adherence', [MedicationController::class, 'adherence']);

                Route::apiResource('patients.medications.schedules', MedicationScheduleController::class)
                    ->parameters(['schedules' => 'schedule'])
                    ->scoped();

                Route::get('patients/{patient}/medications/{medication}/logs', [MedicationLogController::class, 'index']);
                Route::post('patients/{patient}/medications/{medication}/logs', [MedicationLogController::class, 'store']);
            });
        });

        Route::middleware('module:wellness')->group(function (): void {
            Route::scopeBindings()->group(function (): void {
                Route::get('patients/{patient}/wellness-preferences', [PatientWellnessPreferenceController::class, 'show']);
                Route::put('patients/{patient}/wellness-preferences', [PatientWellnessPreferenceController::class, 'update']);
                Route::get('patients/{patient}/wellness-recommendations', [WellnessRecommendationController::class, 'index']);
            });

            Route::get('wellness/categories', [WellnessCategoryController::class, 'index']);
            Route::get('wellness/contents', [WellnessContentController::class, 'index']);
            Route::post('wellness/contents', [WellnessContentController::class, 'store']);
            Route::get('wellness/contents/{content}', [WellnessContentController::class, 'show']);
            Route::patch('wellness/contents/{content}', [WellnessContentController::class, 'update']);
            Route::delete('wellness/contents/{content}', [WellnessContentController::class, 'destroy']);
        });

        Route::middleware('module:seo')->group(function (): void {
            Route::get('seo/entities', [SeoEntityController::class, 'index']);
            Route::post('seo/entities', [SeoEntityController::class, 'store']);
            Route::get('seo/entities/{entity}', [SeoEntityController::class, 'show']);
            Route::patch('seo/entities/{entity}', [SeoEntityController::class, 'update']);
            Route::delete('seo/entities/{entity}', [SeoEntityController::class, 'destroy']);
            Route::post('seo/entities/{entity}/publish', [SeoEntityController::class, 'publish']);

            Route::get('seo/faqs', [SeoFaqController::class, 'index']);
            Route::post('seo/faqs', [SeoFaqController::class, 'store']);
            Route::get('seo/faqs/{faq}', [SeoFaqController::class, 'show']);
            Route::patch('seo/faqs/{faq}', [SeoFaqController::class, 'update']);
            Route::delete('seo/faqs/{faq}', [SeoFaqController::class, 'destroy']);
        });

        Route::middleware('module:marketing')->group(function (): void {
            Route::get('attribution/touches', [AttributionController::class, 'index']);

            Route::get('referrals/codes', [ReferralController::class, 'indexCodes']);
            Route::post('referrals/codes', [ReferralController::class, 'storeCode']);
            Route::post('referrals/{referral}/convert', [ReferralController::class, 'convert']);

            Route::get('experiments', [ExperimentController::class, 'index']);
            Route::post('experiments', [ExperimentController::class, 'store']);
            Route::get('experiments/{experiment}', [ExperimentController::class, 'show']);
            Route::patch('experiments/{experiment}', [ExperimentController::class, 'update']);
            Route::delete('experiments/{experiment}', [ExperimentController::class, 'destroy']);
            Route::post('experiments/{key}/expose', [ExperimentController::class, 'expose']);

            Route::post('email/subscriptions', [EmailMarketingController::class, 'storeSubscription']);
            Route::get('email/workflows', [EmailMarketingController::class, 'indexWorkflows']);
            Route::post('email/workflows', [EmailMarketingController::class, 'storeWorkflow']);
            Route::get('email/workflows/{workflow}', [EmailMarketingController::class, 'showWorkflow']);
            Route::patch('email/workflows/{workflow}', [EmailMarketingController::class, 'updateWorkflow']);
            Route::delete('email/workflows/{workflow}', [EmailMarketingController::class, 'destroyWorkflow']);

            Route::get('segments', [AudienceSegmentController::class, 'index']);
            Route::post('segments', [AudienceSegmentController::class, 'store']);
            Route::get('segments/{segment}', [AudienceSegmentController::class, 'show']);
            Route::patch('segments/{segment}', [AudienceSegmentController::class, 'update']);
            Route::delete('segments/{segment}', [AudienceSegmentController::class, 'destroy']);
            Route::get('segments/{key}/members', [AudienceSegmentController::class, 'members']);
        });

        Route::middleware('module:crm')->group(function (): void {
            Route::get('crm/leads', [MarketingLeadController::class, 'index']);
            Route::post('crm/leads', [MarketingLeadController::class, 'store']);
            Route::get('crm/leads/{lead}', [MarketingLeadController::class, 'show']);
            Route::patch('crm/leads/{lead}', [MarketingLeadController::class, 'update']);
            Route::delete('crm/leads/{lead}', [MarketingLeadController::class, 'destroy']);
        });

        Route::prefix('mobile')->group(function (): void {
            Route::get('experience', [MobileExperienceController::class, 'show']);
            Route::post('onboarding/account-type', [MobileExperienceController::class, 'storeAccountType']);
            Route::get('devices', [PushDeviceController::class, 'index']);
            Route::post('devices', [PushDeviceController::class, 'store']);
            Route::delete('devices/{device}', [PushDeviceController::class, 'destroy']);
        });
    });
});
