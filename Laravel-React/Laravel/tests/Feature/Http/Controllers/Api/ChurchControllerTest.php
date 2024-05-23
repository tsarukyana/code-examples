<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Actions\Church\AssignUserToChurch;
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
     * A basic feature test store church.
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

        $response = $this->postJson('/api/churches',[
            'name' => 'Test place Name',
            'short_description' => 'Test place Address',
            'history' => 'Test Church history',
            'selected_users' => [
                ['id' => $user1->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_ADMIN], 'full_name' => $user1->full_name],
                ['id' => $user2->id, 'church_roles' => [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST], 'full_name' => $user2->full_name],
            ],
            'photo_path' => UploadedFile::fake()->image('church.jpeg'),
            'address' => 'Test place Address',
            'address_view' => 'Test Church Address for view',
            'diocese_id' => Diocese::factory()->create()->id,
            'city' => 'Test place City',
            'state' => 'Test place State',
            'country' => 'Test place Country',
            'iso2' => 'AM',
            'place_admin_name' => 'Test admin name',
            'capital' => 'Test admin capital',
            'latitude' => -42.15,
            'longitude' => 15.18,
            'instagram' => 'https://www.instagram.com',
            'facebook' => 'https://www.facebook.com',
            'website' => 'https://www.armchurch.am',
            'telegram' => 'https://www.armchurch.am',
            'regular_procedures_by_key' => [
                'liturgy' => [
                    'week_list' => [
                        'friday' => ['10:20', '10:30'],
                        'monday' => [],
                        'sunday' => [],
                        'tuesday' => [],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => [],

                    ],
                    'extra_info' => 'Test_1'
                ],
                'worship' => [
                    'week_list' => [
                        'friday' => ['11:25', '11:30'],
                        'monday' => [],
                        'sunday' => ['12:10'],
                        'tuesday' => ['13:10'],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => [],

                    ],
                    'extra_info' => null
                ],
                'sermon' => [
                    'week_list' => [
                        'friday' => ['09:00', '09:05'],
                        'monday' => ['12:00'],
                        'sunday' => [],
                        'tuesday' => ['00:00'],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => ['00:57'],

                    ],
                    'extra_info' => 'Test_3'
                ],
            ]
        ]);
        $response->assertStatus(200);

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

        // Checking the roles which created by ChurchObserver::created method
        $this->assertCount(5, Church::where('id', $response->json()['data']['id'])->first()->roles);
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

        $response = $this->postJson('/api/churches',[
            'name' => null, // invalid data
        ]);

        $response->assertJson([
            'success' => false,
            'message' => __('validation.the_validation_error_occurred'),
        ])->assertStatus(400);
    }


    /**
     * A basic feature get churches with pagination.
     *
     * @return void
     */
    public function test_get_churches_with_pagination(): void
    {
        $user = User::factory()->createWithRole();
        Sanctum::actingAs($user);

        Church::factory(2)->create();

        $response = $this->getJson('/api/churches');
        $response->assertStatus(200);
    }


    /**
     * A basic feature show church.
     *
     * @return void
     */
    public function test_show_church(): void
    {
        $user = User::factory()->createWithRole();
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $response = $this->getJson('/api/churches/' . $church->id);
        $response->assertStatus(200);
    }

    /**
     * A basic feature show church with wrong string id.
     * @return void
     */
    public function test_show_church_with_wrong_string_id(): void
    {
        $user = User::factory()->createWithRole();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/churches/abc'); // abc is wrong
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(404);
    }

    /**
     * A basic feature delete church
     * @return void
     */
    public function test_delete_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);
        $church = Church::factory()->create();

        $response = $this->deleteJson('/api/churches/' . $church->id);
        $response->assertStatus(204);
    }

    /**
     * A basic feature delete church with wrong string id
     * @return void
     */
    public function test_delete_church_with_wrong_string_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/churches/abc'); // abc is wrong

        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

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

        $response = $this->deleteJson('/api/churches/999'); // 999 is wrong
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(404);
    }

    /**
     * A basic feature update church
     * @return void
     */
    public function test_update_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);
        $church = Church::factory()->create();

        $response = $this->putJson('/api/churches/' . $church->id, [
            'name' => 'Changed place',
            'short_description' => 'Changed Description',
            'history' => 'Updated history',
            'address' => 'Updated Church Address for view',
            'address_view' => 'Updated Church Address for view',
            'diocese_id' => Diocese::factory()->create()->id,
            'photo_path' => null,
            'latitude' => -25.25,
            'longitude' => 40.25,
            'instagram' => 'https://www.instagram.com',
            'facebook' => 'https://www.facebook.com',
            'website' => 'https://www.armchurch.am',
            'telegram' => 'https://www.armchurch.am',
            'regular_procedures_by_key' => [
                'liturgy' => [
                    'week_list' => [
                        'friday' => ['10:20', '10:30'],
                        'monday' => [],
                        'sunday' => [],
                        'tuesday' => [],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => [],

                    ],
                    'extra_info' => 'Test_1'
                ],
                'worship' => [
                    'week_list' => [
                        'friday' => ['11:25', '11:30'],
                        'monday' => [],
                        'sunday' => ['12:10'],
                        'tuesday' => ['13:10'],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => [],

                    ],
                    'extra_info' => null
                ],
                'sermon' => [
                    'week_list' => [
                        'friday' => ['09:00', '09:05'],
                        'monday' => ['12:00'],
                        'sunday' => [],
                        'tuesday' => ['00:00'],
                        'saturday' => [],
                        'thursday' => [],
                        'wednesday' => ['00:57'],

                    ],
                    'extra_info' => 'Test_3'
                ],
            ]
        ]);
        $response->assertStatus(200);
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
        $response = $this->putJson('/api/churches/abc', [
            'name' => 'Changed Church',
            'short_description' => 'Changed Description',
            'latitude' => -25.25,
            'longitude' => 40.25,
        ]);

        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
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
        $response = $this->putJson('/api/churches/' . $church->id, [
            'name' => 'Changed Church',
            'short_description' => 'Changed Description',
            'latitude' => null, // invalid data
            'longitude' => 40.25,

        ]);
        $response->assertStatus(400);
    }

    /**
     * A basic feature store church role
     * @return void
     */
    public function test_store_church_role(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->postJson('/api/churches/' . $church->id . '/store-role', [
            'name' => 'Test Church Role',
            'description' => 'Test Church Role Description',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('church_roles', [
            'name' => 'Test Church Role',
            'description' => 'Test Church Role Description',
            'church_id' => $church->id,
        ]);
    }

    /**
     * A basic feature store church role with wrong string id
     * @return void
     */
    public function test_store_church_role_with_wrong_string_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/churches/wrong-id/store-role', [
            'name' => 'Test Church Role',
            'description' => 'Test Church Role Description',
        ]);

        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * A basic feature store church role with validation errors
     * @return void
     */
    public function test_store_church_role_with_validation_errors(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->postJson('/api/churches/' . $church->id . '/store-role', [
            'name' => null, // invalid data
            'description' => 'Test Church Role Description',
        ]);
        $response->assertStatus(400);
    }


    /**
     * A basic feature delete church role
     * @return void
     */
    public function test_delete_church_role(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $churchRole = $church->roles()->create([
            'name' => 'Test Church Role',
            'description' => 'Test Church Role Description',
        ]);

        // the abc is wrong in url
        $response = $this->deleteJson('/api/churches/' . $church->id . '/delete-role/' . $churchRole->id);
        $response->assertStatus(204);
    }

    /**
     * A basic feature delete church role with wrong string ids
     * @return void
     */
    public function test_delete_church_role_with_wrong_string_ids(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        // the abc is wrong in url
        $response = $this->deleteJson('/api/churches/wrong-church-id/delete-role/wrong-church-role-id');

        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * A basic feature delete church role with wrong id
     * @return void
     */
    public function test_delete_church_role_with_wrong_id(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->deleteJson('/api/churches/'. $church->id . '/delete-role/999'); // 999 is wrong
        $response->assertStatus(204);
    }

    /**
     * A basic feature update church role
     * @return void
     */
    public function test_update_church_role(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $churchRole = $church->roles()->create([
            'name' => 'Test Church Role',
            'description' => 'Test Church Role Description',
        ]);

        $response = $this->putJson('/api/churches/'. $church->id . '/update-role/' . $churchRole->id, [
            'name' => 'Changed Church Role',
            'description' => 'Changed Church Role Description',
        ]);

        $response->assertStatus(200);
    }

    /**
     * A basic feature update church role with wrong string ids
     * @return void
     */
    public function test_update_church_role_with_wrong_string_ids(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/churches/wrong-church-id/update-role/wrong-church-role-id', [
            'name' => 'Changed Church Role',
            'short_description' => 'Changed Church Role Description',
        ]);

        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * A basic feature get church roles
     * @return void
     */
    public function test_get_church_roles(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->getJson('/api/churches/'. $church->id . '/roles');
        $response->assertStatus(200);

        // the church's 4 roles has been created via ChurchObserver:created method
        $response = $this->getJson('/api/churches/'. $church->id . '/roles/' . $church->roles[0]->id);
        $response->assertStatus(200);
    }

    /**
     * A basic feature get church roles with wrong string ids
     * @return void
     */
    public function test_get_church_roles_with_wrong_string_ids(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/churches/wrong-church-id/roles');
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(400);

        $church = Church::factory()->create();
        $response = $this->getJson('/api/churches/' . $church->id . '/roles/wrong-church-role-id');
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(400);
    }

    /**
     * A basic feature assign user to church
     * @return void
     */
    public function test_assign_user_to_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $churchRoleId = $church->roles[0]->id;
        $response = $this->postJson('/api/churches/' . $church->id . '/roles/' . $churchRoleId . '/users/' . $user->id . '/assign-user');

        $response->assertStatus(200);

        $this->assertDatabaseHas('church_users', [
            'user_id' => $user->id,
            'church_id' => $church->id,
            'church_role_id' => $churchRoleId,
        ]);
    }

    /**
     * A basic feature assign user to church update user church role
     * @return void
     */
    public function test_assign_user_to_church_update_user_church_role(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        // below roles created by ChurchObserver:created method
        $oldChurchRoleId = $church->roles[0]->id;
        $churchRoleId = $church->roles[1]->id;

        ChurchUser::factory(1, [
            'church_id' => $church->id,
            'user_id' => $user->id,
            'church_role_id' => $oldChurchRoleId,
        ])->create();


        $response = $this->postJson('/api/churches/' . $church->id . '/roles/' . $churchRoleId . '/users/' . $user->id . '/assign-user');

        $response->assertStatus(200);

        // with old role
        $this->assertDatabaseMissing('church_users', [
            'user_id' => $user->id,
            'church_id' => $church->id,
            'church_role_id' => $oldChurchRoleId,
        ]);

        // with new role
        $this->assertDatabaseHas('church_users', [
            'user_id' => $user->id,
            'church_id' => $church->id,
            'church_role_id' => $churchRoleId,
        ]);
    }

    /**
     * A basic feature assign user to church with wrong string ids
     * @return void
     */
    public function test_assign_user_to_church_with_wrong_string_ids(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/churches/wrong-church-id/roles/wrong-church-roles-id/users/wrong-user-id/assign-user');
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(400);
    }



    /**
     * A basic feature unassign user from church
     * @return void
     */
    public function test_unassign_user_from_church(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $churchRoleId = $church->roles[0]->id;

        ChurchUser::factory(1, [
            'church_id' => $church->id,
            'user_id' => $user->id,
            'church_role_id' => $churchRoleId,
        ])->create();

        $response = $this->deleteJson('/api/churches/' . $church->id . '/users/' . $user->id . '/unassign-user');
        $response->assertStatus(200);

        $this->assertDatabaseMissing('church_users', [
            'user_id' => $user->id,
            'church_id' => $church->id,
//            'church_role_id' => $churchRoleId,
        ]);
    }

    /**
     * A basic feature unassign user from church with wrong string ids
     * @return void
     */
    public function test_unassign_user_from_church_with_wrong_string_ids(): void
    {
        $user = User::factory()->createWithRole(Role::SUPER_ADMIN);
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/churches/wrong-church-id/users/wrong-user-id/unassign-user');
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(400);
    }

    /**
     * A basic feature follow the church
     * @return void
     */
    public function test_follow_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::BELIEVER);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->postJson('api/churches/' . $church->id . '/follow');
        $response->assertStatus(200);
    }

    /**
     * A basic feature follow the invalid church
     * @return void
     */
    public function test_follow_invalid_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::BELIEVER);
        Sanctum::actingAs($user);

        $response = $this->postJson('api/churches/999/follow'); // the 999 is invalid church id
        $response->assertStatus(404);
    }

    /**
     * A basic feature unfollow the church
     * @return void
     */
    public function test_unfollow_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::BELIEVER);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();
        $churchBelieverRole = $church->roles()->where('church_roles.name', '=', ChurchRole::DEFAULT_ROLE_BELIEVER)->first();
        AssignUserToChurch::run($church, $churchBelieverRole, $user);

        $response = $this->postJson('api/churches/' . $church->id . '/unfollow');
        $response->assertJson([
            'success' => true,
            'data' => true,
            'message' => __('church.unassigned_the_user_from_church'),
        ]);
        $response->assertStatus(200);
    }

    /**
     * A basic feature unfollow the invalid church
     * @return void
     */
    public function test_unfollow_invalid_church(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::BELIEVER);
        Sanctum::actingAs($user);

        $response = $this->postJson('api/churches/999/unfollow'); // the 999 is invalid church id
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);
        $response->assertStatus(404);
    }

    /**
     * A basic feature unfollow the church which did not follow
     * @return void
     */
    public function test_unfollow_church_which_did_not_follow(): void
    {
        $user = User::factory()->state(['church_id' => null])->createWithRole(Role::BELIEVER);
        Sanctum::actingAs($user);

        $church = Church::factory()->create();

        $response = $this->postJson('api/churches/' . $church->id . '/unfollow');
        $response->assertJson([
            'success' => true,
            'data' => false,
            'message' => __('church.unassigned_the_user_from_church'),
        ]);
        $response->assertStatus(200);
    }

    /**
     * Test get churches.
     *
     * @return void
     */
    public function test_get_churches_by_guest(): void
    {
        $response = $this->getJson('/api/churches/get-by-guest');
        $response->assertStatus(200);
    }

    /**
     * Test get church.
     *
     * @return void
     */
    public function test_get_church_by_guest(): void
    {
        $church = Church::factory()->create();
        $response = $this->getJson("/api/churches/get-by-guest/$church->id");
        $responseContent = json_decode($response->getContent(), true)['data'];
        $this->assertArrayHasKey('events', $responseContent);

        $response->assertStatus(200);
    }

    /**
     * Test get church.
     *
     * @return void
     */
    public function test_get_church_by_guest_with_wrong_string_id(): void
    {
        $response = $this->getJson('/api/churches/get-by-guest/abc'); // abc is wrong
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test get church.
     *
     * @return void
     */
    public function test_get_church_by_guest_with_wrong_id(): void
    {
        $response = $this->getJson('/api/churches/get-by-guest/0'); // 0 is wrong
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => __('validation.invalid_type_error'),
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test get churches with distances.
     *
     * @return void
     */
    public function test_get_churches_with_distances(): void
    {
        Church::factory()->create();
        $response = $this->getJson('/api/churches/get-churches-coordinates');
        $response->assertStatus(200);
    }
}
