<?php

namespace App\Services;

use App\Repositories\ReportRepository;

class ReportService
{
    public function __construct(
        protected ReportRepository $reportRepository
    ) {}

    public function getReportData(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'summary' => $this->reportRepository->getSummary($dateFrom, $dateTo),
            'recentBorrowings' => $this->reportRepository->getRecentBorrowings($dateFrom, $dateTo),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ];
    }
}
