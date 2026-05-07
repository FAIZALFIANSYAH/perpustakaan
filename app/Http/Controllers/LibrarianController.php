<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnBorrowingRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\StoreBorrowingRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use App\Services\BorrowingService;
use App\Services\CategoryService;
use App\Services\LibrarianService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibrarianController extends Controller
{
    public function __construct(
        protected LibrarianService $librarianService,
        protected BookService $bookService,
        protected BorrowingService $borrowingService,
        protected CategoryService $categoryService
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('Librarian/Dashboard', $this->librarianService->getDashboardData());
    }

    public function overdue(): Response
    {
        return Inertia::render('Librarian/Overdue', [
            'overdues' => $this->librarianService->getOverdueData(),
        ]);
    }

    public function members(Request $request): Response
    {
        return Inertia::render('Librarian/Members/Index', [
            'members' => $this->librarianService->getMembers($request->query('search')),
            'filters' => [
                'search' => $request->query('search'),
            ],
        ]);
    }

    public function reports(): Response
    {
        return Inertia::render('Librarian/Reports/Index', [
            'reports' => $this->librarianService->getLibrarianReports(),
        ]);
    }

    // Books Management for Librarian
    public function booksIndex(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Librarian/Books/Index', [
            'books' => $this->bookService->getAllBooks($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function booksCreate(): Response
    {
        return Inertia::render('Librarian/Books/Create', [
            'categories' => $this->bookService->getCategories(),
        ]);
    }

    public function booksStore(StoreBookRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->bookService->createBook($request->validated());

        return redirect()->route('librarian.books.index')->with('success', 'Book created successfully.');
    }

    public function booksEdit(int $book): Response
    {
        return Inertia::render('Librarian/Books/Edit', [
            'book' => $this->bookService->findBookById($book),
            'categories' => $this->bookService->getCategories(),
        ]);
    }

    public function booksUpdate(UpdateBookRequest $request, Book $book): \Illuminate\Http\RedirectResponse
    {
        $this->bookService->updateBook($book, $request->validated());

        return redirect()->route('librarian.books.index')->with('success', 'Book updated successfully.');
    }

    public function booksDestroy(int $book): \Illuminate\Http\RedirectResponse
    {
        $this->bookService->deleteBook($book);

        return redirect()->route('librarian.books.index')->with('success', 'Book deleted successfully.');
    }

    // Borrowings Management for Librarian
    public function borrowingsIndex(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Librarian/Borrowings/Index', [
            'borrowings' => $this->borrowingService->getAllBorrowings($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function borrowingsCreate(): Response
    {
        return Inertia::render('Librarian/Borrowings/Create', $this->borrowingService->getBorrowingFormData());
    }

    public function borrowingsStore(StoreBorrowingRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->borrowingService->createBorrowing($request->validated(), $request->user()->id);

        return redirect()->route('librarian.borrowings.index')->with('success', 'Borrowing created successfully.');
    }

    public function borrowingsShow(int $borrowing): Response
    {
        return Inertia::render('Librarian/Borrowings/Show', [
            'borrowing' => $this->borrowingService->findBorrowingById($borrowing),
        ]);
    }

    public function borrowingsReturn(ReturnBorrowingRequest $request, int $borrowing): \Illuminate\Http\RedirectResponse
    {
        $borrowingModel = \App\Models\Borrowing::findOrFail($borrowing);

        $this->borrowingService->returnBorrowing($borrowingModel, $request->validated()['items']);

        return redirect()->route('librarian.borrowings.show', $borrowing)->with('success', 'Borrowing returned successfully.');
    }

    // Categories Management for Librarian
    public function categoriesIndex(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Librarian/Categories/Index', [
            'categories' => $this->categoryService->getAllCategories($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function categoriesCreate(): Response
    {
        return Inertia::render('Librarian/Categories/Create');
    }

    public function categoriesStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $this->categoryService->createCategory($validated);

        return redirect()->route('librarian.categories.index')->with('success', 'Category created successfully.');
    }

    public function categoriesEdit(int $category): Response
    {
        return Inertia::render('Librarian/Categories/Edit', [
            'category' => $this->categoryService->findCategoryById($category),
        ]);
    }

    public function categoriesUpdate(Request $request, int $category): \Illuminate\Http\RedirectResponse
    {
        $categoryModel = \App\Models\Category::findOrFail($category);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $categoryModel->id,
        ]);

        $this->categoryService->updateCategory($categoryModel, $validated);

        return redirect()->route('librarian.categories.index')->with('success', 'Category updated successfully.');
    }

    public function categoriesDestroy(int $category): \Illuminate\Http\RedirectResponse
    {
        $categoryModel = \App\Models\Category::findOrFail($category);

        $this->categoryService->deleteCategory($categoryModel);

        return redirect()->route('librarian.categories.index')->with('success', 'Category deleted successfully.');
    }
}
