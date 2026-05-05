<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class BookRepository
{
    public function getAll(?string $search = null): Collection
    {
        return Book::query()
            ->with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();
    }

    public function getCategories(): Collection
    {
        return Category::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): bool
    {
        return $book->update($data);
    }

    public function delete(Book $book): ?bool
    {
        return $book->delete();
    }
}
