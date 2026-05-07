<?php

namespace App\Http\Controllers;

use App\Models\PenaltyConfig;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PenaltyConfigController extends Controller
{
    /**
     * Display the penalty configuration page
     */
    public function index(): Response
    {
        $config = PenaltyConfig::getOrCreateDefault();
        
        return Inertia::render('Admin/PenaltyConfig/Index', [
            'config' => $config,
        ]);
    }

    /**
     * Update the penalty configuration
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penalty_enabled' => 'sometimes|boolean',
            'grace_period_penalty_days' => 'required|integer|min:0|max:30',
            'penalty_multiplier' => 'required|numeric|min:1|max:10|regex:/^\d+(\.\d{1,2})?$/',
            'is_active' => 'sometimes|boolean',
        ]);

        $config = PenaltyConfig::getOrCreateDefault();
        $config->update([
            'penalty_enabled' => $request->boolean('penalty_enabled'),
            'grace_period_penalty_days' => (int) $validated['grace_period_penalty_days'],
            'penalty_multiplier' => $validated['penalty_multiplier'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('penalty-config.index')
            ->with('success', 'Penalty configuration updated successfully.');
    }
}
