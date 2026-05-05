<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnBorrowingRequest;
use App\Http\Requests\StoreBorrowingRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrowingController extends Controller
{
    public function __construct(
        protected BorrowingService $borrowingService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/Borrowings/Index', [
            'borrowings' => $this->borrowingService->getAllBorrowings($search),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Borrowings/Create', $this->borrowingService->getBorrowingFormData());
    }

    public function show(Borrowing $borrowing): Response
    {
        return Inertia::render('Admin/Borrowings/Show', [
            'borrowing' => $this->borrowingService->findBorrowingById($borrowing->id),
        ]);
    }

    public function store(StoreBorrowingRequest $request): RedirectResponse
    {
        $this->borrowingService->createBorrowing($request->validated(), $request->user()->id);

        return redirect()
            ->route('admin.borrowings.index')
            ->with('success', 'Borrowing created successfully.');
    }

    public function return(ReturnBorrowingRequest $request, Borrowing $borrowing): RedirectResponse
    {
        $this->borrowingService->returnBorrowing($borrowing, $request->validated()['items']);

        return redirect()
            ->route('admin.borrowings.show', $borrowing)
            ->with('success', 'Borrowing returned successfully.');
    }
}
