<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    public function getAllUsers(?string $search = null, ?User $actor = null): Collection
    {
        if ($actor && $actor->hasRole('Librarian')) {
            return $this->userRepository->getAllWhereRoleIn(['Member', 'Librarian'], $search);
        }

        return $this->userRepository->getAll($search);
    }

    public function canEditUser(User $actor, User $target): bool
    {
        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        if ($actor->hasRole('Librarian')) {
            return $target->hasRole('Member');
        }

        return false;
    }

    public function canDeleteUser(User $actor, User $target): bool
    {
        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        return false;
    }

    public function findUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function getUserFormData(): array
    {
        return [
            'roles' => $this->userRepository->getRoles(),
        ];
    }

    public function storeUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = $data['role'];

            if ($role === 'Super Admin') {
                throw ValidationException::withMessages([
                    'role' => 'The Super Admin role cannot be assigned through this form.',
                ]);
            }

            $user = $this->userRepository->create(Arr::except($data, ['role']));

            $this->userRepository->syncRole($user, $role);

            return $user->load('roles');
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $role = $data['role'];

            if ($role === 'Super Admin') {
                throw ValidationException::withMessages([
                    'role' => 'The Super Admin role cannot be assigned through this form.',
                ]);
            }

            if ($user->hasRole('Super Admin')) {
                throw ValidationException::withMessages([
                    'role' => 'The Super Admin account cannot be modified through this form.',
                ]);
            }

            $payload = Arr::except($data, ['role']);

            if (blank($payload['password'] ?? null)) {
                unset($payload['password']);
            }

            $this->userRepository->update($user, $payload);
            $this->userRepository->syncRole($user, $role);

            return $user->load('roles');
        });
    }

    public function deleteUser(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'user' => 'The Super Admin account cannot be deleted.',
            ]);
        }

        $this->userRepository->delete($user);
    }
}
