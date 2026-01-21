<?php

namespace App\Listeners;

use App\Enums\TravelRequestStatus;
use App\Events\TravelRequestStatusUpdated;
use App\Notifications\TravelRequestStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTravelRequestStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TravelRequestStatusUpdated $event): void
    {
        if (!$this->shouldNotify($event->newStatus)) {
            return;
        }

        // Load the requester relationship if not already loaded
        if (!$event->travelRequest->relationLoaded('requester')) {
            $event->travelRequest->load('requester');
        }

        // Load approver or canceller based on status
        if ($event->newStatus === TravelRequestStatus::APPROVED) {
            $event->travelRequest->load('approver');
        } elseif ($event->newStatus === TravelRequestStatus::CANCELLED) {
            $event->travelRequest->load('canceller');
        }

        // Send notification to the requester
        $event->travelRequest->requester->notify(
            new TravelRequestStatusNotification(
                $event->travelRequest,
                $event->oldStatus,
                $event->newStatus
            )
        );
    }

    private function shouldNotify(TravelRequestStatus $status): bool
    {
        return in_array($status, [
            TravelRequestStatus::APPROVED,
            TravelRequestStatus::CANCELLED,
        ], true);
    }
}

