<?php

namespace Tests\Feature\Api;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TravelRequestCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/travel-requests';

    /* ========================================
     * CREATE (STORE) - Requisito: "Criar um pedido de viagem"
     * ======================================== */

    #[Test]
    public function user_can_create_travel_request_with_valid_data(): void
    {
        $user = $this->actingAsUser();

        $travelData = [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo, Brazil',
            'departure_date' => now()->addDays(10)->format('Y-m-d'),
            'return_date' => now()->addDays(15)->format('Y-m-d'),
        ];

        $response = $this->postJson($this->baseUrl, $travelData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'requester_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                    'requester',
                ],
            ])
            ->assertJson([
                'data' => [
                    'requester_name' => 'John Doe',
                    'destination' => 'São Paulo, Brazil',
                    'status' => TravelRequestStatus::REQUESTED->value,
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo, Brazil',
            'requester_user_id' => $user->id,
            'status' => TravelRequestStatus::REQUESTED->value,
        ]);
    }

    #[Test]
    public function create_travel_request_requires_authentication(): void
    {
        $travelData = [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo, Brazil',
            'departure_date' => now()->addDays(10)->format('Y-m-d'),
            'return_date' => now()->addDays(15)->format('Y-m-d'),
        ];

        $response = $this->postJson($this->baseUrl, $travelData);

        $response->assertStatus(401);
    }

    #[Test]
    public function create_travel_request_validates_required_fields(): void
    {
        $this->actingAsUser();

        $response = $this->postJson($this->baseUrl, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['requester_name', 'destination', 'departure_date', 'return_date']);
    }

    #[Test]
    public function create_travel_request_validates_date_logic(): void
    {
        $this->actingAsUser();

        // Departure date in the past
        $response = $this->postJson($this->baseUrl, [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo',
            'departure_date' => now()->subDays(1)->format('Y-m-d'),
            'return_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['departure_date']);

        // Return date before departure date
        $response = $this->postJson($this->baseUrl, [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(10)->format('Y-m-d'),
            'return_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['return_date']);
    }

    /* ========================================
     * READ (SHOW) - Requisito: "Consultar um pedido de viagem"
     * ======================================== */

    #[Test]
    public function user_can_view_their_own_travel_request(): void
    {
        $user = $this->actingAsUser();
        $travelRequest = $this->createTravelRequestFor($user, [
            'requester_name' => 'John Doe',
            'destination' => 'Rio de Janeiro',
        ]);

        $response = $this->getJson("{$this->baseUrl}/{$travelRequest->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'requester_name',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                    'requester',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'requester_name' => 'John Doe',
                    'destination' => 'Rio de Janeiro',
                ],
            ]);
    }

    #[Test]
    public function user_cannot_view_other_users_travel_request(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user1);

        $response = $this->getJson("{$this->baseUrl}/{$travelRequest->id}");

        $response->assertStatus(403);
    }

    /* ========================================
     * LIST (INDEX) - Requisito: "Listar todos os pedidos de viagem"
     * e "opção de filtrar por status"
     * ======================================== */

    #[Test]
    public function user_can_list_only_their_own_travel_requests(): void
    {
        $user = $this->actingAsUser();
        $otherUser = $this->createUser();

        // Create requests for authenticated user
        $myRequest1 = $this->createTravelRequestFor($user, ['destination' => 'São Paulo']);
        $myRequest2 = $this->createTravelRequestFor($user, ['destination' => 'Rio de Janeiro']);

        // Create request for other user (should not appear)
        $this->createTravelRequestFor($otherUser, ['destination' => 'Brasília']);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['destination' => 'São Paulo'])
            ->assertJsonFragment(['destination' => 'Rio de Janeiro'])
            ->assertJsonMissing(['destination' => 'Brasília']);
    }

    #[Test]
    public function user_can_filter_travel_requests_by_status(): void
    {
        $user = $this->actingAsUser();

        // Create requests with different statuses
        $requested = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo',
        ]);

        $approved = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::APPROVED,
            'destination' => 'Rio de Janeiro',
            'approved_by' => $this->createUser()->id,
            'approved_at' => now(),
        ]);

        $cancelled = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::CANCELLED,
            'destination' => 'Brasília',
            'cancelled_by' => $this->createUser()->id,
            'cancelled_at' => now(),
        ]);

        // Filter by REQUESTED
        $response = $this->getJson("{$this->baseUrl}?status=REQUESTED");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'São Paulo'])
            ->assertJsonMissing(['destination' => 'Rio de Janeiro']);

        // Filter by APPROVED
        $response = $this->getJson("{$this->baseUrl}?status=APPROVED");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'Rio de Janeiro']);

        // Filter by CANCELLED
        $response = $this->getJson("{$this->baseUrl}?status=CANCELLED");
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'Brasília']);
    }

    /* ========================================
     * UPDATE (PUT/PATCH) - Requisito: "cada usuário pode ver, editar
     * e cadastrar apenas as suas próprias ordens"
     * ======================================== */

    #[Test]
    public function user_can_update_their_own_requested_travel_request(): void
    {
        $user = $this->actingAsUser();
        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo',
        ]);

        $updateData = [
            'requester_name' => 'Jane Updated',
            'destination' => 'Rio de Janeiro Updated',
            'departure_date' => now()->addDays(20)->format('Y-m-d'),
            'return_date' => now()->addDays(25)->format('Y-m-d'),
        ];

        $response = $this->putJson("{$this->baseUrl}/{$travelRequest->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'requester_name' => 'Jane Updated',
                    'destination' => 'Rio de Janeiro Updated',
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'destination' => 'Rio de Janeiro Updated',
        ]);
    }

    #[Test]
    public function user_cannot_update_other_users_travel_request(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user1);

        $response = $this->putJson("{$this->baseUrl}/{$travelRequest->id}", [
            'destination' => 'Should not update',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_cannot_update_approved_travel_request(): void
    {
        $user = $this->actingAsUser();
        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::APPROVED,
            'approved_by' => $this->createUser()->id,
            'approved_at' => now(),
        ]);

        $response = $this->putJson("{$this->baseUrl}/{$travelRequest->id}", [
            'destination' => 'Should not update',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_cannot_update_cancelled_travel_request(): void
    {
        $user = $this->actingAsUser();
        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::CANCELLED,
            'cancelled_by' => $this->createUser()->id,
            'cancelled_at' => now(),
        ]);

        $response = $this->putJson("{$this->baseUrl}/{$travelRequest->id}", [
            'destination' => 'Should not update',
        ]);

        $response->assertStatus(403);
    }

    /* ========================================
     * DELETE
     * ======================================== */

    #[Test]
    public function user_can_delete_their_own_requested_travel_request(): void
    {
        $user = $this->actingAsUser();
        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->deleteJson("{$this->baseUrl}/{$travelRequest->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('travel_requests', [
            'id' => $travelRequest->id,
        ]);
    }

    #[Test]
    public function user_cannot_delete_other_users_travel_request(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user1);

        $response = $this->deleteJson("{$this->baseUrl}/{$travelRequest->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
        ]);
    }
}

