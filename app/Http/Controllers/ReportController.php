<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): Response
    {
        $dateFrom = $request->string('date_from')->toString() ?: null;
        $dateTo = $request->string('date_to')->toString() ?: null;

        return Inertia::render('Admin/Reports/Index', $this->reportService->getReportData($dateFrom, $dateTo));
    }
}
