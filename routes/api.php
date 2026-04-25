<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SalaryController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/user', [AuthController::class, 'me']);

    Route::get('dashboard', DashboardController::class);

    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{department}', [DepartmentController::class, 'show']);

    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::get('attendance/my', [AttendanceController::class, 'my']);

    Route::get('leaves', [LeaveRequestController::class, 'index']);
    Route::post('leaves', [LeaveRequestController::class, 'store']);
    Route::get('leaves/{leave}', [LeaveRequestController::class, 'show']);
    Route::patch('leaves/{leave}/status', [LeaveRequestController::class, 'updateStatus']);

    Route::get('salary/payslip/{user}/{year}/{month}', [SalaryController::class, 'payslip'])
        ->whereNumber('year')
        ->whereNumber('month');
    Route::get('salary', [SalaryController::class, 'index']);

    Route::middleware('admin')->group(function () {
        Route::post('departments', [DepartmentController::class, 'store']);
        Route::put('departments/{department}', [DepartmentController::class, 'update']);
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);

        Route::apiResource('employees', EmployeeController::class);
        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::post('attendance', [AttendanceController::class, 'store']);
        Route::post('salary', [SalaryController::class, 'store']);
        Route::get('reports/attendance/export', [ReportController::class, 'attendanceExport']);
        Route::get('reports/salary/export', [ReportController::class, 'salaryExport']);
    });
});
