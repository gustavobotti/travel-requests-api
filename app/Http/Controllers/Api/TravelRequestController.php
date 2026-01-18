<?php

namespace App\Http\Controllers\Api;

use App\Enums\TravelRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\TravelRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Travel Request Controller
 *
 * Handles all travel request operations following the challenge requirements.
 */
class TravelRequestController extends Controller
{
    /**
     * Display a listing of the user's travel requests.
     * Users can only see their own travel requests.
     *
     * Filters: status, destination, date ranges
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TravelRequest::class);

        $query = TravelRequest::query()
            ->ownedBy($request->user()->id)
            ->with(['requester', 'approver', 'canceller']);

        // Filter by status
        if ($request->has('status')) {
            $status = TravelRequestStatus::tryFrom($request->status);
            if ($status) {
                $query->withStatus($status);
            }
        }

        // Filter by destination
        if ($request->filled('destination')) {
            $query->byDestination($request->destination);
        }

        // Filter by travel date range
        if ($request->filled(['travel_start_date', 'travel_end_date'])) {
            $query->byTravelDateRange(
                $request->travel_start_date,
                $request->travel_end_date
            );
        }

        // Filter by created at date range
        if ($request->filled(['created_start_date', 'created_end_date'])) {
            $query->byCreatedAtRange(
                $request->created_start_date,
                $request->created_end_date
            );
        }

        $travelRequests = $query->latest()->paginate(15);

        return response()->json($travelRequests);
    }

    /**
     * Store a newly created travel request.
     * Any authenticated user can create a travel request.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TravelRequest::class);

        $validated = $request->validate([
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:departure_date',
        ]);

        $travelRequest = TravelRequest::create([
            'requester_user_id' => $request->user()->id,
            'requester_name' => $request->user()->name,
            'destination' => $validated['destination'],
            'departure_date' => $validated['departure_date'],
            'return_date' => $validated['return_date'],
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        return response()->json($travelRequest->load('requester'), 201);
    }

    /**
     * Display the specified travel request.
     * Users can only view their own travel requests.
     */
    public function show(TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('view', $travelRequest);

        return response()->json(
            $travelRequest->load(['requester', 'approver', 'canceller'])
        );
    }

    /**
     * Update the specified travel request details.
     * Users can only update their own requests and only if status is REQUESTED.
     * Note: Status cannot be changed via this endpoint. Use approve() or cancel() instead.
     */
    public function update(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('update', $travelRequest);

        $validated = $request->validate([
            'destination' => 'sometimes|string|max:255',
            'departure_date' => 'sometimes|date|after_or_equal:today',
            'return_date' => 'sometimes|date|after:departure_date',
        ]);

        $travelRequest->update($validated);

        return response()->json($travelRequest->load('requester'));
    }

    /**
     * Approve a travel request.
     * Business rule: User CANNOT approve their own request.
     * Only other users (managers/approvers) can approve.
     */
    public function approve(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('approve', $travelRequest);

        $travelRequest->status = TravelRequestStatus::APPROVED;
        $travelRequest->approved_by = $request->user()->id;
        $travelRequest->approved_at = now();
        $travelRequest->save();

        // TODO: Dispatch notification event
        // event(new TravelRequestApproved($travelRequest));

        return response()->json([
            'message' => 'Travel request approved successfully.',
            'data' => $travelRequest->load(['requester', 'approver']),
        ]);
    }

    /**
     * Cancel a travel request.
     * Business rule: Can cancel REQUESTED or APPROVED requests.
     * Both requester and managers can cancel.
     */
    public function cancel(Request $request, TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('cancel', $travelRequest);

        $travelRequest->status = TravelRequestStatus::CANCELLED;
        $travelRequest->cancelled_by = $request->user()->id;
        $travelRequest->cancelled_at = now();
        $travelRequest->save();

        // TODO: Dispatch notification event
        // event(new TravelRequestCancelled($travelRequest));

        return response()->json([
            'message' => 'Travel request cancelled successfully.',
            'data' => $travelRequest->load(['requester', 'canceller']),
        ]);
    }

    /**
     * Remove the specified travel request.
     * Users can only delete their own requests and only if status is REQUESTED.
     */
    public function destroy(TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('delete', $travelRequest);

        $travelRequest->delete();

        return response()->json([
            'message' => 'Travel request deleted successfully.',
        ]);
    }
}

