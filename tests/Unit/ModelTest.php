<?php

namespace Tests\Unit;

use App\Models\HelpCategory;
use App\Models\Plan;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_resumes_relationship(): void
    {
        $user = User::factory()->create();
        Resume::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->resumes);
    }

    public function test_user_has_api_tokens_relationship(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->apiTokens());
    }

    public function test_resume_build_modules_snapshot(): void
    {
        $user = User::factory()->create();
        $resume = Resume::factory()->create(['user_id' => $user->id]);
        $resume->modules()->create(['type' => 'personal', 'data' => ['name' => 'Test'], 'sort_order' => 0]);
        $resume->modules()->create(['type' => 'education', 'data' => ['school' => 'MIT'], 'sort_order' => 1]);

        $snapshot = $resume->buildModulesSnapshot();

        $this->assertCount(2, $snapshot);
        $this->assertEquals('personal', $snapshot[0]['type']);
        $this->assertEquals('education', $snapshot[1]['type']);
    }

    public function test_resume_scopes(): void
    {
        $user = User::factory()->create();
        Resume::factory()->create(['user_id' => $user->id, 'ats_score' => 80]);
        Resume::factory()->create(['user_id' => $user->id, 'ats_score' => null]);
        Resume::factory()->create(['user_id' => $user->id, 'ats_score' => 60]);

        $this->assertCount(2, Resume::scored()->get());
        $this->assertCount(1, Resume::unscored()->get());
    }

    public function test_plan_find_by_slug_caches(): void
    {
        $plan = Plan::factory()->create(['slug' => 'test-plan', 'is_active' => true]);

        $found = Plan::findBySlug('test-plan');
        $this->assertNotNull($found);
        $this->assertEquals('test-plan', $found->slug);

        $notFound = Plan::findBySlug('nonexistent');
        $this->assertNull($notFound);
    }

    public function test_help_category_cached_visible(): void
    {
        HelpCategory::factory()->create(['is_visible' => true, 'sort_order' => 1]);
        HelpCategory::factory()->create(['is_visible' => true, 'sort_order' => 2]);
        HelpCategory::factory()->create(['is_visible' => false, 'sort_order' => 3]);

        $visible = HelpCategory::cachedVisible();
        $this->assertCount(2, $visible);
    }
}
