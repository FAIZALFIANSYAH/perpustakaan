<?php

namespace App\Http\Controllers;

use App\Models\BorrowingItem;
use App\Models\Borrowing;
use App\Models\Fine;
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
            // Update the item status to lost
            $item->status = 'lost';
            $item->save();
            
            // Create a lost book fine
            $fineService = app(FineService::class);
            $fineConfig = $fineService->getActiveFineConfig();
            
            if ($fineConfig) {
                $fine = $fineService->createLostBookFine($borrowing, $item, $fineConfig->lost_book_fine);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Book reported as lost successfully',
                    'fine' => $fine
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Book reported as lost successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report book as lost: ' . $e->getMessage()
            ], 500);
        }
    }
}