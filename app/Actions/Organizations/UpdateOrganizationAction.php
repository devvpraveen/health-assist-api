<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Support\MarketingProfile;

class UpdateOrganizationAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Organization $organization, array $data): Organization
    {
        if (array_key_exists('profile', $data) && is_array($data['profile'])) {
            $data['meta'] = MarketingProfile::mergeIntoMeta(
                $data['profile'],
                is_array($data['meta'] ?? null) ? $data['meta'] : $organization->meta,
            );
            unset($data['profile']);
        }

        $organization->fill($data);
        $organization->save();

        return $organization->refresh();
    }
}
