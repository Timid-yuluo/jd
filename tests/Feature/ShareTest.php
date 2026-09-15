<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_resume_page_loads(): void
    {
        $user = User::factory()->create();
        $resume = Resume::factory()->create([
            'user_id' => $user->id,
            'is_shareable' => true,
            'share_token' => 'test-token-123',
        ]);

        $this->get('/share/test-token-123')
            ->assertOk()
            ->assertSee($resume->title);
    }

    public function test_shared_resume_requires_shareable_flag(): void
    {
        $user = User::factory()->create();
        Resume::factory()->create([
            'user_id' => $user->id,
            'is_shareable' => false,
            'share_token' => 'private-token',
        ]);

        $this->get('/share/private-token')->assertNotFound();
    }

    public function test_invalid_share_token_returns_404(): void
    {
        $this->get('/share/nonexistent-token')->assertNotFound();
    }

    public function test_welcome_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_help_page_loads(): void
    {
        $this->get('/help')->assertOk();
    }
}
