<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __construct(
        protected MemberService $memberService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        return Inertia::render('Member/Catalog/Index', $this->memberService->getCatalogData($search, $categoryId));
    }

    public function show(Book $book): Response
    {
        $user = request()->user();

        return Inertia::render('Member/Catalog/Show', [
            'book' => $this->memberService->getCatalogBookDetail($book->id),
            'hasActiveBorrowing' => $this->memberService->hasActiveBorrowingForBook($user, $book->id),
            'hasPendingReservation' => $this->memberService->hasPendingReservationForBook($user, $book->id),
        ]);
    }

    public function borrow(Request $request, Book $book): RedirectResponse
    {
        $this->memberService->borrowBook($request->user(), $book);

        return redirect()
            ->route('member.dashboard')
            ->with('success', 'Book borrowed successfully. Please return it before the due date.');
    }

    public function reserve(Request $request, Book $book): RedirectResponse
    {
        $this->memberService->reserveBook($request->user(), $book);

        return redirect()
            ->route('member.catalog.show', $book)
            ->with('success', 'Book reserved successfully. You will be notified when it becomes available.');
    }
}
