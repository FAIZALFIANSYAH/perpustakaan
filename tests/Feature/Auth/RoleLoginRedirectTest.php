<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_redirects_to_admin_dashboard(): void
    {
        $user = $this->createUserWithRole('Super Admin', 'muhfaiza206@gmail.com', '123456789');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '123456789',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_librarian_redirects_to_librarian_dashboard(): void
    {
        $user = $this->createUserWithRole('Librarian', 'librarian@gmail.com', '12345678');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('librarian.dashboard', absolute: false));
    }

    public function test_member_redirects_to_member_dashboard(): void
    {
        $user = $this->createUserWithRole('Member', 'member@gmail.com', '11111111');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '11111111',
        ]);

        $response->assertRedirect(route('member.dashboard', absolute: false));
    }

    private function createUserWithRole(string $roleName, string $email, string $password): User
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Librarian']);
        Role::firstOrCreate(['name' => 'Member']);

        $user = User::factory()->create([
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole($roleName);

        return $user;
    }
}

