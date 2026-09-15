<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ])->assertRedirect('/user/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_password(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password123')]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_with_nonexistent_email(): void
    {
        $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('password123'),
            'is_suspended' => true,
        ]);

        $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_register_creates_user(): void
    {
        $this->post('/register', [
            'name' => '新用户',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => '新用户',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }
}
