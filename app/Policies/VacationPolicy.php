<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacation;

class VacationPolicy
{
    /**
     * Determine if the user can view any vacations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'show vacations daily',
            'show vacations time', 
            'show vacations sick',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can view the vacation.
     */
    public function view(User $user, Vacation $vacation): bool
    {
        return $user->hasAnyPermission([
            'show vacations daily',
            'show vacations time',
            'show vacations sick',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can create vacations.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'add vacation daily',
            'add vacation time',
            'add vacation sick',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can update the vacation.
     */
    public function update(User $user, Vacation $vacation): bool
    {
        return $user->hasAnyPermission([
            'edit vacation daily',
            'edit vacation time',
            'edit vacation sick',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can delete the vacation.
     */
    public function delete(User $user, Vacation $vacation): bool
    {
        return $user->hasAnyPermission([
            'delete vacation daily',
            'delete vacation time',
            'delete vacation sick',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can manage office vacations.
     */
    public function manageOffice(User $user): bool
    {
        return $user->hasAnyPermission(['vacation office', 'Administrator']);
    }

    /**
     * Determine if the user can manage center vacations.
     */
    public function manageCenter(User $user): bool
    {
        return $user->hasAnyPermission(['vacation center', 'Administrator']);
    }
}
