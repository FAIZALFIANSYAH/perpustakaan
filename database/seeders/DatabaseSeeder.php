<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Category;
use App\Models\Book;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Librarian']);
        Role::firstOrCreate(['name' => 'Member']);

        $categories = [
            'Teknologi',
            'Novel',
            'Sejarah',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        $tech = Category::where('slug', 'teknologi')->first();
        $novel = Category::where('slug', 'novel')->first();

        Book::firstOrCreate(
            ['isbn' => '978001'],
            [
                'category_id' => $tech->id,
                'title' => 'Laravel Dasar',
                'author' => 'Faiz',
                'publisher' => 'Open Library',
                'publish_year' => 2024,
                'stock' => 10,
            ]
        );

        Book::firstOrCreate(
            ['isbn' => '978002'],
            [
                'category_id' => $novel->id,
                'title' => 'Senja di Perpustakaan',
                'author' => 'Mfa',
                'publisher' => 'Open Library',
                'publish_year' => 2023,
                'stock' => 5,
            ]
        );

        // Create test users
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@library.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        $librarian = User::firstOrCreate(
            ['email' => 'librarian@library.com'],
            [
                'name' => 'Librarian',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $librarian->assignRole('Librarian');

        $member = User::firstOrCreate(
            ['email' => 'member@library.com'],
            [
                'name' => 'Test Member',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $member->assignRole('Member');
    }
}
