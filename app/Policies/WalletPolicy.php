<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isClient();
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $user->isClient() && $wallet->user_id === $user->getKey();
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $this->update($user, $wallet);
    }
}
