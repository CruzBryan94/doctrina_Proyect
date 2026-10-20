<?php

namespace App\Policies;

use App\Models\Manual;
use App\Models\User;

class ManualPolicy
{
    public function viewAny(User $user): bool
    {
        return count($user->accessiblePhaseSlugs()) > 0;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('planificador');
    }

    public function view(User $user, Manual $manual): bool
    {
        return in_array($manual->manualPhase->slug, $user->accessiblePhaseSlugs(), true);
    }

    public function update(User $user, Manual $manual): bool
    {
        return $this->view($user, $manual);
    }
}
