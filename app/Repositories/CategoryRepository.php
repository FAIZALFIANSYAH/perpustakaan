<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function getAll(?string $search = null): Collection
    {
        return Category::query()
            ->withCount('books')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::query()
            ->withCount('books')
            ->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): ?bool
    {
        return $category->delete();
    }
}
