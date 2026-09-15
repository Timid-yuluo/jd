<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_resume_index_requires_auth(): void
    {
        $this->get('/user/resumes')->assertRedirect('/login');
    }

    public function test_resume_index_shows_user_resumes(): void
    {
        Resume::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get('/user/resumes')
            ->assertOk()
            ->assertSee('简历');
    }

    public function test_resume_create_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get('/user/resumes/create')
            ->assertOk();
    }

    public function test_resume_store_creates_resume(): void
    {
        $this->actingAs($this->user)
            ->post('/user/resumes', [
                'title' => '测试简历',
                'target_job' => 'PHP 开发',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('resumes', [
            'user_id' => $this->user->id,
            'title' => '测试简历',
        ]);
    }

    public function test_resume_duplicate(): void
    {
        $resume = Resume::factory()->create(['user_id' => $this->user->id, 'title' => '原始简历']);

        $this->actingAs($this->user)
            ->post("/user/resumes/{$resume->id}/duplicate")
            ->assertRedirect();

        $this->assertDatabaseHas('resumes', [
            'user_id' => $this->user->id,
            'title' => '原始简历（副本）',
        ]);
    }

    public function test_resume_destroy_moves_to_trash(): void
    {
        $resume = Resume::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete("/user/resumes/{$resume->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('resumes', ['id' => $resume->id]);
    }

    public function test_resume_toggle_share(): void
    {
        $resume = Resume::factory()->create(['user_id' => $this->user->id, 'is_shareable' => false]);

        $this->actingAs($this->user)
            ->post("/user/resumes/{$resume->id}/toggle-share")
            ->assertRedirect();

        $resume->refresh();
        $this->assertTrue($resume->is_shareable);
        $this->assertNotNull($resume->share_token);
    }

    public function test_resume_versions_page_loads(): void
    {
        $resume = Resume::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get("/user/resumes/{$resume->id}/versions")
            ->assertOk();
    }

    public function test_cannot_access_other_user_resume(): void
    {
        $other = User::factory()->create();
        $resume = Resume::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->get("/user/resumes/{$resume->id}")
            ->assertForbidden();
    }
}
