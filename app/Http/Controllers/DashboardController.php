<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Borrowing;
use App\Models\Book;
use App\Models\User;
use App\Models\Fine;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get statistics based on user role
        if ($user->hasRole('Super Admin') || $user->hasRole('Librarian')) {
            $stats = $this->getAdminStats();
        } else {
            $stats = $this->getMemberStats();
        }
        
        return inertia('Dashboard', [
            'stats' => $stats,
            'user' => $user
        ]);
    }
    
    /**
     * Get admin statistics.
     */
    private function getAdminStats(): array
    {
        return [
            'total_books' => Book::count(),
            'total_borrowings' => Borrowing::count(),
            'active_borrowings' => Borrowing::whereIn('status', ['borrowed', 'overdue'])->count(),
            'overdue_borrowings' => Borrowing::where('status', 'overdue')->count(),
            'total_fines' => Fine::count(),
            'unpaid_fines' => Fine::where('status', 'unpaid')->sum('amount'),
            'total_members' => User::whereHas('roles', function($query) {
                $query->where('name', 'Member');
            })->count(),
            'recent_borrowings' => Borrowing::with(['member', 'items.book'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
    }
    
    /**
     * Get member statistics.
     */
    private function getMemberStats(): array
    {
        $userId = Auth::id();
        
        return [
            'my_borrowings' => Borrowing::where('member_id', $userId)->count(),
            'active_borrowings' => Borrowing::where('member_id', $userId)
                ->whereIn('status', ['borrowed', 'overdue'])
                ->count(),
            'overdue_borrowings' => Borrowing::where('member_id', $userId)
                ->where('status', 'overdue')
                ->count(),
            'my_fines' => Fine::where('member_id', $userId)->count(),
            'unpaid_fines' => Fine::where('member_id', $userId)
                ->where('status', 'unpaid')
                ->sum('amount'),
            'recent_borrowings' => Borrowing::with(['items.book'])
                ->where('member_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];
    }
}