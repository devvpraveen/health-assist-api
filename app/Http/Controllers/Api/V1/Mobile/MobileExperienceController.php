<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Actions\Mobile\BuildMobileExperienceAction;
use App\Actions\Mobile\UpdateMobileAccountTypeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\StoreMobileAccountTypeRequest;
use App\Http\Resources\MobileExperienceResource;
use Illuminate\Http\Request;

class MobileExperienceController extends Controller
{
    public function show(Request $request, BuildMobileExperienceAction $action): MobileExperienceResource
    {
        $experience = $action->handle($request->user());

        return new MobileExperienceResource($experience);
    }

    public function storeAccountType(
        StoreMobileAccountTypeRequest $request,
        UpdateMobileAccountTypeAction $action,
    ): MobileExperienceResource {
        $experience = $action->handle($request->user(), $request->validated());

        return new MobileExperienceResource($experience);
    }
}
