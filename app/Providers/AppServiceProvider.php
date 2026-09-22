<?php

namespace App\Providers;

use App\Contracts\AI\AIProviderInterface;
use App\Contracts\Auth\EmailOtpProvider;
use App\Contracts\Auth\FirebaseAuthProvider;
use App\Contracts\Auth\GoogleAuthProvider;
use App\Contracts\Auth\MobileOtpProvider;
use App\Contracts\Billing\PaymentGatewayInterface;
use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Contracts\Ocr\OcrProviderInterface;
use App\Contracts\RecordIntegrityVerifierInterface;
use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\AiPrompt;
use App\Models\AiTrainingJob;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowRun;
use App\Models\AutomationWorkflowVersion;
use App\Models\ContentTemplate;
use App\Models\FormDefinition;
use App\Models\FormVersion;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ReportAnalysis;
use App\Models\TemplateVersion;
use App\Services\AI\AgentRegistry;
use App\Services\AI\Agents\HealthGuideAgent;
use App\Services\AI\Agents\Stubs\AdminAgent;
use App\Services\AI\Agents\Stubs\AnalyticsAgent;
use App\Services\AI\Agents\Stubs\AppointmentAgent;
use App\Services\AI\Agents\Stubs\BillingAgent;
use App\Services\AI\Agents\Stubs\ClinicalAgent;
use App\Services\AI\Agents\Stubs\FollowUpAgent;
use App\Services\AI\Agents\Stubs\LeadQualificationAgent;
use App\Services\AI\Agents\Stubs\MarketingAgent;
use App\Services\AI\Agents\Stubs\MedicationAgent;
use App\Services\AI\Agents\Stubs\PatientAgent;
use App\Services\AI\Agents\Stubs\PhysiotherapyAgent;
use App\Services\AI\Agents\Stubs\ProviderRecommendationAgent;
use App\Services\AI\Agents\Stubs\ReceptionistAgent;
use App\Services\AI\Agents\Stubs\ReportAgent;
use App\Services\AI\Agents\Stubs\ReviewAgent;
use App\Services\AI\Agents\Stubs\VoiceAgent;
use App\Services\AI\Agents\Stubs\WellnessAgent;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\AiUsageQuotaService;
use App\Services\AI\Learning\KnowledgeRetriever;
use App\Services\AI\Learning\LearningEngine;
use App\Services\AI\Learning\MemoryService;
use App\Services\AI\ModelRouter;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAICompatibleProvider;
use App\Services\Auth\Providers\DevEmailOtpProvider;
use App\Services\Auth\Providers\DevGoogleAuthProvider;
use App\Services\Auth\Providers\DevMobileOtpProvider;
use App\Services\Auth\Providers\FirebaseIdTokenProvider;
use App\Services\Billing\ManualPaymentGateway;
use App\Services\Integrity\NullRecordIntegrityVerifier;
use App\Services\Marketing\Analytics\AnalyticsManager;
use App\Services\Ocr\MockOcrProvider;
use App\Services\Ocr\NullOcrProvider;
use App\Services\WhatsApp\EvolutionApiGateway;
use App\Services\WhatsApp\NullWhatsAppGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RecordIntegrityVerifierInterface::class, function (): RecordIntegrityVerifierInterface {
            // Blockchain verification is deferred; always bind the null verifier in Phase 1.
            return new NullRecordIntegrityVerifier;
        });

        $this->app->bind(PaymentGatewayInterface::class, function (): PaymentGatewayInterface {
            // Live Stripe/Razorpay SDKs are deferred; Phase 6 uses manual recording.
            return match (config('billing.default_gateway', 'manual')) {
                default => new ManualPaymentGateway,
            };
        });

        $this->app->bind(WhatsAppGatewayInterface::class, function (): WhatsAppGatewayInterface {
            $driver = (string) config('whatsapp.driver', 'evolution');
            $baseUrl = trim((string) config('whatsapp.evolution.base_url'));
            $apiKey = trim((string) config('whatsapp.evolution.api_key'));

            if ($driver === 'null' || $baseUrl === '' || $apiKey === '') {
                return new NullWhatsAppGateway;
            }

            return new EvolutionApiGateway;
        });

        $this->app->bind(OcrProviderInterface::class, function (): OcrProviderInterface {
            return match ((string) config('report_ai.ocr_driver', 'mock')) {
                'null' => new NullOcrProvider,
                default => new MockOcrProvider,
            };
        });

        $this->app->bind(MobileOtpProvider::class, DevMobileOtpProvider::class);
        $this->app->bind(EmailOtpProvider::class, DevEmailOtpProvider::class);
        $this->app->bind(GoogleAuthProvider::class, DevGoogleAuthProvider::class);
        $this->app->bind(FirebaseAuthProvider::class, FirebaseIdTokenProvider::class);

        $this->app->singleton(AnalyticsGatewayInterface::class, function (): AnalyticsGatewayInterface {
            return new AnalyticsManager;
        });

        $this->app->singleton(MockAIProvider::class);
        $this->app->singleton(OpenAICompatibleProvider::class);
        $this->app->singleton(ModelRouter::class);

        $this->app->singleton(AIProviderInterface::class, function ($app): AIProviderInterface {
            $driver = (string) config('ai.default_provider', 'mock');

            if (app()->environment('testing') || $driver === 'mock') {
                return $app->make(MockAIProvider::class);
            }

            if ($driver === 'openai_compatible' && filled(config('ai.providers.openai_compatible.api_key'))) {
                return $app->make(OpenAICompatibleProvider::class);
            }

            return $app->make(MockAIProvider::class);
        });

        $this->app->singleton(AgentRegistry::class, function (): AgentRegistry {
            $registry = new AgentRegistry;

            foreach ([
                PatientAgent::class,
                HealthGuideAgent::class,
                ReportAgent::class,
                ClinicalAgent::class,
                PhysiotherapyAgent::class,
                MedicationAgent::class,
                AppointmentAgent::class,
                ReceptionistAgent::class,
                ProviderRecommendationAgent::class,
                FollowUpAgent::class,
                WellnessAgent::class,
                BillingAgent::class,
                MarketingAgent::class,
                ReviewAgent::class,
                AnalyticsAgent::class,
                LeadQualificationAgent::class,
                VoiceAgent::class,
                AdminAgent::class,
            ] as $agentClass) {
                $registry->register(new $agentClass);
            }

            return $registry;
        });

        $this->app->singleton(MemoryService::class);
        $this->app->singleton(KnowledgeRetriever::class);
        $this->app->singleton(LearningEngine::class);

        $this->app->singleton(AIOrchestrator::class, function ($app): AIOrchestrator {
            return new AIOrchestrator(
                $app->make(AgentRegistry::class),
                $app->make(ModelRouter::class),
                $app->make(AIProviderInterface::class),
                $app->make(AiUsageQuotaService::class),
                $app->make(MemoryService::class),
                $app->make(KnowledgeRetriever::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRouteBindings();
    }

    protected function configureRouteBindings(): void
    {
        Route::bind('document', function (string $value, \Illuminate\Routing\Route $route): PatientDocument {
            $patient = $route->parameter('patient');

            $query = PatientDocument::query()->where(
                fn ($builder) => $builder->where('id', $value)->orWhere('uuid', $value)
            );

            if ($patient instanceof Patient) {
                $query->where('patient_id', $patient->id);
            }

            return $query->firstOrFail();
        });

        Route::bind('analysis', function (string $value, \Illuminate\Routing\Route $route): ReportAnalysis {
            $patient = $route->parameter('patient');

            $query = ReportAnalysis::query()->where(
                fn ($builder) => $builder->where('id', $value)->orWhere('uuid', $value)
            );

            if ($patient instanceof Patient) {
                $query->where('patient_id', $patient->id);
            }

            return $query->firstOrFail();
        });

        Route::bind('prompt', function (string $value): AiPrompt {
            return AiPrompt::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('key', $value))
                ->firstOrFail();
        });

        Route::bind('template', function (string $value): ContentTemplate {
            return ContentTemplate::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('key', $value))
                ->firstOrFail();
        });

        Route::bind('form', function (string $value): FormDefinition {
            return FormDefinition::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('key', $value))
                ->firstOrFail();
        });

        Route::bind('version', function (string $value, \Illuminate\Routing\Route $route): TemplateVersion|FormVersion {
            $template = $route->parameter('template');
            if ($template instanceof ContentTemplate) {
                return TemplateVersion::query()
                    ->where('template_id', $template->id)
                    ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('version', $value))
                    ->firstOrFail();
            }

            $form = $route->parameter('form');
            if ($form instanceof FormDefinition) {
                return FormVersion::query()
                    ->where('form_definition_id', $form->id)
                    ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('version', $value))
                    ->firstOrFail();
            }

            abort(404);
        });

        Route::bind('workflow', function (string $value): AutomationWorkflow {
            return AutomationWorkflow::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('key', $value))
                ->firstOrFail();
        });

        Route::bind('workflowVersion', function (string $value, \Illuminate\Routing\Route $route): AutomationWorkflowVersion {
            $workflow = $route->parameter('workflow');
            $query = AutomationWorkflowVersion::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value)->orWhere('version', $value));

            if ($workflow instanceof AutomationWorkflow) {
                $query->where('workflow_id', $workflow->id);
            }

            return $query->firstOrFail();
        });

        Route::bind('workflowRun', function (string $value): AutomationWorkflowRun {
            return AutomationWorkflowRun::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value))
                ->firstOrFail();
        });

        Route::bind('trainingJob', function (string $value): AiTrainingJob {
            return AiTrainingJob::query()
                ->where(fn ($q) => $q->where('id', $value)->orWhere('uuid', $value))
                ->firstOrFail();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
