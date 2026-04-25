<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
        ]);

        return response()->json(Department::create($data), 201);
    }

    public function show(Department $department)
    {
        return $department->load('users');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments,code,'.$department->id,
            'description' => 'nullable|string',
        ]);
        $department->update($data);

        return $department->refresh();
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return response()->json(['message' => 'Department has employees assigned. Reassign or remove them first.'], 422);
        }
        $department->delete();

        return response()->json(null, 204);
    }
}
