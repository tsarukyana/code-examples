<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Auth;

// Requests
use App\Http\Requests\Church\IndexChurchRequest;
use App\Http\Requests\Church\StoreChurchRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

// Models
use App\Models\Church;
use App\Models\ChurchRole;
use App\Models\Role;

// Actions
use App\Actions\Church\AssignUserToChurchWithSelectedUsers;
use App\Actions\Church\FetchChurchUsersWithRoles;
use App\Actions\Diocese\FetchAllDioceses;
use App\Actions\Church\FetchChurches;
use App\Actions\Church\CreateChurch;
use App\Actions\Church\DeleteChurch;
use App\Actions\Church\UpdateChurch;
use App\Actions\Church\FetchUsers;
use App\Actions\Church\AddChurchRegularProcedure;
use App\Actions\Church\UpdateChurchRegularProcedure;

class ChurchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexChurchRequest $request
     * @return Response
     */
    public function index(IndexChurchRequest $request): Response
    {
        $validatedData = $request->validated();
        $loggedUser = $request->user();
        $filters = $request->filters ?? [];

        if (! $loggedUser->isSuperAdmin()) {
            if ($loggedUser->isDioceseAdmin()) {
                $dioceses = $loggedUser->dioceses()->get();
                $filters['diocese_ids'] = $loggedUser->dioceses()->pluck('dioceses.id')->toArray();

                foreach ($dioceses as $diocese) {
                    $filters['church_ids'] = array_merge($filters['church_ids'] ?? [], $diocese->churches()->pluck('churches.id')->toArray());
                }
            } else {
                $filters['church_ids'] = $loggedUser->churches()->pluck('churches.id')->toArray();
            }
        }

        if (! empty($request->search)) {
            $filters['search'] = trim($request->search);
        }

        $filters['page'] = $validatedData['page'] ?? null;
        $filters['pageSize'] = $validatedData['pageSize'] ?? null;

        // for order by
        $filters['order_by'] = $validatedData['order_by'] ?? 'name'; // default is name
        $filters['order_by_direction'] = $validatedData['order_by_direction'] ?? 'asc'; // default is asc
        $filters['latitude'] = isset($validatedData['latitude']) ? (float)$validatedData['latitude'] : null; // 'between:-90,90'
        $filters['longitude'] = isset($validatedData['longitude']) ? (float)$validatedData['longitude'] : null; // 'between:-180,180'
        $filters['diocese'] = $validatedData['diocese'] ?? null;

        $churches = FetchChurches::run(min($request->pageSize ?? self::DEFAULT_LIMIT, self::MAX_LIMIT), $filters);
        $churchIds = $churches->pluck('id')->toArray();
        $churchRoles = array_filter(ChurchRole::DEFAULT_ROLES, fn($role) => $role != ChurchRole::DEFAULT_ROLE_BELIEVER);

        $dioceses = FetchAllDioceses::run(['id', 'name' ], $filters);

        return Inertia::render('Churches/Index', [
            'churches' => $churches,
            'church_users_with_roles' => FetchChurchUsersWithRoles::run($churchIds, true, $churchRoles),
            'filters' => $filters,
            'dioceses' => $dioceses,
            'diocese' => $request->diocese
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): Response
    {
        $loggedUser = Auth::user();
        $filters = [
            'diocese_ids' => null
        ];

        $users = FetchUsers::run(['id', 'name', 'surname'], [Role::PRIEST, Role::ADMINISTRATOR])
            ->map(fn ($u) => ['id' => $u->id, 'full_name' => $u->full_name])
            ->toArray();

        if ($loggedUser->isDioceseAdmin()) {
            $filters['diocese_ids'] = $loggedUser->dioceses()->pluck('dioceses.id')->toArray();
        }

        return Inertia::render('Churches/Create', [
            'google_api_key' => config('app.google_api_key'),
            'google_api_url' => config('app.google_api_url'),
            'users' => $users,
            'church_roles' => ChurchRole::defaultChurchPriestRoles(),
            'dioceses' => FetchAllDioceses::run(['id', 'name'], $filters),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreChurchRequest $request
     * @return RedirectResponse
     */
    public function store(StoreChurchRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $church = CreateChurch::run($validatedData);
        if (! $church) {
            return redirect(route('churches.index'))->with(['error' => __('validation.errors.invalid_data')]);
        }
        AssignUserToChurchWithSelectedUsers::run($church, $validatedData['selected_users'] ?? null);
        AddChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);
        return redirect(route('churches.index'))->with(['message' => __('church.stored_the_church')]);
    }

    /**
     * Display the specified resource.
     *
     * @param Church $church
     * @return Response
     */
    public function show(Church $church): Response
    {
        return Inertia::render('Churches/Show', [
            'church' => $church,
            'church_users_with_roles' => FetchChurchUsersWithRoles::run([$church->id]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Church $church
     * @return Response
     */
    public function edit(Church $church): Response
    {
        $loggedUser = Auth::user();
        $users = FetchUsers::run(['id', 'name', 'surname'], [Role::PRIEST, Role::ADMINISTRATOR])
            ->map(fn ($u) => ['id' => $u->id, 'full_name' => $u->full_name])
            ->toArray();
        $churchId = $church->id;
        $filters = [
            'diocese_ids' => null
        ];

        if (!empty($church->social_links)){
            $church['instagram'] = $church->social_links['instagram'] ?? null;
            $church['facebook'] = $church->social_links['facebook'] ?? null;
            $church['website'] = $church->social_links['website'] ?? null;
            $church['telegram'] = $church->social_links['telegram'] ?? null;
        }

        if ($loggedUser->isDioceseAdmin()) {
            $filters['diocese_ids'] = $loggedUser->dioceses()->pluck('dioceses.id')->toArray();
        }

        $church->load('regularProcedures'); // Make sure regularProcedures is loaded
        $church->regular_procedures_by_key = $church->regularProceduresByKey; // Assign the attribute

        return Inertia::render('Churches/Edit', [
            'church' => $church,
            'users' => $users,
            'church_users' => $church->churchUsers()
                ->with(['churchesRoles' => function($q) use ($churchId) {
                    $q->where('church_users.church_id', '=', $churchId);
                    $q->select(['church_roles.id', 'church_roles.name']);
                }])
                ->role([Role::PRIEST, Role::ADMINISTRATOR])
                ->get(['users.id', 'name', 'surname'])
                ->each(function ($u) {
                    $u->setAppends(['full_name']);
                })
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'church_roles' => $u->getRelation('churchesRoles')->pluck('name')->toArray(),
                ])
                ->toArray(),
            'google_api_key' => config('app.google_api_key'),
            'google_api_url' => config('app.google_api_url'),
            'church_roles' => $church->roles()->pluck('name')->toArray(),
            'dioceses' => FetchAllDioceses::run(['id', 'name'], $filters),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreChurchRequest $request
     * @param Church $church
     * @return RedirectResponse
     */
    public function update(StoreChurchRequest $request, Church $church): RedirectResponse
    {
        $validatedData = $request->validated();
        $church = UpdateChurch::run($validatedData, $church);
        if (! $church) {
            return redirect(route('churches.index'))->with(['error' => __('validation.errors.invalid_data')]);
        }

        AssignUserToChurchWithSelectedUsers::run($church, $validatedData['selected_users'] ?? null, true);
        UpdateChurchRegularProcedure::run($church, $validatedData['regular_procedures_by_key'] ?? null);
        return redirect(route('churches.index'))->with(['message' =>  __('church.updated_the_church')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Church $church
     * @return RedirectResponse
     */
    public function destroy(Church $church): RedirectResponse
    {
        if (! DeleteChurch::run($church)) {
            return redirect(route('churches.index'))->with(['message' => __('validation.errors.cannot_delete_item')]);
        }
        return redirect(route('churches.index'))->with(['message' => __('church.deleted_the_church')]);
    }
}
