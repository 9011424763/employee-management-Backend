<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SalaryRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function attendanceExport(Request $request): StreamedResponse|JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        $q = Attendance::query()->with('user.department');
        if ($f = $request->query('from')) {
            $q->whereDate('date', '>=', $f);
        }
        if ($t = $request->query('to')) {
            $q->whereDate('date', '<=', $t);
        }
        if ($d = $request->query('department_id')) {
            $q->whereHas('user', fn ($uq) => $uq->where('department_id', $d));
        }
        $rows = $q->orderBy('date')->get();

        $name = 'attendance_report_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'email', 'date', 'status', 'department', 'note']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->user->name,
                    $r->user->email,
                    $r->date->toDateString(),
                    $r->status,
                    $r->user->department?->name ?? '',
                    $r->note,
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function salaryExport(Request $request): StreamedResponse|JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Admins only.'], 403);
        }
        $q = SalaryRecord::query()->with('user.department');
        if ($y = $request->query('year')) {
            $q->where('year', (int) $y);
        }
        if ($m = $request->query('month')) {
            $q->where('month', (int) $m);
        }
        $rows = $q->orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $name = 'salary_report_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'email', 'year', 'month', 'gross', 'bonus', 'deductions', 'net', 'department']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->user->name,
                    $r->user->email,
                    $r->year,
                    $r->month,
                    $r->gross,
                    $r->bonus,
                    $r->deductions,
                    $r->net,
                    $r->user->department?->name ?? '',
                ]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
