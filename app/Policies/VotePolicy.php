<?php

namespace App\Policies;

use App\Auth\NostrUser;
use App\Models\ProjectProposal;
use App\Models\Vote;

class VotePolicy
{
    /**
     * Determine whether the user can create a vote for a project proposal.
     * Requires: authenticated user with a pleb record who has not yet voted on this proposal.
     */
    public function create(NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return ! Vote::query()
            ->where('project_proposal_id', $projectProposal->id)
            ->where('einundzwanzig_pleb_id', $pleb->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the vote.
     * Only the vote owner can update their vote.
     */
    public function update(NostrUser $user, Vote $vote): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->id === $vote->einundzwanzig_pleb_id;
    }

    /**
     * Determine whether the user can delete the vote.
     * Only the vote owner can delete their vote.
     */
    public function delete(NostrUser $user, Vote $vote): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->id === $vote->einundzwanzig_pleb_id;
    }
}
