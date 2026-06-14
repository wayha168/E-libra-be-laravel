<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAuthor() || $user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('view_categories');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        if ($user->isAuthor() || $user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $this->hasCategoryPermission($user, $category, 'view_categories');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_categories');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $this->hasCategoryPermission($user, $category, 'edit_categories');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $this->hasCategoryPermission($user, $category, 'delete_categories');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return $this->hasCategoryPermission($user, $category, 'edit_categories');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $this->hasCategoryPermission($user, $category, 'delete_categories');
    }

    private function hasCategoryPermission(User $user, Category $category, string $permissionName): bool
    {
        if ($user->hasPermission($permissionName)) {
            return true;
        }

        return \App\Models\UserCategoryPermission::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->whereHas('permission', function ($q) use ($permissionName) {
                $q->where('name', $permissionName);
            })
            ->exists();
    }
}
