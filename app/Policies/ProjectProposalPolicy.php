<?php

namespace App\Policies;

use App\Auth\NostrUser;
use App\Enums\ProjectProposalStatus;
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
     * Allowed for: board members (always) OR active members with paid membership for the current year.
     */
    public function create(NostrUser $user): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->isBoardMember() || $pleb->hasPaidMembership();
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
            || $pleb->isBoardMember();
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
            || $pleb->isBoardMember();
    }

    /**
     * Determine whether the user can see the submitter's contact preference.
     * Contact details are personal data on an otherwise public page, so they are
     * limited to board members and the submitter.
     */
    public function viewContact(?NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user?->getPleb();

        if (! $pleb) {
            return false;
        }

        return $pleb->id === $projectProposal->einundzwanzig_pleb_id
            || $pleb->isBoardMember();
    }

    /**
     * Determine whether the user can record a payout.
     *
     * Board membership alone is not enough: money may only follow a resolution.
     * The proposal must carry the board's absolute majority in favour and must
     * not already be paid out — otherwise a single board member could pay out a
     * proposal that is still being voted on, or one the board rejected.
     */
    public function payout(NostrUser $user, ProjectProposal $projectProposal): bool
    {
        $pleb = $user->getPleb();

        if (! $pleb || ! $pleb->isBoardMember()) {
            return false;
        }

        return $projectProposal->status() === ProjectProposalStatus::Accepted;
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

        return $pleb->isBoardMember();
    }
}
