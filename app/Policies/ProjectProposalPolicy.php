<?php

namespace App\Policies;

use App\Auth\NostrUser;
use App\Models\ProjectProposal;

class ProjectProposalPolicy
{
    /**
     * Determine whether the user can view any project proposals.
     */
    public function viewAny(?NostrUser $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project proposal.
     */
    public function view(?NostrUser $user, ProjectProposal $projectProposal): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create project proposals.
     * Requires: authenticated, association_status > 1, paid membership for current year.
     */
    public function create(NostrUser $user): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->association_status->value > 1
            && $pleb->paymentEvents()->where('year', date('Y'))->where('paid', true)->exists();
    }

    /**
     * Determine whether the user can update the project proposal.
     * Allowed for: the creator OR board members.
     */
    public function update(NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->id === $projectProposal->einundzwanzig_pleb_id
            || $this->isBoardMember($pleb);
    }

    /**
     * Determine whether the user can delete the project proposal.
     * Allowed for: the creator OR board members.
     */
    public function delete(NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->id === $projectProposal->einundzwanzig_pleb_id
            || $this->isBoardMember($pleb);
    }

    /**
     * Determine whether the user can accept/reject the project proposal.
     * Only board members can change the accepted flag and sats_paid.
     */
    public function accept(NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $this->isBoardMember($pleb);
    }

    /**
     * @param  \App\Models\EinundzwanzigPleb  $pleb
     */
    private function isBoardMember(object $pleb): bool
    {
        return in_array($pleb->npub, config('einundzwanzig.config.current_board'), true);
    }
}
