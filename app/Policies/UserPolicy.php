<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isAnalyst();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isClient()) {
            return $user->is($model);
        }

        return $this->isResponsibleFor($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isManager() || $user->isAnalyst();
    }

    public function viewCreateForm(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    public function sendInvitations(User $user): bool
    {
        return $user->isAnalyst();
    }

    private function isResponsibleFor(User $user, User $model): bool
    {
        return $model->manager_id === $user->getKey()
            && (($user->isManager() && $model->isAnalyst())
                || ($user->isAnalyst() && $model->isClient()));
    }
}
