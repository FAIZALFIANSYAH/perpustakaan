<?php

namespace App\Http\Controllers;

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

    public function booksStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'publish_year' => 'nullable|digits:4|integer|min:1000|max:' . date('Y'),
            'stock' => 'required|numeric|min:0',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $this->bookService->createBook($validated);

        return redirect()->route('librarian.books.index')->with('success', 'Book created successfully.');
    }

    public function booksEdit(int $book): Response
    {
        return Inertia::render('Librarian/Books/Edit', [
            'book' => $this->bookService->findBookById($book),
            'categories' => $this->bookService->getCategories(),
        ]);
    }

    public function booksUpdate(Request $request, int $book): \Illuminate\Http\RedirectResponse
    {
        // Get all request data
        $data = $request->all();
        
        // Remove cover field if it's a string (existing path) to avoid validation error
        if (isset($data['cover']) && is_string($data['cover'])) {
            unset($data['cover']);
            // Replace request data without the cover string
            $request->replace($data);
        }
        
        // Build validation rules dynamically - only include cover if it's actually a file
        $rules = [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:255',
            'publish_year' => 'nullable|digits:4|integer|min:1000|max:' . date('Y'),
            'stock' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ];
        
        // Only add cover validation if cover is present and it's a file
        if ($request->hasFile('cover')) {
            $rules['cover'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048';
        }
        
        $validated = $request->validate($rules);

        $this->bookService->updateBook($book, $validated);

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

    public function borrowingsStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->borrowingService->createBorrowing($request->validate([
            'member_id' => 'required|exists:users,id',
            'borrowed_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:borrowed_at',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]), $request->user()->id);

        return redirect()->route('librarian.borrowings.index')->with('success', 'Borrowing created successfully.');
    }

    public function borrowingsShow(int $borrowing): Response
    {
        return Inertia::render('Librarian/Borrowings/Show', [
            'borrowing' => $this->borrowingService->findBorrowingById($borrowing),
        ]);
    }

    public function borrowingsReturn(Request $request, int $borrowing): \Illuminate\Http\RedirectResponse
    {
        $borrowingModel = \App\Models\Borrowing::findOrFail($borrowing);
        
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:borrowing_items,id',
            'items.*.return_quantity' => 'required|integer|min:0',
        ]);

        $this->borrowingService->returnBorrowing($borrowingModel, $validated['items']);

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
