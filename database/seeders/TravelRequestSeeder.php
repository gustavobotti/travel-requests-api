<?php

namespace Database\Seeders;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TravelRequestSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have users to work with
        $testUser = User::where('email', 'test@example.com')->first();
        $adminUser = User::where('email', 'admin@example.com')->first();
        $allUsers = User::all();

        if ($allUsers->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Create REQUESTED travel requests (pending approval)
        // Test user has 3 pending requests
        if ($testUser) {
            TravelRequest::factory()
                ->count(3)
                ->forRequester($testUser)
                ->upcoming()
                ->create();
        }

        // Other users have pending requests
        TravelRequest::factory()
            ->count(5)
            ->upcoming()
            ->create();

        // Create APPROVED travel requests
        // Test user has 2 approved upcoming trips
        if ($testUser) {
            TravelRequest::factory()
                ->count(2)
                ->forRequester($testUser)
                ->upcoming()
                ->approved()
                ->create();
        }

        // Admin has 1 approved upcoming trip
        if ($adminUser) {
            TravelRequest::factory()
                ->forRequester($adminUser)
                ->upcoming()
                ->approved()
                ->create();
        }

        // Various approved trips (mix of past and upcoming)
        TravelRequest::factory()
            ->count(8)
            ->approved()
            ->create();

        // Create CANCELLED travel requests (cancelled without approval)
        // Test user has 1 cancelled request
        if ($testUser) {
            TravelRequest::factory()
                ->forRequester($testUser)
                ->cancelled()
                ->create();
        }

        // Other users have cancelled requests
        TravelRequest::factory()
            ->count(4)
            ->cancelled()
            ->create();

        // Create CANCELLED travel requests (approved then cancelled)
        // These have both approval and cancellation timestamps in correct order
        if ($testUser) {
            TravelRequest::factory()
                ->forRequester($testUser)
                ->upcoming()
                ->approvedThenCancelled()
                ->create();
        }

        if ($adminUser) {
            TravelRequest::factory()
                ->forRequester($adminUser)
                ->approvedThenCancelled()
                ->create();
        }

        // Various approved-then-cancelled trips
        TravelRequest::factory()
            ->count(5)
            ->approvedThenCancelled()
            ->create();

        // Create some past completed trips (all approved)
        TravelRequest::factory()
            ->count(10)
            ->past()
            ->approved()
            ->create();

        $this->command->info('Travel requests seeded successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Requested: ' . TravelRequest::requested()->count());
        $this->command->info('- Approved: ' . TravelRequest::approved()->count());
        $this->command->info('- Cancelled: ' . TravelRequest::cancelled()->count());
        $this->command->info('- Total: ' . TravelRequest::count());
    }
}
