<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CaptureReferralAction;
use App\Actions\Marketing\ConvertReferralAction;
use App\Actions\Marketing\CreateReferralCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\CaptureReferralRequest;
use App\Http\Requests\Api\V1\Marketing\StoreReferralCodeRequest;
use App\Http\Resources\ReferralCodeResource;
use App\Http\Resources\ReferralResource;
use App\Models\Referral;
use App\Models\ReferralCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReferralController extends Controller
{
    public function indexCodes(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ReferralCode::class);

        return ReferralCodeResource::collection(
            ReferralCode::query()->latest()->paginate()
        );
    }

    public function storeCode(
        StoreReferralCodeRequest $request,
        CreateReferralCodeAction $action,
    ): JsonResponse {
        $code = $action->handle($request->validated());

        return (new ReferralCodeResource($code))
            ->response()
            ->setStatusCode(201);
    }

    public function capturePublic(
        CaptureReferralRequest $request,
        CaptureReferralAction $action,
    ): JsonResponse {
        $referral = $action->handle($request->validated());

        return (new ReferralResource($referral->load('rewards')))
            ->response()
            ->setStatusCode(201);
    }

    public function convert(
        Request $request,
        Referral $referral,
        ConvertReferralAction $action,
    ): ReferralResource {
        $this->authorize('convert', $referral);

        $validated = $request->validate([
            'referred_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'referred_patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'reward_type' => ['nullable', 'string', 'in:credit,badge,none'],
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ]);

        return new ReferralResource($action->handle($referral, $validated));
    }
}
