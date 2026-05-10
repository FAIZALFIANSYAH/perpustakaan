<?php

namespace App\Services;

use App\Repositories\LibrarianRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LibrarianService
{
    public function __construct(
        protected LibrarianRepository $librarianRepository
    ) {}

    public function getDashboardData(): array
    {
        return [
            'stats' => $this->librarianRepository->getDashboardStats(),
            'recentTransactions' => $this->librarianRepository->getRecentTransactions(8),
            'dueToday' => $this->librarianRepository->getDueToday(),
        ];
    }

    public function getOverdueData(): Collection
    {
        return $this->librarianRepository->getOverdue();
    }

    public function getMembers(?string $search = null): LengthAwarePaginator
    {
        return $this->librarianRepository->searchMembers($search);
    }

    public function getLibrarianReports(): array
    {
        return $this->librarianRepository->getLibrarianReports();
    }
}
