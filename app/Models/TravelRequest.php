<?php

namespace App\Models;

use App\Enums\TravelRequestStatus;
use Illuminate\Database\Eloquent\Builder;
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
        'status',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'approved_by',
        'cancelled_by',
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
     * Set default status when creating a new instance.
     */
    protected static function booted(): void
    {
        static::creating(function (TravelRequest $travelRequest) {
            if (empty($travelRequest->status)) {
                $travelRequest->status = TravelRequestStatus::REQUESTED;
            }
        });
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
    public function scopeWithStatus(Builder $query, TravelRequestStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include requested travel requests.
     */
    public function scopeRequested(Builder $query): Builder
    {
        return $query->where('status', TravelRequestStatus::REQUESTED);
    }

    /**
     * Scope a query to only include approved travel requests.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', TravelRequestStatus::APPROVED);
    }

    /**
     * Scope a query to only include cancelled travel requests.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TravelRequestStatus::CANCELLED);
    }

    /**
     * Scope a query to only include travel requests that can be cancelled.
     */
    public function scopeCancellable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TravelRequestStatus::REQUESTED,
            TravelRequestStatus::APPROVED
        ]);
    }

    /**
     * Scope a query to only include travel requests that can be approved.
     */
    public function scopeApprovable(Builder $query): Builder
    {
        return $query->where('status', TravelRequestStatus::REQUESTED);
    }

    /**
     * Scope a query to filter by destination.
     */
    public function scopeByDestination(Builder $query, string $destination): Builder
    {
        return $query->where('destination', 'like', '%' . $destination . '%');
    }

    /**
     * Scope a query to filter by departure date range.
     */
    public function scopeByDepartureDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('departure_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by return date range.
     */
    public function scopeByReturnDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('return_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by created at date range.
     */
    public function scopeByCreatedAtRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by travel date range (departure or return within range).
     */
    public function scopeByTravelDateRange(Builder $query, $startDate, $endDate): Builder
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
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('requester_user_id', $userId);
    }

    /**
     * Change the status of the travel request and update related fields.
     *
     * @param TravelRequestStatus $newStatus
     * @param User $user The user performing the action
     * @return TravelRequestStatus The old status before the change
     */
    public function changeStatus(TravelRequestStatus $newStatus, User $user): TravelRequestStatus
    {
        $oldStatus = $this->status;

        $updateData = ['status' => $newStatus];

        if ($newStatus === TravelRequestStatus::APPROVED) {
            $updateData['approved_by'] = $user->id;
            $updateData['approved_at'] = now();
        } elseif ($newStatus === TravelRequestStatus::CANCELLED) {
            $updateData['cancelled_by'] = $user->id;
            $updateData['cancelled_at'] = now();
        }

        $this->update($updateData);

        return $oldStatus;
    }
}
