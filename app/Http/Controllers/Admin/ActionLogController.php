<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ActionLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminActionLog::query()->with('user');

        if ($search = $request->input('search')) {
            $query->where('action', 'like', "%{$search}%")
                ->orWhere('path', 'like', "%{$search}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.action_logs.index', compact('logs'));
    }

    public function exportCsv(Request $request)
    {
        $query = AdminActionLog::query()->with('user');

        if ($search = $request->input('search')) {
            $query->where('action', 'like', "%{$search}%")
                ->orWhere('path', 'like', "%{$search}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest('id')->limit(10000)->get();

        $csv = "ID,用户,操作,方法,路径,IP,时间\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->user?->name ?? '-',
                $log->action,
                $log->method,
                $log->path,
                $log->ip,
                $log->created_at->format('Y-m-d H:i:s')
            );
        }

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="action-logs-' . now()->format('YmdHis') . '.csv"',
        ]);
    }
}
