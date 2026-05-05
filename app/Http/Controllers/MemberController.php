<?php

namespace App\Http\Controllers;

use App\Services\MemberService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        protected MemberService $memberService
    ) {}

    public function dashboard(Request $request): Response
    {
        return Inertia::render('Member/Dashboard', $this->memberService->getDashboardData($request->user()));
    }

    public function borrowingHistory(Request $request): Response
    {
        return Inertia::render('Member/Borrowings/History', [
            'borrowings' => $this->memberService->getBorrowingHistory($request->user()),
        ]);
    }

    public function reservations(Request $request): Response
    {
        return Inertia::render('Member/Reservations/Index', [
            'reservations' => $this->memberService->getReservations($request->user()),
        ]);
    }
}
