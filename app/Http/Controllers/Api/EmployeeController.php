<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('department')->where('role', 'employee');
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($dept = $request->query('department_id')) {
            $query->where('department_id', $dept);
        }

        return $query->orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);
        $data['role'] = 'employee';

        $user = User::create($data);

        return response()->json($user->load('department'), 201);
    }

    public function show(User $employee)
    {
        if ($employee->role !== 'employee') {
            return response()->json(['message' => 'Not an employee.'], 404);
        }

        return $employee->load('department');
    }

    public function update(Request $request, User $employee)
    {
        if ($employee->role !== 'employee') {
            return response()->json(['message' => 'Not an employee.'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'sometimes', Password::min(8)],
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $employee->update($data);

        return $employee->refresh()->load('department');
    }

    public function destroy(User $employee)
    {
        if ($employee->role !== 'employee') {
            return response()->json(['message' => 'Not an employee.'], 404);
        }
        $employee->delete();

        return response()->json(null, 204);
    }
}
