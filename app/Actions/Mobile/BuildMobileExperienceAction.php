<?php

namespace App\Actions\Mobile;

use App\Models\MobileUserPreference;
use App\Models\User;
use App\Services\Mobile\MobileExperienceBuilder;

class BuildMobileExperienceAction
{
    public function __construct(private MobileExperienceBuilder $builder) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $preference = MobileUserPreference::query()->where('user_id', $user->id)->first();

        return $this->builder->build($user, $preference);
    }
}
