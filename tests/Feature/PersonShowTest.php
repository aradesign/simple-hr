<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_view_person_profile_with_educations(): void
    {
        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->create();

        $response = $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.persons.show', $person));

        $response->assertOk();
        $response->assertSee($person->first_name);
    }
}
