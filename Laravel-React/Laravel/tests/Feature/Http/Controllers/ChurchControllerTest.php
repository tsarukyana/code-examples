<?php

namespace Tests\Feature\Http\Controllers;

use App\Actions\Church\AddChurchRegularProcedure;
use App\Models\Church;
use App\Models\ChurchRole;
use App\Models\ChurchUser;
use App\Models\Diocese;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChurchControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_get_church(): void
    {
        $user = User::factory()->createWithRole();

        Sanctum::actingAs($user);

        Church::factory(2)->create();

        $response = $this->getJson('/churches');
        $response->assertStatus(200);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);

        Sanctum::actingAs($user);

        $response = $this->getJson('/churches/create');
        $response->assertStatus(200);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_store_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::SUPER_ADMIN);
        $user1 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);
        $user2 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);

        Sanctum::actingAs($user);
        Image::shouldReceive('make')->times(2)->andReturnSelf();
        Image::shouldReceive('resize')->times(2)->andReturnSelf();
        Image::shouldReceive('save')->times(2)->andReturnSelf();

        $response = $this->postJson('/churches',[
            'name' => 'Test Church Name',
            'short_description' => 'Test Church Description',
            'history' => 'Test Church history',
            'photo_path' => UploadedFile::fake()->image('church.jpeg'),
            'selected_users' => [
                ['id' => $user1->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_ADMIN], 'full_name' => $user1->full_name],
                ['id' => $user2->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST], 'full_name' => $user2->full_name],
            ],
            'address' => 'Test Church Address',
            'address_view' => 'Test Church Address for view',
            'diocese_id' => Diocese::factory()->create()->id,
            'city' => 'Test Church City',
            'state' => 'Test Church State',
            'country' => 'Test Church Country',
            'iso2' => 'AM',
            'place_admin_name' => 'Test admin name',
            'capital' => 'Test admin capital',
            'latitude' => -42.15,
            'longitude' => 15.18,
            'instagram' => 'https://www.instagram.com',
            'facebook' => 'https://www.facebook.com',
            'website' => 'https://www.armchurch.am',
            'telegram' => 'https://www.armchurch.am',
        ]);

        $response->assertRedirect(route('churches.index'));
        $response->assertStatus(302);

        $church = Church::first();
        $newChurchUsers = ChurchUser::where('church_id', '=', $church->id)->get()->toArray();

        $admin = ChurchRole::where('church_id', '=', $church->id)->where('name', '=', ChurchRole::DEFAULT_ROLE_ADMIN)->first(['id']);
        $parishPriest = ChurchRole::where('church_id', '=', $church->id)->where('name', '=', ChurchRole::DEFAULT_ROLE_PARISH_PRIEST)->first(['id']);

        // Administrator role
        $this->assertEquals([
            $church->id,
            $user1->id,
            $admin->id,
        ], [
            $newChurchUsers[0]['church_id'],
            $newChurchUsers[0]['user_id'],
            $newChurchUsers[0]['church_role_id'],
        ]);


        // Parish Priest role
        $this->assertEquals([
            $church->id,
            $user2->id,
            $parishPriest->id,
        ], [
            $newChurchUsers[1]['church_id'],
            $newChurchUsers[1]['user_id'],
            $newChurchUsers[1]['church_role_id'],
        ]);

        // Photo upload
        $photoPath = $church->photo_path;
        // Assert the file was stored...
        Storage::disk('public')->assertExists($photoPath);
        Storage::delete($photoPath);
    }

    /**
     * A basic feature store church with validation errors.
     *
     * @return void
     */
    public function test_store_church_with_validation_errors(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);

        Sanctum::actingAs($user);

        $response = $this->postJson('/churches',[
            'name' => null, // invalid data
        ]);

        $response->assertJson([
            'success' => false,
            'message' => __('validation.the_validation_error_occurred'),
        ])->assertStatus(400);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_edit_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);

        Sanctum::actingAs($user);

        $newChurch = Church::factory()->create();
        $createdChurchId = $newChurch->id;

        $response = $this->getJson("/churches/$createdChurchId/edit");
        $response->assertStatus(200);
    }

    /**
     * A basic feature edit church with wrong permission.
     *
     * @return void
     */
    public function test_edit_church_with_right_permission(): void
    {
        $user = User::factory()->createWithRole(Role::PRIEST);
        Sanctum::actingAs($user);

        $newChurch = Church::factory()->create();
        $churchRolePriest = $newChurch->roles()->where('church_roles.name', '=', ChurchRole::DEFAULT_ROLE_PARISH_PRIEST)->first();
        ChurchUser::factory()->state([
            'church_id' => $newChurch->id,
            'user_id' => $user->id,
            'church_role_id' => $churchRolePriest->id,
        ])->create();

        $response = $this->getJson("/churches/$newChurch->id/edit");
        $response->assertStatus(200);
    }

    /**
     * A basic feature edit church with wrong permission.
     *
     * @return void
     */
    public function test_edit_church_with_wrong_permission(): void
    {
        $user = User::factory()->createWithRole(Role::PRIEST);
        Sanctum::actingAs($user);

        $newChurch = Church::factory()->create();
        $churchRolePriest = $newChurch->roles()->where('church_roles.name', '=', ChurchRole::DEFAULT_ROLE_PARISH_PRIEST)->first(['id']);
        ChurchUser::factory()->state([
            'church_id' => $newChurch->id,
            'user_id' => $user->id,
            'church_role_id' => $churchRolePriest->id,
        ])->create();

        $otherChurch = Church::factory()->create();

        $response = $this->getJson("/churches/$otherChurch->id/edit");
        $response->assertStatus(403);
    }

    /**
     * A basic feature update church
     * @return void
     */
    public function test_update_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::SUPER_ADMIN);
        $user1 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);
        $user2 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);

        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        AddChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);
        $regularProcedures = $church->regularProcedures->pluck(null, 'name')->toArray();

        $response = $this->putJson("/churches/$church->id", [
            'name' => 'Updated Church Name',
            'short_description' => 'Updated Description',
            'history' => 'Updated history',
            'address' => 'Updated Church Address for view',
            'address_view' => 'Updated Church Address for view',
            'diocese_id' => Diocese::factory()->create()->id,
            'selected_users' => [
                ['id' => $user1->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_ADMIN], 'full_name' => $user1->full_name],
                ['id' => $user2->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST], 'full_name' => $user2->full_name],
            ],
            'photo_path' => null,
            'regular_procedures_by_key' => $regularProcedures,
            'latitude' => -25.25,
            'longitude' => 40.25,
            'instagram' => 'https://www.instagram.com',
            'facebook' => 'https://www.facebook.com',
            'website' => 'https://www.armchurch.am',
            'telegram' => 'https://www.armchurch.am',
        ]);

        $updatedChurch = Church::find($church->id);
        $this->assertSame($updatedChurch->name, 'Updated Church Name');

        $response->assertStatus(302);
    }

    /**
     * A basic feature update church
     * @return void
     */
    public function test_update_church_users_role(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::SUPER_ADMIN);
        $user1 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);
        $user2 = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);

        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        AddChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);
        $regularProcedures = $church->regularProcedures->pluck(null, 'name')->toArray();

        $response = $this->putJson("/churches/$church->id", [
            'name' => 'Updated Church Name',
            'history' => 'Updated history',
            'address' => 'Updated Church Address for view',
            'address_view' => 'Updated Church Address for view',
            'diocese_id' => Diocese::factory()->create()->id,
            'selected_users' => [
                ['id' => $user1->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_ADMIN], 'full_name' => $user1->full_name],
                ['id' => $user2->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST], 'full_name' => $user2->full_name],
            ],
            'photo_path' => null,
            'regular_procedures_by_key' => $regularProcedures,
            'latitude' => -25.25,
            'longitude' => 40.25,
            'instagram' => 'https://www.instagram.com',
            'facebook' => 'https://www.facebook.com',
            'website' => 'https://www.armchurch.am',
            'telegram' => 'https://www.armchurch.am',
        ]);

        $response->assertRedirect(route('churches.index'));
        $response->assertStatus(302);

        $updatedChurchUsers = ChurchUser::where('church_id', '=', $church->id)->get()->toArray();

        $admin = ChurchRole::where('church_id', '=', $church->id)->where('name', '=', ChurchRole::DEFAULT_ROLE_ADMIN)->first(['id']);
        $parishPriest = ChurchRole::where('church_id', '=', $church->id)->where('name', '=', ChurchRole::DEFAULT_ROLE_PARISH_PRIEST)->first(['id']);

        // Administrator role
        $this->assertEquals([
            $church->id,
            $user1->id,
            $admin->id,
        ], [
            $updatedChurchUsers[0]['church_id'],
            $updatedChurchUsers[0]['user_id'],
            $updatedChurchUsers[0]['church_role_id'],
        ]);

        // Parish Priest role
        $this->assertEquals([
            $church->id,
            $user2->id,
            $parishPriest->id,
        ], [
            $updatedChurchUsers[1]['church_id'],
            $updatedChurchUsers[1]['user_id'],
            $updatedChurchUsers[1]['church_role_id'],
        ]);
    }

    /**
     * A basic feature update church with wrong string id
     * @return void
     */
    public function test_update_church_with_wrong_string_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        // the abc is wrong in url
        $response = $this->putJson('/churches/abc', [
            'name' => 'Changed Church',
            'short_description' => 'Changed Description',
            'latitude' => -25.25,
            'longitude' => 40.25,
        ]);

        $response->assertStatus(404);
    }

    /**
     * A basic feature update church with validation errors
     * @return void
     */
    public function test_update_church_with_validation_errors(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        // the abc is wrong in url
        $response = $this->putJson('/churches/' . $church->id, [
            'name' => 'Changed Church',
            'short_description' => 'Changed Description',
            'latitude' => null, // invalid data
            'longitude' => 40.25,

        ]);
        $response->assertStatus(400);
    }

    /**
     * A basic feature delete church
     * @return void
     */
    public function test_delete_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $response = $this->deleteJson("/churches/" . $church->id);

        $response->assertRedirect(route('churches.index'));
        $response->assertStatus(302);
    }

    /**
     * A basic feature delete church with wrong string id
     * @return void
     */
    public function test_delete_church_with_wrong_string_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/churches/abc'); // abc is wrong

        $response->assertStatus(404);
    }

    /**
     * A basic feature delete church with wrong id
     * @return void
     */
    public function test_delete_church_with_wrong_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/churches/999'); // 999 is wrong
        $response->assertStatus(404);
    }

    /**
     * A basic feature delete church with wrong permission
     * @return void
     */
    public function test_delete_church_with_wrong_permission(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::PRIEST);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $response = $this->deleteJson("/churches/" . $church->id);
        $response->assertStatus(403);
    }
}
