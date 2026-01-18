<?php

namespace App\Policies;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TravelRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     * Users can list their own travel requests.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Users can only view their own travel requests.
     */
    public function view(User $user, TravelRequest $travelRequest): bool
    {
        return $travelRequest->isRequesterUser($user->id);
    }

    /**
     * Determine whether the user can create models.
     * Any authenticated user can create a travel request.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only the requester can update their own travel request,
     * and only if it's still in REQUESTED status.
     */
    public function update(User $user, TravelRequest $travelRequest): Response
    {
        if (!$travelRequest->isRequesterUser($user->id)) {
            return Response::deny('You can only update your own travel requests.');
        }

        if ($travelRequest->status !== TravelRequestStatus::REQUESTED) {
            return Response::deny('You can only update travel requests that are in REQUESTED status.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can approve the model.
     * Only other users (not the requester) can approve requests.
     * The request must be in REQUESTED status.
     */
    public function approve(User $user, TravelRequest $travelRequest): Response
    {
        if ($travelRequest->isRequesterUser($user->id)) {
            return Response::deny('You cannot approve your own travel request.');
        }

        if (!$travelRequest->status->canBeApproved()) {
            return Response::deny('Only travel requests in REQUESTED status can be approved.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can cancel the model.
     * - The requester can cancel their own request (REQUESTED or APPROVED).
     * - Other users (managers/approvers) can also cancel any request.
     * - Cannot cancel if already CANCELLED.
     */
    public function cancel(User $user, TravelRequest $travelRequest): Response
    {
        if (!$travelRequest->status->canBeCancelled()) {
            return Response::deny('This travel request cannot be cancelled.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     * Users can only soft delete their own travel requests,
     * and only if they are in REQUESTED status (not yet approved).
     */
    public function delete(User $user, TravelRequest $travelRequest): Response
    {
        if (!$travelRequest->isRequesterUser($user->id)) {
            return Response::deny('You can only delete your own travel requests.');
        }

        if ($travelRequest->status !== TravelRequestStatus::REQUESTED) {
            return Response::deny('You can only delete travel requests that are in REQUESTED status.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     * Not implemented - soft deletes are not used in this application.
     */
    public function restore(User $user, TravelRequest $travelRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Force delete is not allowed to maintain audit trail.
     */
    public function forceDelete(User $user, TravelRequest $travelRequest): bool
    {
        return false;
    }
}
