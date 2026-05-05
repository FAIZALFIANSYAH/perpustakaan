<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(
        protected BookService $bookService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/Books/Index', [
            'books' => $this->bookService->getAllBooks($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Books/Create', [
            'categories' => $this->bookService->getBookFormCategories(),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $this->bookService->createBook($request->validated());

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book created successfully.');
    }

    public function edit(Book $book): Response
    {
        return Inertia::render('Admin/Books/Edit', [
            'book' => $book,
            'categories' => $this->bookService->getBookFormCategories(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->bookService->updateBook($book, $request->validated());

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book updated successfully.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->bookService->deleteBook($book);

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Book deleted successfully.');
    }
}
