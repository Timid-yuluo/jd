<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_notification_index_loads(): void
    {
        $this->actingAs($this->user)
            ->get('/user/notifications')
            ->assertOk();
    }

    public function test_notification_show(): void
    {
        $notification = UserNotification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => '测试通知',
            'content' => '通知内容',
        ]);

        $this->actingAs($this->user)
            ->get("/user/notifications/{$notification->id}")
            ->assertOk()
            ->assertSee('测试通知');
    }

    public function test_mark_notification_read(): void
    {
        $notification = UserNotification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => '未读通知',
            'content' => '内容',
        ]);

        $this->actingAs($this->user)
            ->post("/user/notifications/{$notification->id}/read")
            ->assertRedirect();

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_read(): void
    {
        UserNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $this->actingAs($this->user)
            ->post('/user/notifications/mark-all-read')
            ->assertRedirect();

        $this->assertDatabaseCount('user_notifications', 3);
        $this->assertEquals(0, UserNotification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_delete_notification(): void
    {
        $notification = UserNotification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => '要删除的通知',
            'content' => '内容',
        ]);

        $this->actingAs($this->user)
            ->delete("/user/notifications/{$notification->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('user_notifications', ['id' => $notification->id]);
    }

    public function test_cannot_view_other_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = UserNotification::create([
            'user_id' => $other->id,
            'type' => 'system',
            'title' => '别人的通知',
            'content' => '内容',
        ]);

        $this->actingAs($this->user)
            ->get("/user/notifications/{$notification->id}")
            ->assertForbidden();
    }
}
