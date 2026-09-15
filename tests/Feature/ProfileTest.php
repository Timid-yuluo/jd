<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_profile_page_loads(): void
    {
        $this->actingAs($this->user)
            ->get('/user/profile')
            ->assertOk()
            ->assertSee('个人资料');
    }

    public function test_update_profile(): void
    {
        $this->actingAs($this->user)
            ->put('/user/profile', [
                'name' => '新名字',
                'school' => '清华大学',
                'major' => '计算机科学',
            ])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertEquals('新名字', $this->user->name);
        $this->assertEquals('清华大学', $this->user->school);
    }

    public function test_update_password(): void
    {
        $this->user->update(['password' => bcrypt('oldpass')]);

        $this->actingAs($this->user)
            ->put('/user/profile/password', [
                'current_password' => 'oldpass',
                'password' => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertRedirect();

        $this->assertTrue(\Hash::check('newpass123', $this->user->fresh()->password));
    }

    public function test_update_password_rejects_wrong_current(): void
    {
        $this->user->update(['password' => bcrypt('oldpass')]);

        $this->actingAs($this->user)
            ->put('/user/profile/password', [
                'current_password' => 'wrongpass',
                'password' => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_export_data(): void
    {
        $this->actingAs($this->user)
            ->get('/user/profile/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_profile_requires_auth(): void
    {
        $this->get('/user/profile')->assertRedirect('/login');
    }
}
