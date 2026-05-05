<?php

namespace App\Services;

use App\Models\Book;
use App\Repositories\BookRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class BookService
{
    public function __construct(
        protected BookRepository $bookRepository
    ) {}

    public function getAllBooks(?string $search = null): Collection
    {
        return $this->bookRepository->getAll($search);
    }

    public function getCategories(): Collection
    {
        return $this->bookRepository->getCategories();
    }

    public function getBookFormCategories(): Collection
    {
        return $this->bookRepository->getCategories();
    }

    public function findBookById(int $id): ?Book
    {
        return Book::find($id);
    }

    public function createBook(array $data): Book
    {
        $data = $this->handleCoverUpload($data);
        
        return $this->bookRepository->create($data);
    }

    public function updateBook(int|Book $book, array $data): bool
    {
        if (is_int($book)) {
            $book = Book::findOrFail($book);
        }
        
        $data = $this->handleCoverUpload($data, $book->cover);
        
        return $this->bookRepository->update($book, $data);
    }

    public function deleteBook(int|Book $book): ?bool
    {
        if (is_int($book)) {
            $book = Book::findOrFail($book);
        }
        
        // Delete cover image if exists
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        
        return $this->bookRepository->delete($book);
    }

    protected function handleCoverUpload(array $data, ?string $existingCover = null): array
    {
        // If cover is an UploadedFile, it's a new upload
        if (isset($data['cover']) && $data['cover'] instanceof UploadedFile) {
            // Delete old cover if exists
            if ($existingCover) {
                Storage::disk('public')->delete($existingCover);
            }

            // Store new cover
            $path = $data['cover']->store('books/covers', 'public');
            $data['cover'] = $path;
        } 
        // If cover is explicitly set to null, empty string, or "REMOVE", remove it
        elseif (isset($data['cover']) && ($data['cover'] === null || $data['cover'] === '' || $data['cover'] === 'REMOVE')) {
            if ($existingCover) {
                Storage::disk('public')->delete($existingCover);
            }
            $data['cover'] = null;
        }
        // If cover is a string (existing path), remove it from data to preserve existing cover
        elseif (isset($data['cover']) && is_string($data['cover'])) {
            // Remove cover from data so it doesn't get updated/validated
            unset($data['cover']);
        }
        // If cover is not provided in the data, preserve existing cover
        elseif (!isset($data['cover'])) {
            // Don't modify the cover field - let the repository handle it
            if ($existingCover) {
                $data['cover'] = $existingCover;
            }
        }
        
        return $data;
    }
}
