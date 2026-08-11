<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    /**
     * Determine if the user can view the dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->hasAnyPermission(['dashboard', 'Administrator']);
    }
}
