<?php

namespace App\Actions\Branches;

use App\Models\Branch;

class UpdateBranchAction
{
    /**
     * @param  array{name?: string, code?: string|null, timezone?: string, status?: string, organization_id?: int}  $data
     */
    public function handle(Branch $branch, array $data): Branch
    {
        $branch->fill($data);
        $branch->save();

        return $branch->refresh();
    }
}
