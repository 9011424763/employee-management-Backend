<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $q = LeaveRequest::with('user.department', 'approver');
            if ($s = $request->query('status')) {
                $q->where('status', $s);
            }

            return $q->orderByDesc('created_at')->paginate(30);
        }

        return LeaveRequest::with('approver')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type' => 'required|string|max:100',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $leave = LeaveRequest::create($data);

        return response()->json($leave->load('user'), 201);
    }

    public function show(Request $request, LeaveRequest $leave)
    {
        if (! $request->user()->isAdmin() && $leave->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $leave->load('user.department', 'approver');
    }

    public function updateStatus(Request $request, LeaveRequest $leave)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Request already decided.'], 422);
        }
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);
        $leave->update([
            'status' => $data['status'],
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return $leave->refresh()->load('user', 'approver');
    }
}
