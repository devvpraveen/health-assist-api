<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CreateEmailSubscriptionAction;
use App\Actions\Marketing\CreateEmailWorkflowAction;
use App\Actions\Marketing\UnsubscribeEmailAction;
use App\Actions\Marketing\UpdateEmailWorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\StoreEmailSubscriptionRequest;
use App\Http\Requests\Api\V1\Marketing\StoreEmailWorkflowRequest;
use App\Http\Requests\Api\V1\Marketing\UpdateEmailWorkflowRequest;
use App\Http\Resources\EmailSubscriptionResource;
use App\Http\Resources\EmailWorkflowResource;
use App\Models\EmailWorkflow;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmailMarketingController extends Controller
{
    public function unsubscribe(Request $request, UnsubscribeEmailAction $action): JsonResponse
    {
        $token = (string) $request->query('token', $request->input('token', ''));

        $subscription = $action->handle($token);

        return response()->json([
            'data' => [
                'unsubscribed' => true,
                'email' => $subscription->email,
            ],
        ]);
    }

    public function storeSubscription(
        StoreEmailSubscriptionRequest $request,
        CreateEmailSubscriptionAction $action,
    ): JsonResponse {
        $subscription = $action->handle($request->validated());

        return (new EmailSubscriptionResource($subscription))
            ->response()
            ->setStatusCode(201);
    }

    public function indexWorkflows(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmailWorkflow::class);

        return EmailWorkflowResource::collection(
            EmailWorkflow::query()->latest()->paginate()
        );
    }

    public function storeWorkflow(
        StoreEmailWorkflowRequest $request,
        CreateEmailWorkflowAction $action,
    ): JsonResponse {
        $workflow = $action->handle($request->validated());

        return (new EmailWorkflowResource($workflow))
            ->response()
            ->setStatusCode(201);
    }

    public function showWorkflow(EmailWorkflow $workflow): EmailWorkflowResource
    {
        $this->authorize('view', $workflow);

        return new EmailWorkflowResource($workflow);
    }

    public function updateWorkflow(
        UpdateEmailWorkflowRequest $request,
        EmailWorkflow $workflow,
        UpdateEmailWorkflowAction $action,
    ): EmailWorkflowResource {
        return new EmailWorkflowResource($action->handle($workflow, $request->validated()));
    }

    public function destroyWorkflow(EmailWorkflow $workflow, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $workflow);

        $auditLogger->log('marketing.email_workflow.deleted', $workflow, [
            'key' => $workflow->key,
        ]);

        $workflow->delete();

        return response()->noContent();
    }
}
