<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_user_id' => $this->requester_user_id,
            'requester_name' => $this->requester_name,
            'destination' => $this->destination,
            'departure_date' => $this->departure_date->toISOString(),
            'return_date' => $this->return_date->toISOString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            'requester' => $this->whenLoaded('requester', fn() => new UserResource($this->requester)),
            'approver' => $this->whenLoaded('approver', fn() => new UserResource($this->approver)),
            'canceller' => $this->whenLoaded('canceller', fn() => new UserResource($this->canceller)),
        ];
    }
}
