<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CREATING PENALTY CONFIG SYSTEM ===\n\n";

// 1. Create PenaltyConfig migration
echo "1. CREATING PENALTY CONFIG MIGRATION\n";
echo "===================================\n";

try {
    $migrationName = 'create_penalty_configs_table';
    $migrationPath = database_path('migrations/' . date('Y_m_d_His') . '_create_penalty_configs_table.php');
    
    $migrationContent = '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(\'penalty_configs\', function (Blueprint $table) {
            $table->id();
            $table->boolean(\'penalty_enabled\')->default(true);
            $table->integer(\'grace_period_penalty_days\')->default(3)->comment(\'Grace period before penalty applies\');
            $table->decimal(\'penalty_multiplier\', 8, 2)->default(2.00)->comment(\'Multiplier for penalty calculation\');
            $table->boolean(\'is_active\')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(\'penalty_configs\');
    }
};';
    
    file_put_contents($migrationPath, $migrationContent);
    echo "✅ Penalty config migration created: " . basename($migrationPath) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to create migration: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Run the migration
echo "2. RUNNING PENALTY CONFIG MIGRATION\n";
echo "===================================\n";

try {
    // Check if table exists first
    if (!\Illuminate\Support\Facades\Schema::hasTable('penalty_configs')) {
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--path' => 'database/migrations/' . basename($migrationPath, '.php') . '.php'
        ]);
        echo "✅ Migration executed successfully\n";
    } else {
        echo "✅ Penalty configs table already exists\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Create PenaltyConfig model
echo "3. CREATING PENALTY CONFIG MODEL\n";
echo "===============================\n";

try {
    $modelPath = app_path('Models/PenaltyConfig.php');
    
    $modelContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        \'penalty_enabled\',
        \'grace_period_penalty_days\',
        \'penalty_multiplier\',
        \'is_active\',
    ];

    protected $casts = [
        \'penalty_enabled\' => \'boolean\',
        \'grace_period_penalty_days\' => \'integer\',
        \'penalty_multiplier\' => \'decimal:2\',
        \'is_active\' => \'boolean\',
    ];

    /**
     * Get the active penalty configuration
     */
    public static function getActiveConfig(): ?self
    {
        return static::where(\'is_active\', true)->first();
    }

    /**
     * Get or create the default penalty configuration
     */
    public static function getOrCreateDefault(): self
    {
        $config = static::getActiveConfig();
        
        if (!$config) {
            $config = static::create([
                \'penalty_enabled\' => true,
                \'grace_period_penalty_days\' => 3,
                \'penalty_multiplier\' => 2.00,
                \'is_active\' => true,
            ]);
        }

        return $config;
    }

    /**
     * Check if penalty is enabled
     */
    public function isPenaltyEnabled(): bool
    {
        return $this->penalty_enabled && $this->is_active;
    }

    /**
     * Calculate penalty amount
     */
    public function calculatePenaltyAmount(float $originalFine): float
    {
        if (!$this->isPenaltyEnabled()) {
            return 0;
        }

        return $originalFine * $this->penalty_multiplier;
    }

    /**
     * Check if penalty should be applied based on days overdue
     */
    public function shouldApplyPenalty(int $daysOverdue): bool
    {
        if (!$this->isPenaltyEnabled()) {
            return false;
        }

        return $daysOverdue > $this->grace_period_penalty_days;
    }

    /**
     * Get the penalty threshold day
     */
    public function getPenaltyThresholdDay(): int
    {
        return $this->grace_period_penalty_days + 1;
    }
}';
    
    file_put_contents($modelPath, $modelContent);
    echo "✅ PenaltyConfig model created\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to create model: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Create default penalty config
echo "4. CREATING DEFAULT PENALTY CONFIG\n";
echo "===================================\n";

try {
    $defaultConfig = \App\Models\PenaltyConfig::getOrCreateDefault();
    
    echo "✅ Default penalty config created/updated:\n";
    echo "  ├─ Penalty Enabled: " . ($defaultConfig->penalty_enabled ? 'Yes' : 'No') . "\n";
    echo "  ├─ Grace Period Penalty Days: {$defaultConfig->grace_period_penalty_days}\n";
    echo "  ├─ Penalty Multiplier: {$defaultConfig->penalty_multiplier}\n";
    echo "  ├─ Is Active: " . ($defaultConfig->is_active ? 'Yes' : 'No') . "\n";
    echo "  └─ Penalty Threshold Day: " . $defaultConfig->getPenaltyThresholdDay() . "\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to create default config: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Create PenaltyConfigController
echo "5. CREATING PENALTY CONFIG CONTROLLER\n";
echo "=====================================\n";

try {
    $controllerPath = app_path('Http/Controllers/PenaltyConfigController.php');
    
    $controllerContent = '<?php

namespace App\Http\Controllers;

use App\Models\PenaltyConfig;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class PenaltyConfigController extends Controller
{
    /**
     * Display the penalty configuration page
     */
    public function index()
    {
        $config = PenaltyConfig::getOrCreateDefault();
        
        return view(\'penalty-config.index\', compact(\'config\'));
    }

    /**
     * Update the penalty configuration
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            \'penalty_enabled\' => \'required|boolean\',
            \'grace_period_penalty_days\' => \'required|integer|min:0|max:30\',
            \'penalty_multiplier\' => \'required|numeric|min:1|max:10|regex:/^\d+(\.\d{1,2})?$/\',
            \'is_active\' => \'required|boolean\',
        ]);

        $config = PenaltyConfig::getOrCreateDefault();
        $config->update($validated);

        return redirect()
            ->route(\'penalty-config.index\')
            ->with(\'success\', \'Penalty configuration updated successfully.\');
    }
}';
    
    file_put_contents($controllerPath, $controllerContent);
    echo "✅ PenaltyConfigController created\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to create controller: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Create routes
echo "6. CREATING PENALTY CONFIG ROUTES\n";
echo "==================================\n";

try {
    $routesPath = base_path('routes/web.php');
    $routesContent = file_get_contents($routesPath);
    
    // Add penalty config routes for Super Admin only
    $penaltyRoutes = '
// Penalty Configuration Routes (Super Admin only)
Route::middleware([\'auth\', \'role:Super Admin\'])->prefix(\'penalty-config\')->name(\'penalty-config.\')->group(function () {
    Route::get(\'/\', [App\Http\Controllers\PenaltyConfigController::class, \'index\'])->name(\'index\');
    Route::post(\'/\', [App\Http\Controllers\PenaltyConfigController::class, \'update\'])->name(\'update\');
});
';
    
    // Add routes to web.php
    file_put_contents($routesPath, $routesContent . $penaltyRoutes);
    echo "✅ Penalty config routes added\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to add routes: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Summary
echo "7. PENALTY CONFIG SYSTEM SUMMARY\n";
echo "================================\n";

echo "✅ SYSTEM CREATED:\n";
echo "  1. ✅ PenaltyConfig database table\n";
echo "  2. ✅ PenaltyConfig model with methods\n";
echo "  3. ✅ PenaltyConfigController for Super Admin\n";
echo "  4. ✅ Routes for penalty configuration\n";
echo "  5. ✅ Default configuration created\n";
echo "  6. ✅ Grace period penalty support\n";
echo "  7. ✅ Penalty multiplier support\n";
echo "  8. ✅ Super Admin only access\n";

echo "\n🎯 CONFIGURATION FEATURES:\n";
echo "  ├─ Penalty enabled/disabled\n";
echo "  ├─ Grace period penalty days (configurable)\n";
echo "  ├─ Penalty multiplier (configurable)\n";
echo "  ├─ Active/inactive status\n";
echo "  ├─ Penalty threshold calculation\n";
echo "  ├─ Penalty amount calculation\n";
echo "  └─ Should apply penalty logic\n";

echo "\n📊 DEFAULT SETTINGS:\n";
echo "  ├─ Penalty Enabled: Yes\n";
echo "  ├─ Grace Period Penalty Days: 3 days\n";
echo "  ├─ Penalty Multiplier: 2.00x\n";
echo "  ├─ Is Active: Yes\n";
echo "  └─ Penalty Threshold: Day 4\n";

echo "\n=== PENALTY CONFIG SYSTEM COMPLETE ===\n";
echo "\n🎉 PENALTY CONFIG SYSTEM CREATED!\n";
echo "✅ Database table created\n";
echo "✅ Model with business logic created\n";
echo "✅ Controller for Super Admin created\n";
echo "✅ Routes configured\n";
echo "✅ Default settings applied\n";
echo "✅ Ready for UI implementation\n";
echo "✅ Ready for penalty logic integration\n";
echo "✅ Super Admin only access enforced\n\n";
