<?php

namespace App\Events;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelRequestStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public TravelRequest $travelRequest,
        public TravelRequestStatus $oldStatus,
        public TravelRequestStatus $newStatus
    ) {
        //
    }
}
