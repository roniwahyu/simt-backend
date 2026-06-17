<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $tenants = Tenant::withCount([
            'users',
            'students',
            'classes',
            'modules' => function ($q) {
                $q->where('active', true);
            }
        ])->latest()->paginate(20);

        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_users' => User::count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
        ];

        return view('core::super.dashboard', compact('tenants', 'stats'));
    }

    public function createTenant(): View
    {
        return view('core::super.tenant-create');
    }

    public function storeTenant(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:100|unique:tenants',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $tenant = Tenant::create([
            'name' => $request->input('name'),
            'domain' => $request->input('domain'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'status' => 'contracted',
        ]);

        // Activate default modules: Core + Student + Attendance + Finance
        $defaultModules = ['Core', 'Student', 'Attendance', 'Finance'];
        foreach ($defaultModules as $mod) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }

        // Create default admin user for tenant
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin ' . $tenant->name,
            'email' => 'admin@' . $tenant->domain,
            'phone' => $request->input('phone', '08' . rand(1000000000, 9999999999)),
            'password' => Hash::make('simt2026'),
            'role_display' => 'kepala_madrasah',
        ])->assignRole('kepala_madrasah');

        return redirect()->route('super.dashboard')->with('success', 'Tenant berhasil dibuat.');
    }

    public function editTenant(Tenant $tenant): View
    {
        $tenant->load('modules');
        $allModules = ['Core', 'Student', 'Attendance', 'Finance', 'Tahfiz', 'Inklusi'];
        return view('core::super.tenant-edit', compact('tenant', 'allModules'));
    }

    public function updateTenant(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:prospect,contracted,active,grace_read,suspended,terminated',
        ]);

        $tenant->update($request->only('name', 'phone', 'address', 'status'));

        // Update modules
        if ($request->has('modules')) {
            foreach ($request->input('modules', []) as $mod => $active) {
                TenantModule::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'module_code' => $mod],
                    ['active' => (bool) $active]
                );
            }
        }

        return redirect()->route('super.tenant.edit', $tenant)->with('success', 'Tenant diperbarui.');
    }

    public function auditLogs(Request $request): View
    {
        $query = \App\Models\AuditLog::with(['tenant', 'user'])->orderBy('id', 'desc');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', 'App\\Models\\' . $request->input('auditable_type'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $logs = $query->paginate(50)->withQueryString();
        $tenants = Tenant::select('id', 'name')->get();
        $users = User::select('id', 'name')->get();

        return view('core::super.audit_logs', compact('logs', 'tenants', 'users'));
    }

    public function failedJobs(Request $request): View
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('core::super.failed_jobs', compact('failedJobs'));
    }

    public function retryFailedJob($id): RedirectResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => $id]);
            return redirect()->route('super.failed-jobs')->with('success', "Job #{$id} berhasil di-retry.");
        } catch (\Throwable $e) {
            return redirect()->route('super.failed-jobs')->with('error', "Gagal me-retry Job: " . $e->getMessage());
        }
    }

    public function deleteFailedJob($id): RedirectResponse
    {
        try {
            Artisan::call('queue:forget', ['id' => $id]);
            return redirect()->route('super.failed-jobs')->with('success', "Job #{$id} berhasil dihapus.");
        } catch (\Throwable $e) {
            return redirect()->route('super.failed-jobs')->with('error', "Gagal menghapus Job: " . $e->getMessage());
        }
    }
}
