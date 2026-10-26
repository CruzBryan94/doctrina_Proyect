<?php

namespace App\Policies;

use App\Models\Manual;
use App\Models\User;

class ManualPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canConsultManuals();
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->accessiblePhaseSlugs() !== [] || $this->createPublished($user));
    }

    public function createInPhase(User $user, string $phase): bool
    {
        return $phase === 'published' ? $this->createPublished($user)
            : $user->is_active && in_array($phase, $user->accessiblePhaseSlugs(), true);
    }

    public function view(User $user, Manual $manual): bool
    {
        return $user->canConsultManuals();
    }

    public function update(User $user, Manual $manual): bool
    {
        return $user->is_active && in_array($manual->manualPhase->slug, $user->accessiblePhaseSlugs(), true);
    }

    public function updateMetadata(User $user, Manual $manual): bool
    {
        return $user->is_active && ($manual->is_published
            ? $user->hasAnyRole(['admin', 'publicados'])
            : $this->update($user, $manual));
    }

    public function createPublished(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['admin', 'publicados', 'cgft']);
    }
}
