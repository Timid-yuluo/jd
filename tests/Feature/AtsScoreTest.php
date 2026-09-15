<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtsScoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->resume = Resume::factory()->create([
            'user_id' => $this->user->id,
            'content_raw' => str_repeat('PHP Laravel MySQL API development experience with RESTful services. ', 10),
        ]);
    }

    public function test_ats_report_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get("/user/resumes/{$this->resume->id}/ats-report")
            ->assertOk();
    }

    public function test_ats_score_requires_auth(): void
    {
        $this->post("/user/resumes/{$this->resume->id}/ats-score")
            ->assertRedirect('/login');
    }

    public function test_ats_score_first_time_not_limited(): void
    {
        $this->resume->update(['ats_score' => null]);

        $this->actingAs($this->user)
            ->post("/user/resumes/{$this->resume->id}/ats-score")
            ->assertRedirect();
    }

    public function test_ats_score_daily_limit_enforced(): void
    {
        $this->resume->update(['ats_score' => 50]);

        for ($i = 0; $i < 3; $i++) {
            \App\Models\UsageLog::create([
                'user_id' => $this->user->id,
                'scenario' => 'resume_ats',
                'meta' => ['resume_id' => $this->resume->id],
            ]);
        }

        $this->actingAs($this->user)
            ->post("/user/resumes/{$this->resume->id}/ats-score")
            ->assertSessionHas('warning');
    }

    public function test_cannot_ats_score_other_users_resume(): void
    {
        $other = User::factory()->create();
        $resume = Resume::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user)
            ->post("/user/resumes/{$resume->id}/ats-score")
            ->assertForbidden();
    }
}
