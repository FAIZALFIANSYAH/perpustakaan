<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Services\FineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FineController extends Controller
{
    public function __construct(
        protected FineService $fineService
    ) {}

    // Admin and Librarian: View all fines
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        return Inertia::render('Admin/Fines/Index', [
            'fines' => $this->fineService->getAllFines($search, $status),
            'statistics' => $this->fineService->getFineStatistics(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Admin and Librarian: Process fine payment
    public function processPayment(Request $request, Fine $fine): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string',
        ]);

        $this->fineService->processFinePayment((int) $fine->id, [
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
            'processed_by' => $request->user()->id,
            'paid_by' => $fine->member_id,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Fine payment processed successfully.');
    }

    // Admin and Librarian: Report lost book
    public function reportLostBook(Request $request, int $borrowing, int $borrowingItem): RedirectResponse
    {
        $validated = $request->validate([
            'lost_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $borrowingModel = \App\Models\Borrowing::with('items')->findOrFail($borrowing);
        $borrowingItemModel = \App\Models\BorrowingItem::findOrFail($borrowingItem);

        $this->fineService->handleLostBook(
            $borrowingModel,
            $borrowingItemModel,
            $validated['lost_quantity'],
            $validated['notes'] ?? null
        );

        // Dynamic redirect based on user role
        $user = $request->user();
        if ($user->hasRole('Super Admin')) {
            $route = 'admin.borrowings.show';
        } else {
            $route = 'librarian.borrowings.show';
        }

        return redirect()
            ->route($route, $borrowing)
            ->with('success', 'Lost book reported successfully. Fine has been created.');
    }

    // Member: View own fines
    public function memberIndex(Request $request): Response
    {
        $memberId = $request->user()->id;

        return Inertia::render('Member/Fines/Index', [
            'fines' => $this->fineService->getMemberFines($memberId),
            'statistics' => $this->fineService->getMemberFineStatistics($memberId),
            'totalUnpaid' => $this->fineService->getTotalUnpaidFines($memberId),
        ]);
    }

    // Member: Make payment for fine
    public function memberProcessPayment(Request $request, Fine $fine): RedirectResponse
    {
        // Ensure member owns this fine
        if ($fine->member_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string',
        ]);

        // For member self-service, they need to contact librarian/admin to actually pay
        // This is just a placeholder - in real scenario, payment would be verified by staff
        $this->fineService->processFinePayment((int) $fine->id, [
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
            'processed_by' => $request->user()->id,
            'paid_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('member.fines.index')
            ->with('success', 'Fine payment processed successfully.');
    }
}
