<?php

namespace Tests\Feature\Api;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TravelRequestStatusTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/travel-requests';

    /* ========================================
     * APPROVE - Requisito: "Atualizar o status de um pedido de viagem para aprovado"
     * Requisito: "O usuário que fez o pedido não pode alterar o status"
     * ======================================== */

    #[Test]
    public function different_user_can_approve_requested_travel_request(): void
    {
        $requester = $this->createUser(['name' => 'Requester User']);
        $approver = $this->actingAsUser(['name' => 'Approver User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo',
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'status' => TravelRequestStatus::APPROVED->value,
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => TravelRequestStatus::APPROVED->value,
            'approved_by' => $approver->id,
        ]);

        $travelRequest->refresh();
        $this->assertNotNull($travelRequest->approved_at);
        $this->assertEquals($approver->id, $travelRequest->approved_by);
    }

    #[Test]
    public function requester_cannot_approve_their_own_travel_request(): void
    {
        $user = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => TravelRequestStatus::REQUESTED->value,
            'approved_by' => null,
        ]);
    }

    #[Test]
    public function cannot_approve_already_approved_travel_request(): void
    {
        $requester = $this->createUser();
        $approver1 = $this->createUser();
        $approver2 = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::APPROVED,
            'approved_by' => $approver1->id,
            'approved_at' => now(),
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function cannot_approve_cancelled_travel_request(): void
    {
        $requester = $this->createUser();
        $approver = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::CANCELLED,
            'cancelled_by' => $this->createUser()->id,
            'cancelled_at' => now(),
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(403);
    }

    /* ========================================
     * CANCEL - Requisito: "Atualizar o status de um pedido de viagem para cancelado"
     * Requisito: "lógica de negócios que verifique se é possível cancelar um pedido já aprovado"
     * ======================================== */

    #[Test]
    public function different_user_can_cancel_requested_travel_request(): void
    {
        $requester = $this->createUser(['name' => 'Requester User']);
        $canceller = $this->actingAsUser(['name' => 'Canceller User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo',
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'status' => TravelRequestStatus::CANCELLED->value,
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => TravelRequestStatus::CANCELLED->value,
            'cancelled_by' => $canceller->id,
        ]);

        $travelRequest->refresh();
        $this->assertNotNull($travelRequest->cancelled_at);
        $this->assertEquals($canceller->id, $travelRequest->cancelled_by);
    }

    #[Test]
    public function different_user_can_cancel_approved_travel_request(): void
    {
        $requester = $this->createUser(['name' => 'Requester User']);
        $approver = $this->createUser(['name' => 'Approver User']);
        $canceller = $this->actingAsUser(['name' => 'Canceller User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now()->subDays(2),
            'destination' => 'Rio de Janeiro',
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'status' => TravelRequestStatus::CANCELLED->value,
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => TravelRequestStatus::CANCELLED->value,
            'cancelled_by' => $canceller->id,
        ]);

        // Verify approved_by is still preserved
        $travelRequest->refresh();
        $this->assertEquals($approver->id, $travelRequest->approved_by);
        $this->assertNotNull($travelRequest->approved_at);
        $this->assertEquals($canceller->id, $travelRequest->cancelled_by);
        $this->assertNotNull($travelRequest->cancelled_at);
    }

    #[Test]
    public function requester_cannot_cancel_their_own_travel_request(): void
    {
        $user = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => TravelRequestStatus::REQUESTED->value,
            'cancelled_by' => null,
        ]);
    }

    #[Test]
    public function cannot_cancel_already_cancelled_travel_request(): void
    {
        $requester = $this->createUser();
        $canceller1 = $this->createUser();
        $canceller2 = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::CANCELLED,
            'cancelled_by' => $canceller1->id,
            'cancelled_at' => now(),
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(403);
    }

    /* ========================================
     * VALIDATION - Status update validation
     * ======================================== */

    #[Test]
    public function status_update_requires_valid_status(): void
    {
        $requester = $this->createUser();
        $approver = $this->actingAsUser(); // Different user (can change status)

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        // Invalid status
        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => 'INVALID_STATUS']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Empty status
        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function status_update_requires_authentication(): void
    {
        $requester = $this->createUser();

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(401);
    }
}

