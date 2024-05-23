<?php

namespace App\Http\Controllers\Api;

use App\Actions\Church\AddChurchRegularProcedure;
use App\Actions\Church\AssignUserToChurchWithSelectedUsers;
use App\Actions\Church\FetchAllChurches;
use App\Actions\Church\FetchChurchUsersWithRoles;
use App\Actions\Church\GetChurchUserDistance;
use App\Actions\Church\UpdateChurchRegularProcedure;
use App\Http\Requests\Church\IndexChurchByGuestRequest;
use App\Http\Requests\Church\ShowChurchByGuestRequest;
use App\Http\Requests\Church\ShowChurchRequest;
use App\Models\ChurchRole;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Church\IndexChurchRequest;
use App\Http\Requests\Church\StoreChurchRequest;
use App\Http\Requests\Church\StoreChurchRoleRequest;

// Models
use App\Models\Church;
use App\Models\User;

// Actions
use App\Actions\Church\FetchChurches;
use App\Actions\Church\AssignUserToChurch;
use App\Actions\Church\CreateChurchRole;
use App\Actions\Church\DeleteChurch;
use App\Actions\Church\CreateChurch;
use App\Actions\Church\DeleteChurchRole;
use App\Actions\Church\UnassignUserFromChurch;
use App\Actions\Church\UpdateChurch;
use App\Actions\Church\UpdateChurchRole;

class ChurchController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexChurchRequest $request
     * @return JsonResponse
     */
    public function index(IndexChurchRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $filters = $request->filters ?? [];
        if (! empty($request->search)) {
            $filters['search'] = trim($request->search);
        }
        $loggedUser = $request->user();
        if ($loggedUser && !$loggedUser->isSuperAdmin()) {
            $filters['church_ids'] = $loggedUser->churches()->pluck('churches.id')->toArray();
        }

        $filters['page'] = $validatedData['page'] ?? null;
        $filters['pageSize'] = $validatedData['pageSize'] ?? null;

        // for order by
        $filters['order_by'] = $validatedData['order_by'] ?? 'name'; // default is name
        $filters['order_by_direction'] = $validatedData['order_by_direction'] ?? 'asc'; // default is asc
        $filters['latitude'] = isset($validatedData['latitude']) ? (float)$validatedData['latitude'] : null; // 'between:-90,90'
        $filters['longitude'] = isset($validatedData['longitude']) ? (float)$validatedData['longitude'] : null; // 'between:-180,180'

        return $this->handleResponse(
            FetchChurches::run(min($request->pageSize ?? self::DEFAULT_LIMIT, self::MAX_LIMIT), $filters),
            __('church.got_churches'),
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreChurchRequest  $request
     * @return JsonResponse
     */
    public function store(StoreChurchRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $church = CreateChurch::run($validatedData);
        AssignUserToChurchWithSelectedUsers::run($church, $validatedData['selected_users'] ?? null);
        AddChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);
        return $this->handleResponse($church, __('church.stored_the_church'));
    }

    /**
     * Display the specified resource.
     *
     * @param ShowChurchRequest $request
     * @param Church $church
     * @return JsonResponse
     */
    public function show(ShowChurchRequest $request, Church $church): JsonResponse
    {
        $validatedData = $request->validated();
        $priestIds = User::role(ChurchRole::DEFAULT_ROLE_PRIEST)->pluck('id')->toArray();
        $church->church_priests = FetchChurchUsersWithRoles::run([$church->id], false, [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST, ChurchRole::DEFAULT_ROLE_PRIEST, ChurchRole::DEFAULT_ROLE_VISITING_PASTOR], $priestIds);
        $church->load('regularProcedures');
        $coordinates = [
            'church_latitude' => $church->coordinate->latitude, // 'between:-180,180'
            'church_longitude' => $church->coordinate->longitude, // 'between:-180,180'
            'latitude' => isset($validatedData['latitude']) ? (float)$validatedData['latitude'] : null, // 'between:-180,180'
            'longitude' => isset($validatedData['longitude']) ? (float)$validatedData['longitude'] : null, // 'between:-180,180'
        ];

        $church->distance = GetChurchUserDistance::run($coordinates);
        $church->is_followed = $request->user()->churches()->where('churches.id', $church->id)->exists()
            || $request->user()->church()->where('churches.id', $church->id)->exists();

        return $this->handleResponse($church, __('church.shown_the_church'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  StoreChurchRequest $request
     * @param  Church $church
     * @return JsonResponse
     */
    public function update(StoreChurchRequest $request, Church $church): JsonResponse
    {
        $validatedData = $request->validated();
        $church = UpdateChurch::run($validatedData, $church);
        AssignUserToChurchWithSelectedUsers::run($church, $validatedData['selected_users'] ?? null, true);
        UpdateChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);

        return $this->handleResponse($church, __('church.updated_the_church'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Church $church
     * @return JsonResponse
     */
    public function destroy(Church $church): JsonResponse
    {
        if (! DeleteChurch::run($church)) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.cannot_delete_item')]);
        }
        return $this->handleResponse(null, __('church.deleted_the_church'), 204);
    }

    /**
     * Get roles the specified church for.
     * @param int $churchId
     * @param int|null $churchRoleId
     * @return JsonResponse
     */
    public function roles(int $churchId, ?int $churchRoleId = null): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        $roles = $church->roles();
        if ($churchRoleId) {
            $roles->where('id', $churchRoleId);
        }

        return $this->handleResponse(
            $churchRoleId ? $roles->first() : $roles->get(),
            __('church.got_the_church_roles'),
        );
    }

    /**
     * Store the specified role for.
     * @param StoreChurchRoleRequest $request
     * @param int $churchId
     * @return JsonResponse
     */
    public function storeRole(StoreChurchRoleRequest $request, int $churchId): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        return $this->handleResponse(
            CreateChurchRole::run($church, $request->validated()),
            __('church.stored_the_church_role'),
        );
    }

    /**
     * Update the specified role for.
     * @param StoreChurchRoleRequest $request
     * @param int $churchId
     * @param int $churchRoleId
     * @return JsonResponse
     */
    public function updateRole(StoreChurchRoleRequest $request, int $churchId, int $churchRoleId): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        return $this->handleResponse(
            UpdateChurchRole::run($church, $request->validated(), $churchRoleId),
            __('church.updated_the_church_role'),
        );
    }

    /**
     * Delete the specified role for.
     * @param int $churchId
     * @param int $churchRoleId
     * @return JsonResponse
     */
    public function deleteRole(int $churchId, int $churchRoleId): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        return $this->handleResponse(
            DeleteChurchRole::run($church, $churchRoleId),
            __('church.deleted_the_church_role'),
        204
        );
    }

    /**
     * Assign user to the specified role for.
     * @param int $churchId
     * @param int $churchRoleId
     * @param int $userId
     * @return JsonResponse
     */
    public function assignUser(int $churchId, int $churchRoleId, int $userId): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        $churchRole = $church->roles()->where('id', $churchRoleId)->first();
        if (! $churchRole) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        $user = User::find($userId);
        if (! $user) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        return $this->handleResponse(
            AssignUserToChurch::run($church, $churchRole, $user),
            __('church.assigned_the_user_to_church')
        );
    }

    /**
     * Assign user to the specified role for.
     * @param int $churchId
     * @param int $userId
     * @return JsonResponse
     */
    public function unassignUser(int $churchId, int $userId): JsonResponse
    {
        $church = Church::find($churchId);
        if (! $church) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        $user = User::find($userId);
        if (! $user) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.invalid_data')]);
        }

        return $this->handleResponse(
            UnassignUserFromChurch::run($church, $user),
            __('church.unassigned_the_user_from_church')
        );
    }

     /**
     * @param Church $church
     * @return JsonResponse
     */
    public function follow(Church $church): JsonResponse
    {
        $loggedUser = request()->user();
        $churchBelieverRole = $church->roles()->where('church_roles.name', '=', ChurchRole::DEFAULT_ROLE_BELIEVER)->first();

        return $this->handleResponse(
            AssignUserToChurch::run($church, $churchBelieverRole, $loggedUser),
            __('church.assigned_the_user_to_church')
        );
    }

    /**
     * @param Church $church
     * @return JsonResponse
     */
    public function unfollow(Church $church): JsonResponse
    {
        return $this->handleResponse(
            UnassignUserFromChurch::run($church, request()->user()),
            __('church.unassigned_the_user_from_church')
        );
    }

    /**
     * @param IndexChurchByGuestRequest $request
     * @return JsonResponse
     */
    public function churchesByGuestIndex(IndexChurchByGuestRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $filters = $request->filters ?? [];
        if (! empty($validatedData['search'])) {
            $filters['search'] = trim($validatedData['search']);
        }

        $filters['pageSize'] = $validatedData['pageSize'] ?? null;

        // for order by
        $filters['order_by'] = $validatedData['order_by'] ?? 'name'; // default is name
        $filters['diocese_id'] = $validatedData['diocese_id'] ?? null; // default is null
        $filters['order_by_direction'] = $validatedData['order_by_direction'] ?? 'asc'; // default is asc
        $filters['latitude'] = isset($validatedData['latitude']) ? (float)$validatedData['latitude'] : null; // 'between:-90,90'
        $filters['longitude'] = isset($validatedData['longitude']) ? (float)$validatedData['longitude'] : null; // 'between:-180,180'

        return $this->handleResponse(
            FetchChurches::run(min($request->pageSize ?? self::DEFAULT_LIMIT, self::MAX_LIMIT), $filters),
            __('church.got_churches')
        );
    }

    /**
     * @param ShowChurchByGuestRequest $request
     * @param Church $church
     * @return JsonResponse
     */
    public function churchByGuestShow(ShowChurchByGuestRequest $request, Church $church): JsonResponse
    {
        $validatedData = $request->validated();
        $now = Carbon::now()->format(Event::DATE_TIME_HOUR_MINUTE_FORMAT);
        $priestIds = User::role(ChurchRole::DEFAULT_ROLE_PRIEST)->pluck('id')->toArray();
        $church->church_priests = FetchChurchUsersWithRoles::run([$church->id], false, [ChurchRole::DEFAULT_ROLE_PARISH_PRIEST, ChurchRole::DEFAULT_ROLE_PRIEST, ChurchRole::DEFAULT_ROLE_VISITING_PASTOR], $priestIds);
        $church->load([
            'regularProcedures',
            'events' => function ($query) use ($now) {
                $query->whereDate('event_end_time', '>', $now);
                $query->orderBy('event_start_time')->take(3);
            }
        ]);

        $coordinates = [
            'church_latitude' => $church->coordinate->latitude, // 'between:-180,180'
            'church_longitude' => $church->coordinate->longitude, // 'between:-180,180'
            'latitude' => isset($validatedData['latitude']) ? (float)$validatedData['latitude'] : null, // 'between:-180,180'
            'longitude' => isset($validatedData['longitude']) ? (float)$validatedData['longitude'] : null, // 'between:-180,180'
        ];

        $church->distance = GetChurchUserDistance::run($coordinates);

        return $this->handleResponse($church, __('church.got_churches'));
    }

    /**
     * @return JsonResponse
     */
    public function churchesCoordinates(): JsonResponse
    {
        return $this->handleResponse(
            FetchAllChurches::run([
                'columns' => ['id', 'name', 'coordinate', 'diocese_id'],
                'orderByColumn' => 'name',
            ]),
            __('church.got_churches')
        );
    }
}
