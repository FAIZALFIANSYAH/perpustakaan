<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UserRepository
{
    public function getAll(?string $search = null): Collection
    {
        return User::query()
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();
    }

    public function getAllWhereRoleIn(array $roleNames, ?string $search = null): Collection
    {
        return User::query()
            ->with('roles')
            ->whereHas('roles', function ($query) use ($roleNames) {
                $query->whereIn('name', $roleNames);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();
    }

    public function findById(int $id): ?User
    {
        return User::query()
            ->with('roles')
            ->find($id);
    }

    public function getRoles(): Collection
    {
        return Role::query()
            ->select('id', 'name')
            ->whereIn('name', ['Librarian', 'Member'])
            ->orderByRaw("FIELD(name, 'Librarian', 'Member')")
            ->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): ?bool
    {
        return $user->delete();
    }

    public function syncRole(User $user, string $role): void
    {
        $user->syncRoles([$role]);
    }
}
