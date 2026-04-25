<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();
        if (! $u->isAdmin()) {
            $today = Carbon::today()->toDateString();
            $at = Attendance::where('user_id', $u->id)->where('date', $today)->first();

            return response()->json([
                'role' => 'employee',
                'attendance_today' => $at?->status,
                'pending_leaves' => LeaveRequest::where('user_id', $u->id)->where('status', 'pending')->count(),
            ]);
        }

        $deptNames = User::query()
            ->select('departments.name', DB::raw('count(*) as total'))
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->where('users.role', 'employee')
            ->groupBy('departments.name')
            ->get()
            ->map(function ($r) {
                $label = $r->name ?? 'Unassigned';

                return ['department' => $label, 'total' => (int) $r->total];
            });

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $present = Attendance::whereDate('date', $d)->where('status', 'present')->count();
            $trend[] = ['date' => $d->toDateString(), 'present' => $present];
        }

        $today = Carbon::today()->toDateString();
        $attToday = Attendance::where('date', $today)->get();

        return response()->json([
            'role' => 'admin',
            'totals' => [
                'employees' => User::where('role', 'employee')->count(),
                'departments' => Department::count(),
                'leaves_pending' => LeaveRequest::where('status', 'pending')->count(),
                'attendance_today' => $attToday->count(),
            ],
            'attendance_7d' => $trend,
            'department_headcount' => $deptNames,
        ]);
    }
}
