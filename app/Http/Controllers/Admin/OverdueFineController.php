<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OverdueFineService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class OverdueFineController extends Controller
{
    public function __construct(
        protected OverdueFineService $overdueFineService
    ) {}

    /**
     * Show overdue fines processing page
     */
    public function index(): Response
    {
        $statistics = $this->overdueFineService->getOverdueFineStatistics();

        return Inertia::render('Admin/OverdueFines/Index', [
            'statistics' => $statistics,
        ]);
    }

    /**
     * Process all overdue fines
     */
    public function process(Request $request): \Illuminate\Http\RedirectResponse
    {
        $results = $this->overdueFineService->processOverdueFines();

        $message = "Processed {$results['processed']} borrowings. ";
        $message .= "Created {$results['created']} fines. ";
        $message .= "Skipped {$results['skipped']}.";

        if (!empty($results['errors'])) {
            $message .= " Errors: " . count($results['errors']);
        }

        return redirect()
            ->route('admin.overdue-fines.index')
            ->with('success', $message)
            ->with('results', $results);
    }

    /**
     * Get AJAX statistics for dashboard
     */
    public function statistics(): \Illuminate\Http\JsonResponse
    {
        $stats = $this->overdueFineService->getOverdueFineStatistics();

        return response()->json($stats);
    }
}
