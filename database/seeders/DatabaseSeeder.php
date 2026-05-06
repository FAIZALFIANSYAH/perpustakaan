<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Teknologi', 'Novel', 'Sejarah'];

        foreach ($categories as $name) {
            Category::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        $tech = Category::where('slug', 'teknologi')->first();
        $novel = Category::where('slug', 'novel')->first();

        if ($tech) {
            Book::firstOrCreate(
                ['isbn' => '978001'],
                [
                    'category_id' => $tech->id,
                    'title' => 'Laravel Dasar',
                    'author' => 'Faiz',
                    'publisher' => 'Open Library',
                    'publish_year' => 2024,
                    'stock' => 10,
                    'is_active' => true,
                ],
            );
        }

        if ($novel) {
            Book::firstOrCreate(
                ['isbn' => '978002'],
                [
                    'category_id' => $novel->id,
                    'title' => 'Senja di Perpustakaan',
                    'author' => 'Mfa',
                    'publisher' => 'Open Library',
                    'publish_year' => 2023,
                    'stock' => 5,
                    'is_active' => true,
                ],
            );
        }

        $this->call(FoundationUserSeeder::class);
    }
}
