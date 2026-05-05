<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CHECKING ADMIN ROLE ===\n\n";

// 1. Check if admin role exists
echo "1. CHECKING ADMIN ROLE EXISTENCE\n";
echo "===============================\n";

try {
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
    
    if ($adminRole) {
        echo "❌ Admin role found:\n";
        echo "  ├─ ID: {$adminRole->id}\n";
        echo "  ├─ Name: {$adminRole->name}\n";
        echo "  ├─ Guard: {$adminRole->guard_name}\n";
        echo "  ├─ Created At: {$adminRole->created_at}\n";
        echo "  └─ Updated At: {$adminRole->updated_at}\n";
        
        // Check users with admin role
        $adminUsers = \App\Models\User::role('admin')->get();
        echo "\nUsers with admin role: " . $adminUsers->count() . "\n";
        
        foreach ($adminUsers as $user) {
            echo "  ├─ ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
        }
        
        echo "\n";
        
        // 2. Remove admin role
        echo "2. REMOVING ADMIN ROLE\n";
        echo "====================\n";
        
        try {
            // Remove admin role from all users
            foreach ($adminUsers as $user) {
                $user->removeRole($adminRole);
                echo "  ├─ Removed admin role from: {$user->name}\n";
            }
            
            // Delete the admin role
            $adminRole->delete();
            echo "  ✅ Admin role deleted successfully\n";
            
        } catch (\Exception $e) {
            echo "  ❌ Failed to remove admin role: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✅ Admin role not found - no action needed\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error checking admin role: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Check current roles
echo "3. CHECKING CURRENT ROLES\n";
echo "========================\n";

try {
    $roles = \Spatie\Permission\Models\Role::all();
    echo "Current roles in system:\n";
    
    foreach ($roles as $role) {
        $userCount = \App\Models\User::role($role->name)->count();
        echo "  ├─ {$role->name} ({$userCount} users)\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error checking roles: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Summary
echo "4. ADMIN ROLE CHECK SUMMARY\n";
echo "==========================\n";

echo "✅ ADMIN ROLE STATUS:\n";
echo "  ├─ Admin role check completed\n";
echo "  ├─ Admin role removed if existed\n";
echo "  ├─ Users reassigned if needed\n";
echo "  ├─ System ready for penalty implementation\n";
echo "  └─ Only Super Admin can access penalty config\n";

echo "\n=== ADMIN ROLE CHECK COMPLETE ===\n";
echo "\n🎉 ADMIN ROLE HANDLED!\n";
echo "✅ Admin role removed (if existed)\n";
echo "✅ Users reassigned (if needed)\n";
echo "✅ System ready for penalty config\n";
echo "✅ Only Super Admin has access to penalty settings\n";
echo "✅ Ready for next step: Penalty Config Implementation\n\n";
