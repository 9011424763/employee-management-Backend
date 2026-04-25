<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        $query = Attendance::with('user.department');
        if ($d = $request->query('date')) {
            $query->whereDate('date', $d);
        }
        if ($u = $request->query('user_id')) {
            $query->where('user_id', $u);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('date', '<=', $to);
        }

        return $query->orderByDesc('date')->orderBy('user_id')->paginate(50);
    }

    public function my(Request $request)
    {
        $query = Attendance::where('user_id', $request->user()->id);
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereYear('date', (int) $request->query('year'))
                ->whereMonth('date', (int) $request->query('month'));
        } elseif ($request->filled('year')) {
            $query->whereYear('date', (int) $request->query('year'));
        }

        return $query->orderByDesc('date')->limit(120)->get();
    }

    public function store(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,leave,holiday',
            'note' => 'nullable|string',
        ]);
        $target = User::findOrFail($data['user_id']);
        if ($target->role !== 'employee') {
            return response()->json(['message' => 'Can only mark attendance for employees.'], 422);
        }
        $day = Carbon::parse($data['date'])->toDateString();
        $att = Attendance::updateOrCreate(
            ['user_id' => $data['user_id'], 'date' => $day],
            ['status' => $data['status'], 'note' => $data['note'] ?? null]
        );

        return response()->json($att->load('user'), 201);
    }
}
