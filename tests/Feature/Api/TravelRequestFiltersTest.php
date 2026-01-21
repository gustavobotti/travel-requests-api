<?php

namespace Tests\Feature\Api;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TravelRequestFiltersTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/travel-requests';

    /* ========================================
     * FILTER BY DESTINATION - Requisito: "Filtragem por destino"
     * ======================================== */

    #[Test]
    public function user_can_filter_travel_requests_by_destination(): void
    {
        $user = $this->actingAsUser();

        // Create requests with different destinations
        $this->createTravelRequestFor($user, ['destination' => 'São Paulo, Brazil']);
        $this->createTravelRequestFor($user, ['destination' => 'Rio de Janeiro, Brazil']);
        $this->createTravelRequestFor($user, ['destination' => 'São Paulo, SP']);

        // Filter by partial match
        $response = $this->getJson("{$this->baseUrl}?destination=São Paulo");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['destination' => 'São Paulo, Brazil'])
            ->assertJsonFragment(['destination' => 'São Paulo, SP'])
            ->assertJsonMissing(['destination' => 'Rio de Janeiro, Brazil']);
    }

    #[Test]
    public function destination_filter_is_case_insensitive(): void
    {
        $user = $this->actingAsUser();

        $this->createTravelRequestFor($user, ['destination' => 'São Paulo, Brazil']);
        $this->createTravelRequestFor($user, ['destination' => 'Rio de Janeiro']);

        // Filter with lowercase
        $response = $this->getJson("{$this->baseUrl}?destination=são paulo");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'São Paulo, Brazil']);
    }

    /* ========================================
     * FILTER BY DEPARTURE DATE - Requisito: "Filtragem por período"
     * ======================================== */

    #[Test]
    public function user_can_filter_travel_requests_by_departure_date_range(): void
    {
        $user = $this->actingAsUser();

        // Create requests with different departure dates
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 1',
            'departure_date' => '2026-02-01',
            'return_date' => '2026-02-05',
        ]);

        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 2',
            'departure_date' => '2026-02-15',
            'return_date' => '2026-02-20',
        ]);

        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 3',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-05',
        ]);

        // Filter by departure date range
        $response = $this->getJson("{$this->baseUrl}?departure_from=2026-02-01&departure_to=2026-02-20");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['destination' => 'Trip 1'])
            ->assertJsonFragment(['destination' => 'Trip 2'])
            ->assertJsonMissing(['destination' => 'Trip 3']);
    }

    /* ========================================
     * FILTER BY RETURN DATE - Requisito: "Filtragem por período"
     * ======================================== */

    #[Test]
    public function user_can_filter_travel_requests_by_return_date_range(): void
    {
        $user = $this->actingAsUser();

        // Create requests with different return dates
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 1',
            'departure_date' => '2026-02-01',
            'return_date' => '2026-02-10',
        ]);

        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 2',
            'departure_date' => '2026-02-01',
            'return_date' => '2026-02-25',
        ]);

        $this->createTravelRequestFor($user, [
            'destination' => 'Trip 3',
            'departure_date' => '2026-02-01',
            'return_date' => '2026-03-10',
        ]);

        // Filter by return date range
        $response = $this->getJson("{$this->baseUrl}?return_from=2026-02-10&return_to=2026-02-28");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['destination' => 'Trip 1'])
            ->assertJsonFragment(['destination' => 'Trip 2'])
            ->assertJsonMissing(['destination' => 'Trip 3']);
    }

    /* ========================================
     * FILTER BY CREATED AT - Requisito: "pedidos feitos dentro de uma faixa de datas"
     * ======================================== */

    #[Test]
    public function user_can_filter_travel_requests_by_creation_date_range(): void
    {
        $user = $this->actingAsUser();

        // Create requests with different creation dates
        $trip1 = $this->createTravelRequestFor($user, ['destination' => 'Trip 1']);
        $trip1->created_at = '2026-01-05 10:00:00';
        $trip1->save();

        $trip2 = $this->createTravelRequestFor($user, ['destination' => 'Trip 2']);
        $trip2->created_at = '2026-01-15 10:00:00';
        $trip2->save();

        $trip3 = $this->createTravelRequestFor($user, ['destination' => 'Trip 3']);
        $trip3->created_at = '2026-01-25 10:00:00';
        $trip3->save();

        // Filter by creation date range
        $response = $this->getJson("{$this->baseUrl}?created_from=2026-01-10&created_to=2026-01-20");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'Trip 2'])
            ->assertJsonMissing(['destination' => 'Trip 1'])
            ->assertJsonMissing(['destination' => 'Trip 3']);
    }

    /* ========================================
     * FILTER BY TRAVEL DATE RANGE - Requisito: "datas de viagem dentro de uma faixa"
     * ======================================== */

    #[Test]
    public function user_can_filter_by_travel_date_range(): void
    {
        $user = $this->actingAsUser();

        // Trip that starts within range
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip Starts In Range',
            'departure_date' => '2026-02-10',
            'return_date' => '2026-02-28',
        ]);

        // Trip that ends within range
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip Ends In Range',
            'departure_date' => '2026-01-25',
            'return_date' => '2026-02-05',
        ]);

        // Trip that encompasses the range
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip Encompasses Range',
            'departure_date' => '2026-01-01',
            'return_date' => '2026-02-28',
        ]);

        // Trip completely outside range
        $this->createTravelRequestFor($user, [
            'destination' => 'Trip Outside Range',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-10',
        ]);

        // Filter by travel date range (Feb 1-15)
        $response = $this->getJson("{$this->baseUrl}?travel_from=2026-02-01&travel_to=2026-02-15");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['destination' => 'Trip Starts In Range'])
            ->assertJsonFragment(['destination' => 'Trip Ends In Range'])
            ->assertJsonFragment(['destination' => 'Trip Encompasses Range'])
            ->assertJsonMissing(['destination' => 'Trip Outside Range']);
    }

    /* ========================================
     * COMBINED FILTERS - Testando vários filtros juntos
     * ======================================== */

    #[Test]
    public function user_can_apply_multiple_filters_together(): void
    {
        $user = $this->actingAsUser();

        // Trip that matches all filters
        $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::APPROVED,
            'destination' => 'São Paulo, Brazil',
            'departure_date' => '2026-02-10',
            'return_date' => '2026-02-15',
            'approved_by' => $this->createUser()->id,
            'approved_at' => now(),
        ]);

        // Trip that matches only destination
        $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo, Brazil',
            'departure_date' => '2026-03-10',
            'return_date' => '2026-03-15',
        ]);

        // Trip that matches only status
        $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::APPROVED,
            'destination' => 'Rio de Janeiro',
            'departure_date' => '2026-02-10',
            'return_date' => '2026-02-15',
            'approved_by' => $this->createUser()->id,
            'approved_at' => now(),
        ]);

        // Apply multiple filters
        $response = $this->getJson(
            "{$this->baseUrl}?status=APPROVED&destination=São Paulo&departure_from=2026-02-01&departure_to=2026-02-20"
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'São Paulo, Brazil']);
    }

    /* ========================================
     * PAGINATION - Filtros com paginação
     * ======================================== */

    #[Test]
    public function filters_work_with_pagination(): void
    {
        $user = $this->actingAsUser();

        // Create 20 requests with same destination
        for ($i = 1; $i <= 20; $i++) {
            $this->createTravelRequestFor($user, [
                'destination' => 'São Paulo',
                'departure_date' => now()->addDays($i)->format('Y-m-d'),
                'return_date' => now()->addDays($i + 5)->format('Y-m-d'),
            ]);
        }

        // Create 5 with different destination
        for ($i = 1; $i <= 5; $i++) {
            $this->createTravelRequestFor($user, [
                'destination' => 'Rio de Janeiro',
                'departure_date' => now()->addDays($i)->format('Y-m-d'),
                'return_date' => now()->addDays($i + 5)->format('Y-m-d'),
            ]);
        }

        // Filter by destination with pagination
        $response = $this->getJson("{$this->baseUrl}?destination=São Paulo&per_page=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 10);
    }
}

