<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private string $registerUrl = '/api/v1/register';
    private string $loginUrl = '/api/v1/login';
    private string $logoutUrl = '/api/v1/logout';
    private string $logoutAllUrl = '/api/v1/logout-all';
    private string $meUrl = '/api/v1/me';

    /* ========================================
     * REGISTRATION TESTS
     * ======================================== */

    #[Test]
    public function it_can_register_a_new_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJson([
                'message' => 'User registered successfully',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertTrue(
            Hash::check('password123', User::where('email', 'john@example.com')->first()->password)
        );

        $this->assertNotEmpty($response->json('token'));
    }

    #[Test]
    public function it_fails_to_register_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $userData = [
            'name' => 'Jane Doe',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'message' => 'The email has already been taken.',
                'errors' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
    }

    #[Test]
    public function it_fails_to_register_without_password_confirmation(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_fails_to_register_with_mismatched_password_confirmation(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_fails_to_register_with_password_less_than_8_characters(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJson([
                'errors' => [
                    'password' => ['The password field must be at least 8 characters.'],
                ],
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_fails_to_register_with_invalid_email_format(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson($this->registerUrl, $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The email field must be a valid email address.'],
                ],
            ]);

        $this->assertDatabaseMissing('users', [
            'name' => 'John Doe',
        ]);
    }

    /* ========================================
     * LOGIN TESTS
     * ======================================== */

    #[Test]
    public function it_can_login_with_valid_credentials_and_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson($this->loginUrl, $credentials);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => 'test@example.com',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    #[Test]
    public function it_fails_to_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ];

        $response = $this->postJson($this->loginUrl, $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    #[Test]
    public function it_fails_to_login_with_non_existent_email(): void
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson($this->loginUrl, $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ]);
    }

    /* ========================================
     * LOGOUT TESTS
     * ======================================== */

    #[Test]
    public function it_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        // Login with the first token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson($this->logoutUrl);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);

        // First token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $token1)[1]),
        ]);

        // Second token should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $token2)[1]),
        ]);
    }

    #[Test]
    public function it_can_logout_from_all_devices_and_revoke_all_tokens(): void
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;
        $token3 = $user->createToken('device-3')->plainTextToken;

        // Logout from all devices
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson($this->logoutAllUrl);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out from all devices successfully',
            ]);

        // All tokens should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    /* ========================================
     * ME (AUTHENTICATED USER) TESTS
     * ======================================== */

    #[Test]
    public function it_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Authenticated User',
            'email' => 'auth@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson($this->meUrl);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Authenticated User',
                    'email' => 'auth@example.com',
                ],
            ]);
    }

    #[Test]
    public function it_fails_to_return_user_data_without_authentication(): void
    {
        $response = $this->getJson($this->meUrl);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /* ========================================
     * TOKEN VALIDATION TESTS
     * ======================================== */

    #[Test]
    public function it_creates_and_validates_token_correctly(): void
    {
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Login and get token
        $loginResponse = $this->postJson($this->loginUrl, [
            'email' => 'token@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Use token to access protected route
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson($this->meUrl);

        $meResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'token@example.com',
                ],
            ]);

        // Token should exist in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'auth-token',
        ]);
    }
}

