<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use App\Models\User;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $q = SalaryRecord::with('user.department');
            if ($u = $request->query('user_id')) {
                $q->where('user_id', $u);
            }
            if ($y = $request->query('year')) {
                $q->where('year', (int) $y);
            }
            if ($m = $request->query('month')) {
                $q->where('month', (int) $m);
            }

            return $q->orderByDesc('year')->orderByDesc('month')->paginate(30);
        }

        return SalaryRecord::where('user_id', $request->user()->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    public function store(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'gross' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'net' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $emp = User::findOrFail($data['user_id']);
        if ($emp->role !== 'employee') {
            return response()->json(['message' => 'Salary is stored for employees only.'], 422);
        }
        if (! isset($data['bonus'])) {
            $data['bonus'] = 0;
        }
        if (! isset($data['deductions'])) {
            $data['deductions'] = 0;
        }
        $row = SalaryRecord::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'year' => $data['year'],
                'month' => $data['month'],
            ],
            $data
        );

        return response()->json($row->load('user'), 201);
    }

    public function payslip(Request $request, User $user, int $year, int $month)
    {
        if (! $request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $rec = SalaryRecord::where('user_id', $user->id)->where('year', $year)->where('month', $month)->first();
        if (! $rec) {
            return response()->json(['message' => 'Payslip not found for this period.'], 404);
        }

        return [
            'employee' => $user->only(['id', 'name', 'email', 'job_title', 'phone']),
            'period' => ['year' => $rec->year, 'month' => $rec->month],
            'amounts' => [
                'gross' => (string) $rec->gross,
                'bonus' => (string) $rec->bonus,
                'deductions' => (string) $rec->deductions,
                'net' => (string) $rec->net,
            ],
            'notes' => $rec->notes,
        ];
    }
}
