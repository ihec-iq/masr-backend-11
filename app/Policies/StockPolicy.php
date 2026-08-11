<?php

namespace App\Policies;

use App\Models\User;

class StockPolicy
{
    /**
     * Determine if the user can view stock items.
     */
    public function viewStock(User $user): bool
    {
        return $user->hasAnyPermission(['show storage', 'show items', 'Administrator']);
    }

    /**
     * Determine if the user can manage items.
     */
    public function manageItems(User $user): bool
    {
        return $user->hasAnyPermission([
            'add item',
            'edit item',
            'delete item',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can manage input vouchers.
     */
    public function manageInputVouchers(User $user): bool
    {
        return $user->hasAnyPermission([
            'add inputVoucher',
            'edit inputVoucher',
            'delete inputVoucher',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can view input vouchers.
     */
    public function viewInputVouchers(User $user): bool
    {
        return $user->hasAnyPermission(['show inputVouchers', 'Administrator']);
    }

    /**
     * Determine if the user can manage output vouchers.
     */
    public function manageOutputVouchers(User $user): bool
    {
        return $user->hasAnyPermission([
            'add outputVoucher',
            'edit outputVoucher',
            'delete outputVoucher',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can view output vouchers.
     */
    public function viewOutputVouchers(User $user): bool
    {
        return $user->hasAnyPermission(['show outputVouchers', 'Administrator']);
    }

    /**
     * Determine if the user can manage direct vouchers.
     */
    public function manageDirectVouchers(User $user): bool
    {
        return $user->hasAnyPermission([
            'add directVoucher',
            'edit directVoucher',
            'delete directVoucher',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can manage retrieval vouchers.
     */
    public function manageRetrievalVouchers(User $user): bool
    {
        return $user->hasAnyPermission([
            'add retrievalVoucher',
            'edit retrievalVoucher',
            'delete retrievalVoucher',
            'Administrator'
        ]);
    }
}
