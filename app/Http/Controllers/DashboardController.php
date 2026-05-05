<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect user to their role-specific dashboard.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Super Admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Librarian')) {
            return redirect()->route('librarian.dashboard');
        }

        return redirect()->route('member.dashboard');
    }
}
