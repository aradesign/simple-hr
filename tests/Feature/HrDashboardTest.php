<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_access_dashboard(): void
    {
        $hrUser = User::factory()->hr()->create();

        $response = $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('خوش آمدید');
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)
            ->withoutVite()
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_employee_cannot_access_hr_dashboard(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }
}
