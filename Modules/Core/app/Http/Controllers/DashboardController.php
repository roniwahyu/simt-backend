<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Attendance;
use App\Models\Bill;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = app(\App\Support\Tenancy::class)->tenant();
        $today = now()->toDateString();

        $stats = [
            'students_count' => Student::count(),
            'classes_count' => SchoolClass::count(),
            'today_attendance' => Attendance::where('date', $today)->where('status', 'H')->count(),
            'today_absent' => Attendance::where('date', $today)->where('status', 'A')->count(),
            'unpaid_bills' => Bill::where('status', 'unpaid')->count(),
        ];

        $recentAttendances = Attendance::with('student')
            ->where('date', $today)
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('core::dashboard', compact('user', 'tenant', 'stats', 'recentAttendances', 'today'));
    }

    public function auditLogs(Request $request): View
    {
        $query = \App\Models\AuditLog::with(['user'])->orderBy('id', 'desc');

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
        $tenantId = app(\App\Support\Tenancy::class)->tenantId();
        $users = \App\Models\User::where('tenant_id', $tenantId)->select('id', 'name')->get();

        return view('core::dashboard.audit_logs', compact('logs', 'users'));
    }
}
