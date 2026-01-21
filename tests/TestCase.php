<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Traits\CreatesAuthenticatedUser;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker, CreatesAuthenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function assertDatabaseHasStatus(int $id, string $status): void
    {
        $this->assertDatabaseHas('travel_requests', [
            'id' => $id,
            'status' => $status,
        ]);
    }
}
