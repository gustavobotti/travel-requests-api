<?php

namespace App\Http\Controllers\Api;

use App\Enums\TravelRequestStatus;
use App\Events\TravelRequestStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTravelRequestRequest;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Requests\UpdateTravelStatusRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
use App\Queries\TravelRequestQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TravelRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports filtering by status, destination, and date ranges.
     * Users can only see their own travel requests.
     */
    public function index(IndexTravelRequestRequest $request): AnonymousResourceCollection
    {
        $travelRequests = (new TravelRequestQuery())
            ->withRelationships()
            ->forUser($request->user()->id)
            ->applyFilters($request->validated())
            ->orderByNewest()
            ->paginate($request->input('per_page', 15));

        return TravelRequestResource::collection($travelRequests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTravelRequestRequest $request): JsonResponse
    {
        $travelRequest = TravelRequest::create($request->validated());

        $travelRequest->load('requester');

        return (new TravelRequestResource($travelRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TravelRequest $travelRequest): TravelRequestResource
    {
        $this->authorize('view', $travelRequest);

        return new TravelRequestResource(
            $travelRequest->load(['requester', 'approver', 'canceller'])
        );
    }

    /**
     * Update the specified resource in storage.
     * Only the requester can update their own travel request,
     * and only if it's still in REQUESTED status.
     */
    public function update(
        UpdateTravelRequestRequest $request,
        TravelRequest $travelRequest
    ): TravelRequestResource {
        $travelRequest->update($request->validated());

        return new TravelRequestResource(
            $travelRequest->fresh(['requester', 'approver', 'canceller'])
        );
    }

    /**
     * Update the status of the travel request (approve or cancel).
     */
    public function updateStatus(
        UpdateTravelStatusRequest $request,
        TravelRequest $travelRequest
    ): TravelRequestResource {
        $newStatus = TravelRequestStatus::from($request->input('status'));

        $this->authorize(
            $newStatus === TravelRequestStatus::APPROVED ? 'approve' : 'cancel',
            $travelRequest
        );

        DB::transaction(function () use ($travelRequest, $newStatus, $request) {
            $oldStatus = $travelRequest->changeStatus($newStatus, $request->user());

            // Dispatch event for notification
            event(new TravelRequestStatusUpdated($travelRequest, $oldStatus, $newStatus));
        });

        return new TravelRequestResource(
            $travelRequest->load(['requester', 'approver', 'canceller'])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TravelRequest $travelRequest): JsonResponse
    {
        $this->authorize('delete', $travelRequest);

        $travelRequest->delete();

        return response()->json(null, 204);
    }
}
