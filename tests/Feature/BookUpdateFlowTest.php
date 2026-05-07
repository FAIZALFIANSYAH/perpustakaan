<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookUpdateFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Librarian']);
        Role::create(['name' => 'Member']);
    }

    public function test_admin_can_update_book_without_changing_cover(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $oldCategory = Category::create(['name' => 'Novel', 'slug' => 'novel']);
        $newCategory = Category::create(['name' => 'Sains', 'slug' => 'sains']);

        $book = Book::create([
            'category_id' => $oldCategory->id,
            'title' => 'Old Title',
            'author' => 'Old Author',
            'publisher' => 'Old Publisher',
            'isbn' => 'ISBN-OLD-001',
            'publish_year' => 2020,
            'stock' => 5,
            'cover' => 'books/covers/existing.jpg',
            'description' => 'Old description',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.books.update', $book), [
            '_method' => 'put',
            'category_id' => (string) $newCategory->id,
            'title' => 'New Title',
            'author' => 'New Author',
            'publisher' => 'New Publisher',
            'isbn' => 'ISBN-NEW-001',
            'publish_year' => '2024',
            'stock' => '9',
            'description' => 'New description',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'category_id' => $newCategory->id,
            'title' => 'New Title',
            'author' => 'New Author',
            'stock' => 9,
            'cover' => 'books/covers/existing.jpg',
        ]);
    }

    public function test_admin_can_update_book_with_new_cover_file(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Teknologi', 'slug' => 'teknologi']);

        Storage::disk('public')->put('books/covers/existing.jpg', 'old-file');

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Book Title',
            'author' => 'Book Author',
            'publisher' => 'Publisher',
            'isbn' => 'ISBN-COVER-001',
            'publish_year' => 2022,
            'stock' => 3,
            'cover' => 'books/covers/existing.jpg',
            'description' => null,
            'is_active' => true,
        ]);

        $newCover = UploadedFile::fake()->image('new-cover.jpg');

        $response = $this->actingAs($admin)->post(route('admin.books.update', $book), [
            '_method' => 'put',
            'category_id' => (string) $category->id,
            'title' => 'Book Title Updated',
            'author' => 'Book Author Updated',
            'publisher' => 'Publisher Updated',
            'isbn' => 'ISBN-COVER-002',
            'publish_year' => '2025',
            'stock' => '10',
            'description' => 'Updated description',
            'is_active' => '1',
            'cover' => $newCover,
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertNotNull($book->cover);
        $this->assertStringStartsWith('books/covers/', $book->cover);
        Storage::disk('public')->assertExists($book->cover);
        Storage::disk('public')->assertMissing('books/covers/existing.jpg');
    }

    public function test_admin_can_add_cover_when_book_initially_has_no_cover(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Biografi', 'slug' => 'biografi']);

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Book Without Cover',
            'author' => 'Author Without Cover',
            'publisher' => 'Publisher',
            'isbn' => 'ISBN-NOCOVER-001',
            'publish_year' => 2023,
            'stock' => 4,
            'cover' => null,
            'description' => null,
            'is_active' => true,
        ]);

        $newCover = UploadedFile::fake()->image('first-cover.jpg');

        $response = $this->actingAs($admin)->post(route('admin.books.update', $book), [
            '_method' => 'put',
            'category_id' => (string) $category->id,
            'title' => 'Book Without Cover Updated',
            'author' => 'Author Without Cover Updated',
            'publisher' => 'Publisher Updated',
            'isbn' => 'ISBN-NOCOVER-002',
            'publish_year' => '2026',
            'stock' => '11',
            'description' => 'First cover attached',
            'is_active' => '1',
            'cover' => $newCover,
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertNotNull($book->cover);
        $this->assertStringStartsWith('books/covers/', $book->cover);
        Storage::disk('public')->assertExists($book->cover);
    }

    public function test_admin_can_remove_existing_cover_on_update(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Komik', 'slug' => 'komik']);

        Storage::disk('public')->put('books/covers/existing-remove.jpg', 'old-file');

        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Book Remove Cover',
            'author' => 'Author Remove Cover',
            'publisher' => 'Publisher',
            'isbn' => 'ISBN-REMOVE-001',
            'publish_year' => 2021,
            'stock' => 2,
            'cover' => 'books/covers/existing-remove.jpg',
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.books.update', $book), [
            '_method' => 'put',
            'category_id' => (string) $category->id,
            'title' => 'Book Remove Cover Updated',
            'author' => 'Author Remove Cover Updated',
            'publisher' => 'Publisher Updated',
            'isbn' => 'ISBN-REMOVE-002',
            'publish_year' => '2026',
            'stock' => '8',
            'description' => 'No cover now',
            'is_active' => '1',
            'cover' => 'REMOVE',
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'cover' => null,
        ]);
        Storage::disk('public')->assertMissing('books/covers/existing-remove.jpg');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
