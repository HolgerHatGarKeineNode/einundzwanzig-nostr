<?php

namespace App\Policies;

use App\Auth\NostrUser;
use App\Models\Election;

class ElectionPolicy
{
    /**
     * Determine whether the user can view any elections.
     */
    public function viewAny(?NostrUser $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the election.
     */
    public function view(?NostrUser $user, Election $election): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create elections.
     * Only board members.
     */
    public function create(NostrUser $user): bool
    {
        return $this->isBoardMember($user);
    }

    /**
     * Determine whether the user can update the election (e.g. manage candidates).
     * Only board members.
     */
    public function update(NostrUser $user, Election $election): bool
    {
        return $this->isBoardMember($user);
    }

    /**
     * Determine whether the user can delete the election.
     * Only board members.
     */
    public function delete(NostrUser $user, Election $election): bool
    {
        return $this->isBoardMember($user);
    }

    /**
     * Determine whether the user can vote in the election.
     * Requires: authenticated pleb with active or honorary status.
     */
    public function vote(NostrUser $user, Election $election): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->association_status->value >= 3;
    }

    private function isBoardMember(NostrUser $user): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return in_array($pleb->npub, config('einundzwanzig.config.current_board'), true);
    }
}
