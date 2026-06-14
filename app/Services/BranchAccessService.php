<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchAccessService
{
    public function canSeeAllBranches(User $user): bool
    {
        return $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR');
    }

    public function selectedBranchId(User $user, ?int $requestedBranchId = null): ?int
    {
        if ($this->canSeeAllBranches($user)) {
            return $requestedBranchId;
        }

        if (!$user->branch_id) {
            abort(403, 'Your account is not assigned to a branch.');
        }

        if ($requestedBranchId && (int) $requestedBranchId !== (int) $user->branch_id) {
            abort(403, 'You cannot access another branch.');
        }

        return (int) $user->branch_id;
    }

    public function apply(Builder $query, User $user, ?int $requestedBranchId = null, ?string $qualifiedColumn = null): Builder
    {
        $branchId = $this->selectedBranchId($user, $requestedBranchId);

        if (!$branchId) {
            return $query;
        }

        return $query->where($qualifiedColumn ?: $query->getModel()->getTable() . '.branch_id', $branchId);
    }

    public function visibleBranches(User $user)
    {
        if ($this->canSeeAllBranches($user)) {
            return Branch::orderBy('name')->get();
        }

        return Branch::whereKey($user->branch_id)->get();
    }
}
