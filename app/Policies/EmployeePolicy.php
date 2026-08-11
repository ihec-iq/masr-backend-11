<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine if the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['show employees', 'Administrator']);
    }

    /**
     * Determine if the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->hasAnyPermission(['show employees', 'Administrator']);
    }

    /**
     * Determine if the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['add employee', 'Administrator']);
    }

    /**
     * Determine if the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasAnyPermission(['edit employee', 'Administrator']);
    }

    /**
     * Determine if the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasAnyPermission(['delete employee', 'Administrator']);
    }

    /**
     * Determine if the user can manage bonuses for employees.
     */
    public function manageBonus(User $user, Employee $employee): bool
    {
        return $user->hasAnyPermission(['add bonus', 'edit bonus', 'Administrator']);
    }

    /**
     * Determine if the user can manage promotions for employees.
     */
    public function managePromotion(User $user, Employee $employee): bool
    {
        return $user->hasAnyPermission(['add promotion', 'edit promotion', 'Administrator']);
    }

    /**
     * Determine if the user can view employees by type (office/center).
     */
    public function viewByType(User $user, string $type): bool
    {
        if ($user->hasPermission('Administrator')) {
            return true;
        }

        return match($type) {
            'office' => $user->hasPermission('vacation office'),
            'center' => $user->hasPermission('vacation center'),
            default => false,
        };
    }
}
