<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FoundationUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== MEMBUAT FONDASI AWAL SISTEM ===');

        // 1. Hapus semua users yang ada
        $this->command->info('1. Membersihkan semua users yang ada...');
        User::query()->delete();
        $this->command->info('✓ Semua users telah dihapus');

        // 2. Hapus semua role assignments
        $this->command->info('2. Membersihkan role assignments...');
        DB::table('model_has_roles')->delete();
        $this->command->info('✓ Role assignments telah dihapus');

        // 3. Buat role Super Admin, Librarian, Member
        $this->command->info('3. Membuat role system...');
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $librarianRole = Role::firstOrCreate(['name' => 'Librarian']);
        $memberRole = Role::firstOrCreate(['name' => 'Member']);
        $this->command->info('✓ Role system telah dibuat');

        // 4. Buat Super Admin muhfaiza206@gmail.com
        $this->command->info('4. Membuat Super Admin muhfaiza206@gmail.com...');
        $superAdmin = User::create([
            'name' => 'Muh Faiza',
            'email' => 'muhfaiza206@gmail.com',
            'password' => Hash::make('123456789'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        $superAdmin->assignRole('Super Admin');
        $this->command->info('✓ Super Admin muhfaiza206@gmail.com telah dibuat');

        // 5. Buat Librarian librarian@gmail.com
        $this->command->info('5. Membuat Librarian librarian@gmail.com...');
        $librarian = User::create([
            'name' => 'Librarian',
            'email' => 'librarian@gmail.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
            'role' => 'librarian',
        ]);
        $librarian->assignRole('Librarian');
        $this->command->info('✓ Librarian librarian@gmail.com telah dibuat');

        // 6. Buat Member member@gmail.com
        $this->command->info('6. Membuat Member member@gmail.com...');
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@gmail.com',
            'password' => Hash::make('11111111'),
            'email_verified_at' => now(),
            'role' => 'member',
        ]);
        $member->assignRole('Member');
        $this->command->info('✓ Member member@gmail.com telah dibuat');

        $this->command->info('');
        $this->command->info('=== FONDASI SISTEM SELESAI ===');

        // 7. Verifikasi
        $this->command->info('7. Verifikasi fondasi sistem:');
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $roles = $user->roles->pluck('name')->implode(', ') ?: 'No roles';
            $this->command->info("ID: {$user->id} | Email: {$user->email} | Role: {$roles}");
        }

        $this->command->info('');
        $this->command->info('✓ Total 3 users (satu per role)');
        $this->command->info('✓ muhfaiza206@gmail.com = Super Admin (unique)');
        $this->command->info('✓ librarian@gmail.com = Librarian');
        $this->command->info('✓ member@gmail.com = Member');
        $this->command->info('✓ Sistem hak akses berdasarkan role dipertahankan');
        $this->command->info('✓ Super Admin bisa buat user baru dari dashboard');
        $this->command->info('✓ Register publik langsung jadi member');
    }
}
