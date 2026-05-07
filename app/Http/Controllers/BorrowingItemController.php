<?php

namespace App\Http\Controllers;

use App\Models\BorrowingItem;
use App\Models\Borrowing;
use App\Services\FineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BorrowingItemController extends Controller
{
    /**
     * Report a borrowed item as lost.
     */
    public function reportLost(Request $request, Borrowing $borrowing, BorrowingItem $item): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lost_quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
            ]);

            $fineService = app(FineService::class);
            $result = $fineService->handleLostBook(
                $borrowing,
                $item,
                (int) $validated['lost_quantity'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Book reported as lost successfully',
                'fine' => $result['fine'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report book as lost: ' . $e->getMessage()
            ], 500);
        }
    }
}
