<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class FoundationUserSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Librarian']);
        Role::firstOrCreate(['name' => 'Member']);

        $superAdmin = User::updateOrCreate(
            ['email' => 'muhfaiza206@gmail.com'],
            [
                'name' => 'Muh Faiza',
                'password' => '123456789',
                'email_verified_at' => now(),
                ...(Schema::hasColumn('users', 'role') ? ['role' => 'admin'] : []),
            ],
        );
        $superAdmin->syncRoles(['Super Admin']);

        $librarian = User::updateOrCreate(
            ['email' => 'librarian@gmail.com'],
            [
                'name' => 'Librarian',
                'password' => '12345678',
                'email_verified_at' => now(),
                ...(Schema::hasColumn('users', 'role') ? ['role' => 'librarian'] : []),
            ],
        );
        $librarian->syncRoles(['Librarian']);

        $member = User::updateOrCreate(
            ['email' => 'member@gmail.com'],
            [
                'name' => 'Member',
                'password' => '11111111',
                'email_verified_at' => now(),
                ...(Schema::hasColumn('users', 'role') ? ['role' => 'member'] : []),
            ],
        );
        $member->syncRoles(['Member']);

        User::role('Super Admin')
            ->where('email', '!=', 'muhfaiza206@gmail.com')
            ->each(function (User $user): void {
                $user->removeRole('Super Admin');
            });
    }
}
