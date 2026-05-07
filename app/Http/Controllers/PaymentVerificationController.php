<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\PaymentVerification;
use App\Models\PaymentReceipt;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentVerificationController extends Controller
{
    public function __construct(
        protected PaymentVerificationService $paymentVerificationService
    ) {}

    // Member: Request payment for fine
    public function create(Fine $fine): Response
    {
        $this->authorize('requestPayment', $fine);

        return Inertia::render('Member/PaymentVerification/Create', [
            'fine' => $fine->load(['borrowingItem.book', 'member']),
        ]);
    }

    // Member: Store payment request
    public function store(Request $request, Fine $fine): RedirectResponse
    {
        $this->authorize('requestPayment', $fine);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $fine->remaining_amount],
            'payment_method' => ['required', 'in:cash,bank_transfer,e_wallet,check'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $verification = $this->paymentVerificationService->initiatePaymentRequest(
                $fine,
                $request->user(),
                $validated
            );

            return redirect()
                ->route('member.fines.index')
                ->with('success', 'Payment request submitted successfully. Please wait for librarian verification.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['amount' => $e->getMessage()])
                ->withInput();
        }
    }

    // Librarian: Dashboard for payment verification
    public function dashboard(): Response
    {
        $this->authorize('verifyPayments');

        $statistics = $this->paymentVerificationService->getPaymentStatistics();
        $pendingPayments = $this->paymentVerificationService->getPendingPayments();

        return Inertia::render('Librarian/PaymentVerification/Dashboard', [
            'statistics' => $statistics,
            'pendingPayments' => $pendingPayments,
        ]);
    }

    // Librarian: Index of all payment verifications
    public function index(Request $request): Response
    {
        $this->authorize('verifyPayments');

        $search = $request->get('search');
        $status = $request->get('status');

        $payments = $this->paymentVerificationService->searchPayments($search, $status);

        return Inertia::render('Librarian/PaymentVerification/Index', [
            'payments' => $payments,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Librarian: Show payment verification details
    public function show(PaymentVerification $verification): Response
    {
        $this->authorize('verifyPayments');

        $verification->load([
            'fine.borrowingItem.book',
            'member',
            'requestedBy',
            'verifiedBy',
            'receipt',
            'auditLogs.performedBy'
        ]);

        return Inertia::render('Librarian/PaymentVerification/Show', [
            'verification' => $verification,
        ]);
    }

    // Librarian: Verify payment
    public function verify(Request $request, PaymentVerification $verification): RedirectResponse
    {
        $this->authorize('verifyPayments');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $receipt = $this->paymentVerificationService->verifyPayment(
                $verification,
                $request->user(),
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('librarian.payment-verification.show', $verification)
                ->with('success', 'Payment verified and receipt generated successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['verify' => $e->getMessage()]);
        }
    }

    // Librarian: Reject payment
    public function reject(Request $request, PaymentVerification $verification): RedirectResponse
    {
        $this->authorize('verifyPayments');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->paymentVerificationService->rejectPayment(
                $verification,
                $request->user(),
                $validated['reason']
            );

            return redirect()
                ->route('librarian.payment-verification.show', $verification)
                ->with('success', 'Payment request rejected.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withErrors(['reject' => $e->getMessage()]);
        }
    }

    // Member: Payment history
    public function history(Request $request): Response
    {
        $user = $request->user();
        
        $paymentHistory = $this->paymentVerificationService->getMemberPaymentHistory($user);

        return Inertia::render('Member/PaymentVerification/History', [
            'paymentHistory' => $paymentHistory,
        ]);
    }

    // Download receipt
    public function downloadReceipt(PaymentReceipt $receipt): StreamedResponse
    {
        $this->authorize('view', $receipt->paymentVerification);

        // In real implementation, generate PDF and return file
        // For now, return a simple text file
        $content = "Receipt #{$receipt->receipt_number}\n";
        $content .= "Member: {$receipt->getMemberName()}\n";
        $content .= "Amount: Rp " . number_format($receipt->getAmount(), 2) . "\n";
        $content .= "Payment Method: {$receipt->getPaymentMethod()}\n";
        $content .= "Reference: {$receipt->getReferenceNumber()}\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s');

        $filename = "receipt_{$receipt->receipt_number}.txt";
        
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    // Process expired payments (admin only)
    public function processExpired(): RedirectResponse
    {
        $this->authorize('manageSystem');

        $processedCount = $this->paymentVerificationService->processExpiredPayments();

        return redirect()
            ->route('librarian.payment-verification.dashboard')
            ->with('success', "Processed {$processedCount} expired payment requests.");
    }
}
