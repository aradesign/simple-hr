<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->withoutVite()
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_hr_manager_cannot_access_settings(): void
    {
        $manager = User::factory()->hrManager()->create();

        $this->actingAs($manager)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_user_can_edit_own_profile(): void
    {
        $user = User::factory()->hr()->create();

        $this->actingAs($user)
            ->withoutVite()
            ->get(route('admin.profile.edit'))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'نام جدید',
                'email' => $user->email,
                'mobile' => '09121111111',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'نام جدید',
            'mobile' => '09121111111',
        ]);
    }
}
