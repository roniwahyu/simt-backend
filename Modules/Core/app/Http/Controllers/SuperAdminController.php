<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $tenants = Tenant::withCount('users')->latest()->paginate(20);
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_users' => User::count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
        ];

        return view('admin.super.dashboard', compact('tenants', 'stats'));
    }

    public function createTenant(): View
    {
        return view('admin.super.tenant-create');
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
        return view('admin.super.tenant-edit', compact('tenant', 'allModules'));
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

        return redirect()->route('super.edit-tenant', $tenant)->with('success', 'Tenant diperbarui.');
    }
}
