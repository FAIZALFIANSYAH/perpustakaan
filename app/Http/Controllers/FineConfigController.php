<?php

namespace App\Http\Controllers;

use App\Services\FineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FineConfigController extends Controller
{
    public function __construct(
        protected FineService $fineService
    ) {}

    public function index(): Response
    {
        $config = $this->fineService->getFineConfig();

        return Inertia::render('Admin/FineConfig/Index', [
            'config' => $config,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grace_period_days' => 'required|integer|min:0',
            'fine_per_day' => 'required|numeric|min:0',
            'max_billable_days' => 'required|integer|min:1',
            'max_fine_per_item' => 'required|numeric|min:0',
            'lost_book_fine' => 'required|numeric|min:0',
            'lost_book_payment_deadline' => 'required|integer|min:1',
            'max_fine_cap' => 'nullable|integer|min:0',
        ]);

        $this->fineService->updateFineConfig($validated);

        return redirect()
            ->route('admin.fine-config.index')
            ->with('success', 'Fine configuration updated successfully.');
    }
}
