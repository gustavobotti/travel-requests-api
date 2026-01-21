<?php

namespace App\Queries;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TravelRequestQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = TravelRequest::query();
    }

    /**
     * Eager load relationships to prevent N+1 queries.
     */
    public function withRelationships(): self
    {
        $this->query->with(['requester', 'approver', 'canceller']);
        return $this;
    }

    /**
     * Filter by user (owned by).
     */
    public function forUser(int $userId): self
    {
        $this->query->ownedBy($userId);
        return $this;
    }

    /**
     * Apply all filters from request data.
     */
    public function applyFilters(array $filters): self
    {
        if (!empty($filters['status'])) {
            $this->filterByStatus($filters['status']);
        }

        if (!empty($filters['destination'])) {
            $this->filterByDestination($filters['destination']);
        }

        if (!empty($filters['departure_from']) && !empty($filters['departure_to'])) {
            $this->filterByDepartureDateRange($filters['departure_from'], $filters['departure_to']);
        }

        if (!empty($filters['return_from']) && !empty($filters['return_to'])) {
            $this->filterByReturnDateRange($filters['return_from'], $filters['return_to']);
        }

        if (!empty($filters['created_from']) && !empty($filters['created_to'])) {
            $this->filterByCreatedAtRange($filters['created_from'], $filters['created_to']);
        }

        if (!empty($filters['travel_from']) && !empty($filters['travel_to'])) {
            $this->filterByTravelDateRange($filters['travel_from'], $filters['travel_to']);
        }

        return $this;
    }

    /**
     * Filter by status.
     */
    private function filterByStatus(string $statusValue): void
    {
        $status = TravelRequestStatus::tryFrom($statusValue);
        if ($status) {
            $this->query->withStatus($status);
        }
    }

    /**
     * Filter by destination.
     */
    private function filterByDestination(string $destination): void
    {
        $this->query->byDestination($destination);
    }

    /**
     * Filter by departure date range.
     */
    private function filterByDepartureDateRange(string $startDate, string $endDate): void
    {
        $this->query->byDepartureDateRange($startDate, $endDate);
    }

    /**
     * Filter by return date range.
     */
    private function filterByReturnDateRange(string $startDate, string $endDate): void
    {
        $this->query->byReturnDateRange($startDate, $endDate);
    }

    /**
     * Filter by created at date range.
     */
    private function filterByCreatedAtRange(string $startDate, string $endDate): void
    {
        $this->query->byCreatedAtRange($startDate, $endDate);
    }

    /**
     * Filter by travel date range.
     */
    private function filterByTravelDateRange(string $startDate, string $endDate): void
    {
        $this->query->byTravelDateRange($startDate, $endDate);
    }

    /**
     * Order results by created_at descending.
     */
    public function orderByNewest(): self
    {
        $this->query->orderBy('created_at', 'desc');
        return $this;
    }

    /**
     * Get paginated results with a maximum per page limit.
     */
    public function paginate(int $perPage = 15, int $maxPerPage = 100): LengthAwarePaginator
    {
        $perPage = min($perPage, $maxPerPage);
        return $this->query->paginate($perPage);
    }
}

