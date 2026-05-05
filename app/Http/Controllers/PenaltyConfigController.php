<?php

namespace App\Http\Controllers;

use App\Models\PenaltyConfig;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PenaltyConfigController extends Controller
{
    /**
     * Display the penalty configuration page
     */
    public function index()
    {
        $config = PenaltyConfig::getOrCreateDefault();
        
        return view('penalty-config.index', compact('config'));
    }

    /**
     * Update the penalty configuration
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penalty_enabled' => 'required|boolean',
            'grace_period_penalty_days' => 'required|integer|min:0|max:30',
            'penalty_multiplier' => 'required|numeric|min:1|max:10|regex:/^\d+(\.\d{1,2})?$/',
            'is_active' => 'required|boolean',
        ]);

        $config = PenaltyConfig::getOrCreateDefault();
        $config->update($validated);

        return redirect()
            ->route('penalty-config.index')
            ->with('success', 'Penalty configuration updated successfully.');
    }
}