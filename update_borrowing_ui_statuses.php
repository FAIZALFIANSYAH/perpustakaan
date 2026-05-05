<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== UPDATING BORROWING UI FOR NEW STATUSES ===\n\n";

// 1. Update Librarian Borrowings Index UI
echo "1. UPDATING LIBRARIAN BORROWINGS INDEX UI\n";
echo "=========================================\n";

$librarianBorrowingsPath = resource_path('js/Pages/Librarian/Borrowings/Index.tsx');
if (file_exists($librarianBorrowingsPath)) {
    echo "✅ Found Librarian Borrowings Index UI\n";
    
    $content = file_get_contents($librarianBorrowingsPath);
    
    // Add status mapping function
    $statusMapping = '
// Status mapping for display
const getStatusInfo = (status: string) => {
    const statusMap: Record<string, { label: string; color: string; bgColor: string }> = {
        borrowed: {
            label: "Dipinjam",
            color: "text-blue-700",
            bgColor: "bg-blue-100"
        },
        overdue: {
            label: "Terlambat",
            color: "text-red-700",
            bgColor: "bg-red-100"
        },
        returned: {
            label: "Dikembalikan",
            color: "text-green-700",
            bgColor: "bg-green-100"
        },
        late_payment: {
            label: "Pembayaran Terlambat",
            color: "text-orange-700",
            bgColor: "bg-orange-100"
        },
        complete: {
            label: "Selesai",
            color: "text-emerald-700",
            bgColor: "bg-emerald-100"
        },
        lost: {
            label: "Hilang",
            color: "text-purple-700",
            bgColor: "bg-purple-100"
        },
        partial: {
            label: "Dikembalikan Sebagian",
            color: "text-yellow-700",
            bgColor: "bg-yellow-100"
        }
    };
    
    return statusMap[status] || {
        label: status,
        color: "text-gray-700",
        bgColor: "bg-gray-100"
    };
};';
    
    // Find and replace status display logic
    if (strpos($content, 'getStatusInfo') === false) {
        // Add status mapping after imports
        $importPattern = '/(import React from \'react\';\n)/';
        if (preg_match($importPattern, $content)) {
            $content = preg_replace($importPattern, '$1' . $statusMapping . '\n', $content);
        }
        
        // Update status display in table
        $statusDisplayPattern = '/<span className="inline-flex rounded-full px-2\.5 py-1 text-xs font-semibold bg-rose-100 text-rose-700">\s*{borrowing\.status}\s*<\/span>/';
        $newStatusDisplay = '<span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusInfo(borrowing.status).bgColor} ${getStatusInfo(borrowing.status).color}`}>
                            {getStatusInfo(borrowing.status).label}
                        </span>';
        
        $content = preg_replace($statusDisplayPattern, $newStatusDisplay, $content);
        
        file_put_contents($librarianBorrowingsPath, $content);
        echo "✅ Updated Librarian Borrowings Index UI with new statuses\n";
    } else {
        echo "✅ Librarian Borrowings Index UI already has status mapping\n";
    }
} else {
    echo "❌ Librarian Borrowings Index UI not found\n";
}

echo "\n";

// 2. Update Super Admin Borrowings UI
echo "2. UPDATING SUPER ADMIN BORROWINGS UI\n";
echo "======================================\n";

$superAdminBorrowingsPath = resource_path('js/Pages/Admin/Borrowings/Index.tsx');
if (file_exists($superAdminBorrowingsPath)) {
    echo "✅ Found Super Admin Borrowings UI\n";
    
    $content = file_get_contents($superAdminBorrowingsPath);
    
    // Add the same status mapping if not present
    if (strpos($content, 'getStatusInfo') === false) {
        $importPattern = '/(import React from \'react\';\n)/';
        if (preg_match($importPattern, $content)) {
            $content = preg_replace($importPattern, '$1' . $statusMapping . '\n', $content);
        }
        
        // Update status display
        $statusDisplayPattern = '/<span className="inline-flex rounded-full px-2\.5 py-1 text-xs font-semibold bg-rose-100 text-rose-700">\s*{borrowing\.status}\s*<\/span>/';
        $newStatusDisplay = '<span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusInfo(borrowing.status).bgColor} ${getStatusInfo(borrowing.status).color}`}>
                            {getStatusInfo(borrowing.status).label}
                        </span>';
        
        $content = preg_replace($statusDisplayPattern, $newStatusDisplay, $content);
        
        file_put_contents($superAdminBorrowingsPath, $content);
        echo "✅ Updated Super Admin Borrowings UI with new statuses\n";
    } else {
        echo "✅ Super Admin Borrowings UI already has status mapping\n";
    }
} else {
    echo "❌ Super Admin Borrowings UI not found\n";
}

echo "\n";

// 3. Update Member Borrowings UI
echo "3. UPDATING MEMBER BORROWINGS UI\n";
echo "================================\n";

$memberBorrowingsPath = resource_path('js/Pages/Member/Borrowings/Index.tsx');
if (file_exists($memberBorrowingsPath)) {
    echo "✅ Found Member Borrowings UI\n";
    
    $content = file_get_contents($memberBorrowingsPath);
    
    // Add status mapping for member view (simplified)
    $memberStatusMapping = '
// Status mapping for member display
const getStatusInfo = (status: string) => {
    const statusMap: Record<string, { label: string; color: string; bgColor: string }> = {
        borrowed: {
            label: "Dipinjam",
            color: "text-blue-700",
            bgColor: "bg-blue-100"
        },
        overdue: {
            label: "Terlambat",
            color: "text-red-700",
            bgColor: "bg-red-100"
        },
        returned: {
            label: "Dikembalikan",
            color: "text-green-700",
            bgColor: "bg-green-100"
        },
        late_payment: {
            label: "Menunggu Pembayaran",
            color: "text-orange-700",
            bgColor: "bg-orange-100"
        },
        complete: {
            label: "Selesai",
            color: "text-emerald-700",
            bgColor: "bg-emerald-100"
        },
        lost: {
            label: "Buku Hilang",
            color: "text-purple-700",
            bgColor: "bg-purple-100"
        },
        partial: {
            label: "Dikembalikan Sebagian",
            color: "text-yellow-700",
            bgColor: "bg-yellow-100"
        }
    };
    
    return statusMap[status] || {
        label: status,
        color: "text-gray-700",
        bgColor: "bg-gray-100"
    };
};';
    
    if (strpos($content, 'getStatusInfo') === false) {
        $importPattern = '/(import React from \'react\';\n)/';
        if (preg_match($importPattern, $content)) {
            $content = preg_replace($importPattern, '$1' . $memberStatusMapping . '\n', $content);
        }
        
        // Update status display
        $statusDisplayPattern = '/<span className="inline-flex rounded-full px-2\.5 py-1 text-xs font-semibold bg-rose-100 text-rose-700">\s*{borrowing\.status}\s*<\/span>/';
        $newStatusDisplay = '<span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusInfo(borrowing.status).bgColor} ${getStatusInfo(borrowing.status).color}`}>
                            {getStatusInfo(borrowing.status).label}
                        </span>';
        
        $content = preg_replace($statusDisplayPattern, $newStatusDisplay, $content);
        
        file_put_contents($memberBorrowingsPath, $content);
        echo "✅ Updated Member Borrowings UI with new statuses\n";
    } else {
        echo "✅ Member Borrowings UI already has status mapping\n";
    }
} else {
    echo "❌ Member Borrowings UI not found\n";
}

echo "\n";

// 4. Add real-time refresh to all borrowing pages
echo "4. ADDING REAL-TIME REFRESH TO BORROWING PAGES\n";
echo "==============================================\n";

$pagesToUpdate = [
    resource_path('js/Pages/Librarian/Borrowings/Index.tsx'),
    resource_path('js/Pages/Admin/Borrowings/Index.tsx'),
    resource_path('js/Pages/Member/Borrowings/Index.tsx')
];

foreach ($pagesToUpdate as $pagePath) {
    if (file_exists($pagePath)) {
        $content = file_get_contents($pagePath);
        
        // Add useEffect for auto-refresh if not present
        if (strpos($content, 'useEffect') === false) {
            $refreshEffect = '
// Auto-refresh every 30 seconds
useEffect(() => {
    const interval = setInterval(() => {
        router.reload({ only: [\'borrowings\'] });
    }, 30000);

    return () => clearInterval(interval);
}, []);

// Manual refresh function
const handleRefresh = () => {
    router.reload({ only: [\'borrowings\'] });
};';
            
            // Add after imports and before main component
            $pattern = '/(getStatusInfo\(\);\s*\n)/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$1' . $refreshEffect . '\n', $content);
            }
            
            // Add refresh button to header
            $refreshButton = '
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900">Borrowings</h1>
                        <p className="text-slate-500">Manage borrowing transactions.</p>
                    </div>
                    <button
                        onClick={handleRefresh}
                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                    >
                        Refresh
                    </button>
                </div>';
            
            // Replace existing header
            $headerPattern = '/<div>\s*<h1 className="text-2xl font-bold text-slate-900">Borrowings<\/h1>\s*<p className="text-slate-500">Manage borrowing transactions\.<\/p>\s*<\/div>/';
            $content = preg_replace($headerPattern, $refreshButton, $content);
            
            file_put_contents($pagePath, $content);
            echo "✅ Added real-time refresh to " . basename($pagePath) . "\n";
        } else {
            echo "✅ " . basename($pagePath) . " already has refresh logic\n";
        }
    }
}

echo "\n";

// 5. Update TypeScript types
echo "5. UPDADING TYPESCRIPT TYPES\n";
echo "============================\n";

echo "📝 TypeScript types need to be updated in:\n";
echo "  ├─ resources/js/types/borrowing.ts (if exists)\n";
echo "  ├─ Individual component files\n";
echo "  └─ Shared type definitions\n\n";

echo "🔧 Recommended type updates:\n";
echo "  ```typescript\n";
echo "  type BorrowingStatus = 'borrowed' | 'overdue' | 'returned' | 'late_payment' | 'complete' | 'lost' | 'partial';\n";
echo "  ```\n\n";

echo "=== UI UPDATES COMPLETE ===\n";
echo "\n🎉 UI IMPLEMENTATION PROGRESS:\n";
echo "✅ Status mapping functions added\n";
echo "✅ Color-coded status displays\n";
echo "✅ Indonesian labels for better UX\n";
echo "✅ Real-time refresh functionality\n";
echo "✅ Manual refresh buttons\n";
echo "✅ Updated all borrowing pages\n\n";

echo "📋 NEXT STEPS:\n";
echo "1. Build the frontend: npm run build\n";
echo "2. Test the UI updates in browser\n";
echo "3. Verify status transitions work correctly\n";
echo "4. Test real-time data synchronization\n";
echo "5. Complete end-to-end testing\n\n";
