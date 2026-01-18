<?php

namespace Database\Factories;

use App\Enums\TravelRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelRequest>
 */
class TravelRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departureDate = $this->faker->dateTimeBetween('-2 weeks', '+6 months');

        $returnDate = $this->faker->dateTimeBetween(
            $departureDate,
            (clone $departureDate)->modify('+14 days')
        );

        $requester = User::inRandomOrder()->first() ?? User::factory()->create();

        $destinations = [
            'São Paulo, Brazil',
            'Rio de Janeiro, Brazil',
            'Brasília, Brazil',
            'Salvador, Brazil',
            'Fortaleza, Brazil',
            'Belo Horizonte, Brazil',
            'Juiz de Fora, Brazil',
            'Manaus, Brazil',
            'Curitiba, Brazil',
            'Recife, Brazil',
            'Porto Alegre, Brazil',
            'Belém, Brazil',
            'Goiânia, Brazil',
            'Guarulhos, Brazil',
            'São Luís, Brazil',
            'Maceió, Brazil',
            'Natal, Brazil',
            'Campo Grande, Brazil',
            'Teresina, Brazil',
            'João Pessoa, Brazil',
            'Florianópolis, Brazil',
            'Cuiabá, Brazil',
            'Aracaju, Brazil',
            'Vitória, Brazil',
            'Porto Velho, Brazil',
            'Macapá, Brazil',
            'Rio Branco, Brazil',
            'Boa Vista, Brazil',
            'Palmas, Brazil',

            // Popular Global Destinations
            'New York, USA',
            'Miami, USA',
            'Los Angeles, USA',
            'San Francisco, USA',
            'Chicago, USA',
            'Las Vegas, USA',
            'Orlando, USA',
            'Tokyo, Japan',
            'Osaka, Japan',
            'Kyoto, Japan',
            'London, United Kingdom',
            'Manchester, United Kingdom',
            'Edinburgh, United Kingdom',
            'Paris, France',
            'Nice, France',
            'Lyon, France',
            'Rome, Italy',
            'Milan, Italy',
            'Venice, Italy',
            'Florence, Italy',
            'Barcelona, Spain',
            'Madrid, Spain',
            'Lisbon, Portugal',
            'Porto, Portugal',
            'Berlin, Germany',
            'Munich, Germany',
            'Frankfurt, Germany',
            'Amsterdam, Netherlands',
            'Brussels, Belgium',
            'Zurich, Switzerland',
            'Vienna, Austria',
            'Dubai, UAE',
            'Abu Dhabi, UAE',
            'Singapore',
            'Hong Kong',
            'Shanghai, China',
            'Beijing, China',
            'Seoul, South Korea',
            'Bangkok, Thailand',
            'Phuket, Thailand',
            'Bali, Indonesia',
            'Sydney, Australia',
            'Melbourne, Australia',
            'Auckland, New Zealand',
            'Buenos Aires, Argentina',
            'Santiago, Chile',
            'Lima, Peru',
            'Bogotá, Colombia',
            'Mexico City, Mexico',
            'Cancún, Mexico',
            'Toronto, Canada',
            'Vancouver, Canada',
            'Montreal, Canada',
        ];

        return [
            'requester_user_id' => $requester->id,
            'requester_name' => $requester->name,
            'destination' => $this->faker->randomElement($destinations),
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'status' => TravelRequestStatus::REQUESTED,
            'approved_by' => null,
            'approved_at' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the travel request is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            // Get a different user for approval
            $approver = User::where('id', '!=', $attributes['requester_user_id'])
                ->inRandomOrder()
                ->first() ?? User::factory()->create();

            $departureDate = $attributes['departure_date'];

            // Created date should be 5-30 days before departure (or before now if departure is in the past)
            $maxCreatedDate = min($departureDate, now());
            $createdAt = $this->faker->dateTimeBetween(
                (clone $maxCreatedDate)->modify('-30 days'),
                (clone $maxCreatedDate)->modify('-5 days')
            );

            // Approval happens between creation and departure (or now if departure passed)
            $approvedAt = $this->faker->dateTimeBetween(
                $createdAt,
                $maxCreatedDate
            );

            return [
                'status' => TravelRequestStatus::APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => $approvedAt,
                'created_at' => $createdAt,
            ];
        });
    }

    /**
     * Indicate that the travel request is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            // Get a different user for cancellation
            $canceller = User::where('id', '!=', $attributes['requester_user_id'])
                ->inRandomOrder()
                ->first() ?? User::factory()->create();

            $departureDate = $attributes['departure_date'];

            // Created date should be 3-20 days before departure (or before now if departure is in the past)
            $maxCreatedDate = min($departureDate, now());
            $createdAt = $this->faker->dateTimeBetween(
                (clone $maxCreatedDate)->modify('-20 days'),
                (clone $maxCreatedDate)->modify('-3 days')
            );

            // Cancelled timestamp should be after creation and before departure
            $cancelledAt = $this->faker->dateTimeBetween(
                $createdAt,
                $maxCreatedDate
            );

            return [
                'status' => TravelRequestStatus::CANCELLED,
                'cancelled_by' => $canceller->id,
                'cancelled_at' => $cancelledAt,
                'created_at' => $createdAt,
            ];
        });
    }

    /**
     * Indicate that the travel request was approved and then cancelled.
     * This ensures proper chronological order: created -> approved -> cancelled
     */
    public function approvedThenCancelled(): static
    {
        return $this->state(function (array $attributes) {
            // Get different users for approval and cancellation (both different from requester)
            $approver = User::where('id', '!=', $attributes['requester_user_id'])
                ->inRandomOrder()
                ->first() ?? User::factory()->create();

            // Get a different user for cancellation (not the requester)
            $canceller = User::where('id', '!=', $attributes['requester_user_id'])
                ->inRandomOrder()
                ->first() ?? User::factory()->create();

            $departureDate = $attributes['departure_date'];

            // Timeline: creation -> approval -> cancellation (all before departure/now)
            $maxDate = min($departureDate, now());

            // Created date: 10-40 days before max date
            $createdAt = $this->faker->dateTimeBetween(
                (clone $maxDate)->modify('-40 days'),
                (clone $maxDate)->modify('-10 days')
            );

            // Approved: 1-5 days after creation
            $approvedAt = $this->faker->dateTimeBetween(
                $createdAt,
                (clone $createdAt)->modify('+5 days')
            );

            // Cancelled: after approval but before departure/now
            $cancelledAt = $this->faker->dateTimeBetween(
                $approvedAt,
                $maxDate
            );

            return [
                'status' => TravelRequestStatus::CANCELLED,
                'approved_by' => $approver->id,
                'approved_at' => $approvedAt,
                'cancelled_by' => $canceller->id,
                'cancelled_at' => $cancelledAt,
                'created_at' => $createdAt,
            ];
        });
    }

    /**
     * Create a travel request with a specific requester.
     */
    public function forRequester(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'requester_user_id' => $user->id,
            'requester_name' => $user->name,
        ]);
    }

    /**
     * Create a travel request with past dates (completed trip).
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $departureDate = $this->faker->dateTimeBetween('-90 days', '-15 days');
            $returnDate = $this->faker->dateTimeBetween(
                $departureDate,
                (clone $departureDate)->modify('+14 days')
            );

            return [
                'departure_date' => $departureDate,
                'return_date' => $returnDate,
            ];
        });
    }

    /**
     * Create a travel request with future dates (upcoming trip).
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $departureDate = $this->faker->dateTimeBetween('+5 days', '+120 days');
            $returnDate = $this->faker->dateTimeBetween(
                $departureDate,
                (clone $departureDate)->modify('+14 days')
            );

            return [
                'departure_date' => $departureDate,
                'return_date' => $returnDate,
            ];
        });
    }
}
