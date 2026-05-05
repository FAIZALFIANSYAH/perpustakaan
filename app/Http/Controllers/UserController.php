<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $users = $this->userService->getAllUsers($search, $request->user())->map(function ($user) {
            $user->is_online = $user->isOnline();
            return $user;
        })->sortBy(function ($user) {
            $role = $user->roles->first()?->name;
            return match ($role) {
                'Super Admin' => 1,
                'Librarian' => 2,
                'Member' => 3,
                default => 4,
            };
        })->values();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', $this->userService->getUserFormData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->storeUser($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(Request $request, User $user): Response
    {
        if (! $this->userService->canEditUser($request->user(), $user)) {
            abort(403, 'You do not have permission to edit this user.');
        }

        $roles = $this->userService->getUserFormData()['roles'];

        if ($request->user()->hasRole('Librarian')) {
            $roles = $roles->filter(fn ($role) => $role->name === 'Member')->values();
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $this->userService->findUserById($user->id),
            'roles' => $roles,
            'canEdit' => true,
            'isSuperAdminUser' => $user->hasRole('Super Admin'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if (! $this->userService->canEditUser($request->user(), $user)) {
            abort(403, 'You do not have permission to update this user.');
        }

        $this->userService->updateUser($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if (! $this->userService->canDeleteUser($request->user(), $user)) {
            abort(403, 'You do not have permission to delete this user.');
        }

        $this->userService->deleteUser($user, $request->user());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
