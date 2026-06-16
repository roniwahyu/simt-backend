<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantModule;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\File;

class ManageModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simt:module {action : The action to perform (install, uninstall, status)} {module : The name of the module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage module lifecycle (install, uninstall, status) including composer autoload, migrations, and tenant subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $module = $this->argument('module');

        if (!in_array($action, ['install', 'uninstall', 'status'])) {
            $this->error("Invalid action. Supported actions: install, uninstall, status");
            return 1;
        }

        $modulePath = base_path("Modules/{$module}");

        if (!File::exists($modulePath)) {
            $this->error("Module folder not found at {$modulePath}");
            return 1;
        }

        switch ($action) {
            case 'status':
                return $this->showStatus($module);
            case 'install':
                return $this->installModule($module);
            case 'uninstall':
                return $this->uninstallModule($module);
        }

        return 0;
    }

    /**
     * Show global and tenant status of a module
     */
    protected function showStatus(string $moduleName): int
    {
        $module = Module::find($moduleName);
        $globalEnabled = $module ? $module->isEnabled() : false;
        
        $this->info("--- Module Status: {$moduleName} ---");
        $this->info("Global Code Level (nwidart): " . ($globalEnabled ? "ENABLED" : "DISABLED"));
        
        $activeTenants = TenantModule::where('module_code', $moduleName)
            ->where('active', true)
            ->with('tenant')
            ->get();
            
        $this->info("Active Tenant Subscriptions: " . $activeTenants->count());
        foreach ($activeTenants as $sub) {
            $tenantName = $sub->tenant->name ?? 'Unknown';
            $tenantDomain = $sub->tenant->domain ?? 'Unknown';
            $this->line(" - Tenant: {$tenantName} ({$tenantDomain})");
        }
        
        return 0;
    }

    /**
     * Install and integrate a module
     */
    protected function installModule(string $moduleName): int
    {
        $this->info("Installing module {$moduleName}...");

        // 1. Enable module in nwidart
        $this->info("Enabling module globally...");
        $this->call('module:enable', ['module' => [$moduleName]]);

        // 2. Autoload verification (Composer check)
        $this->info("Verifying composer namespace registration...");
        $composerJsonPath = base_path('composer.json');
        if (File::exists($composerJsonPath)) {
            $composer = json_decode(File::get($composerJsonPath), true);
            $ns = "Modules\\{$moduleName}\\";
            if (!isset($composer['autoload']['psr-4'][$ns])) {
                $this->warn("Namespace {$ns} was not found in root composer.json. Registering it...");
                $composer['autoload']['psr-4'][$ns] = "Modules/{$moduleName}/app/";
                $composer['autoload']['psr-4']["Modules\\{$moduleName}\\Database\\Seeders\\"] = "Modules/{$moduleName}/database/seeders/";
                File::put($composerJsonPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->runComposerDump();
            } else {
                $this->info("Namespace {$ns} already registered in composer.json.");
            }
        }

        // 3. Run module database migrations
        $this->info("Running module database migrations...");
        $migrationPath = module_path($moduleName, 'database/migrations');
        if (File::exists($migrationPath)) {
            app('migrator')->path($migrationPath);
        }
        $this->call('module:migrate', ['module' => $moduleName]);

        // 4. Register active subscription for all existing tenants
        $this->info("Registering active subscriptions for all tenants...");
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            TenantModule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'module_code' => $moduleName],
                ['active' => true]
            );
        }

        $this->info("Module {$moduleName} installed and integrated successfully!");
        return 0;
    }

    /**
     * Uninstall a module
     */
    protected function uninstallModule(string $moduleName): int
    {
        $this->warn("Uninstalling module {$moduleName}...");

        // 1. Reset/Rollback database migrations for the module
        $this->info("Rolling back module database migrations...");
        $migrationPath = module_path($moduleName, 'database/migrations');
        if (File::exists($migrationPath)) {
            app('migrator')->path($migrationPath);
        }
        try {
            $this->call('module:migrate-reset', ['module' => $moduleName]);
        } catch (\Throwable $e) {
            $this->error("Migration reset failed or tables do not exist: " . $e->getMessage());
        }

        // 2. Disable module in nwidart
        $this->info("Disabling module globally...");
        $this->call('module:disable', ['module' => [$moduleName]]);

        // 3. Deactivate subscription for all tenants in the database
        $this->info("Deactivating subscriptions for all tenants...");
        TenantModule::where('module_code', $moduleName)->update(['active' => false]);

        $this->info("Module {$moduleName} uninstalled successfully.");
        return 0;
    }

    /**
     * Regenerate composer autoload safely
     */
    protected function runComposerDump(): void
    {
        $this->info("Running composer dump-autoload --optimize --no-scripts...");
        $composerPath = 'D:\\laragon\\bin\\composer\\composer.phar';
        if (!File::exists($composerPath)) {
            $composerPath = 'composer';
        }
        
        $output = [];
        $retval = null;
        exec("php83 \"$composerPath\" dump-autoload --optimize --no-scripts 2>&1", $output, $retval);
        
        if ($retval === 0) {
            $this->info("Composer autoload regenerated successfully.");
        } else {
            $this->error("Composer autoload generation failed:");
            $this->line(implode("\n", $output));
        }
    }
}
