<?php

namespace App\Models;

use App\Enums\TravelRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'requester_user_id',
        'requester_name',
        'destination',
        'departure_date',
        'return_date',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TravelRequestStatus::class,
            'departure_date' => 'date',
            'return_date' => 'date',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Available statuses
     *
     * @return array<int, string>
     */
    public static function getAvailableStatuses(): array
    {
        return TravelRequestStatus::values();
    }

    /**
     * Get the user who requested the travel.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /**
     * Get the user who approved the travel request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who cancelled the travel request.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Check if a user is the requester of this travel request.
     */
    public function isRequesterUser(int $userId): bool
    {
        return $this->requester_user_id === $userId;
    }

    /**
     * Scope a query to only include travel requests with a specific status.
     */
    public function scopeWithStatus($query, TravelRequestStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include requested travel requests.
     */
    public function scopeRequested($query)
    {
        return $query->where('status', TravelRequestStatus::REQUESTED);
    }

    /**
     * Scope a query to only include approved travel requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', TravelRequestStatus::APPROVED);
    }

    /**
     * Scope a query to only include cancelled travel requests.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', TravelRequestStatus::CANCELLED);
    }

    /**
     * Scope a query to only include travel requests that can be cancelled.
     */
    public function scopeCancellable($query)
    {
        return $query->whereIn('status', [
            TravelRequestStatus::REQUESTED,
            TravelRequestStatus::APPROVED
        ]);
    }

    /**
     * Scope a query to only include travel requests that can be approved.
     */
    public function scopeApprovable($query)
    {
        return $query->where('status', TravelRequestStatus::REQUESTED);
    }

    /**
     * Scope a query to filter by destination.
     */
    public function scopeByDestination($query, string $destination)
    {
        return $query->where('destination', 'like', '%' . $destination . '%');
    }

    /**
     * Scope a query to filter by departure date range.
     */
    public function scopeByDepartureDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('departure_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by return date range.
     */
    public function scopeByReturnDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by created at date range.
     */
    public function scopeByCreatedAtRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by travel date range (departure or return within range).
     */
    public function scopeByTravelDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('departure_date', [$startDate, $endDate])
                ->orWhereBetween('return_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('departure_date', '<=', $startDate)
                        ->where('return_date', '>=', $endDate);
                });
        });
    }

    /**
     * Scope a query to only include travel requests for a specific user.
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('requester_user_id', $userId);
    }
}
