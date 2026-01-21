<?php

namespace Tests\Traits;

use App\Models\TravelRequest;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesAuthenticatedUser
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function actingAsUser(User|array|null $userOrAttributes = null): User
    {
        if (is_array($userOrAttributes)) {
            $user = $this->createUser($userOrAttributes);
        } else {
            $user = $userOrAttributes ?? $this->createUser();
        }

        Sanctum::actingAs($user);
        return $user;
    }

    protected function createTravelRequestFor(User $user, array $attributes = []): TravelRequest
    {
        return TravelRequest::factory()
            ->for($user, 'requester')
            ->create($attributes);
    }
}
