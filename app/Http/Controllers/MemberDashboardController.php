<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Borrowing;
use App\Models\Fine;
use Inertia\Response;

class MemberDashboardController extends Controller
{
    /**
     * Display the member dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $userId = $user->id;
        
        $stats = [
            'my_borrowings' => Borrowing::where('member_id', $userId)->count(),
            'active_borrowings' => Borrowing::where('member_id', $userId)
                ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
                ->count(),
            'overdue_borrowings' => Borrowing::where('member_id', $userId)
                ->where('status', Borrowing::STATUS_OVERDUE)
                ->count(),
            'my_fines' => Fine::where('member_id', $userId)->count(),
            'unpaid_fines' => Fine::where('member_id', $userId)
                ->where('status', Fine::STATUS_UNPAID)
                ->sum('amount'),
            'recent_borrowings' => Borrowing::with(['items.book'])
                ->where('member_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'my_fines_list' => Fine::with(['borrowingItem.book'])
                ->where('member_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
        
        return inertia('Member/Dashboard', [
            'stats' => $stats,
            'user' => $user
        ]);
    }
}
