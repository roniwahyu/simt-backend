<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
        $tenant = app('currentTenant');
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

        return view('admin.dashboard', compact('user', 'tenant', 'stats', 'recentAttendances', 'today'));
    }
}
