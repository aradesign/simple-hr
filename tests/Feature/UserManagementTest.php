<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_manager_can_create_user_linked_to_person(): void
    {
        $manager = User::factory()->hrManager()->create();
        $person = Person::factory()->employee()->create([
            'first_name' => 'علی',
            'last_name' => 'تستی',
            'mobile' => '09120000055',
        ]);

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'علی تستی',
            'email' => 'ali.test@example.com',
            'mobile' => '09120000055',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Employee->value,
            'hr_access' => false,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'ali.test@example.com',
            'person_id' => $person->id,
            'role' => UserRole::Employee->value,
        ]);
    }

    public function test_cannot_link_same_person_to_two_users(): void
    {
        $manager = User::factory()->hrManager()->create();
        $person = Person::factory()->employee()->create();
        User::factory()->employee($person)->create();

        $response = $this->actingAs($manager)->post(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'تکراری',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Employee->value,
        ]);

        $response->assertSessionHasErrors('person_id');
    }

    public function test_create_form_lists_only_persons_without_user_account(): void
    {
        $manager = User::factory()->hrManager()->create();
        $freePerson = Person::factory()->employee()->create(['first_name' => 'آزاد']);
        $linkedPerson = Person::factory()->employee()->create(['first_name' => 'متصل']);
        User::factory()->employee($linkedPerson)->create();

        $response = $this->actingAs($manager)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('آزاد');
        $response->assertDontSee('متصل');
    }
}
