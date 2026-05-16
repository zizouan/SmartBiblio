<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        if ($this->isStaff($actor)) {
            return true;
        }

        return $actor->id === $target->id;
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->role?->value === UserRole::Admin->value) {
            return true;
        }

        if ($actor->role?->value === UserRole::Librarian->value && $target->role?->value === UserRole::Reader->value) {
            return true;
        }

        return $actor->id === $target->id;
    }

    private function isStaff(User $user): bool
    {
        $role = $user->role?->value ?? $user->role;

        return in_array($role, [UserRole::Admin->value, UserRole::Librarian->value], true);
    }
}
