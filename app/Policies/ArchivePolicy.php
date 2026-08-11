<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

class ArchivePolicy
{
    /**
     * Determine if the user can view any archives.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['show archives', 'Administrator']);
    }

    /**
     * Determine if the user can view the archive.
     */
    public function view(User $user, Archive $archive): bool
    {
        return $user->hasAnyPermission(['show archives', 'Administrator']);
    }

    /**
     * Determine if the user can create archives.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['add archive', 'Administrator']);
    }

    /**
     * Determine if the user can update the archive.
     */
    public function update(User $user, Archive $archive): bool
    {
        return $user->hasAnyPermission(['edit archive', 'Administrator']);
    }

    /**
     * Determine if the user can delete the archive.
     */
    public function delete(User $user, Archive $archive): bool
    {
        return $user->hasAnyPermission(['delete archive', 'Administrator']);
    }

    /**
     * Determine if the user can manage archive types.
     */
    public function manageArchiveTypes(User $user): bool
    {
        return $user->hasAnyPermission([
            'add archiveType',
            'edit archiveType',
            'delete archiveType',
            'Administrator'
        ]);
    }

    /**
     * Determine if the user can manage documents.
     */
    public function manageDocuments(User $user): bool
    {
        return $user->hasAnyPermission([
            'add document',
            'delete document',
            'Administrator'
        ]);
    }
}
