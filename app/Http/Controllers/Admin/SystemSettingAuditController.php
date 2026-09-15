<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSettingAuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class SystemSettingAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'setting_key' => trim((string) $request->string('setting_key', '')),
            'changed_by_user_id' => (string) $request->string('changed_by_user_id', ''),
            'date_from' => (string) $request->string('date_from', ''),
            'date_to' => (string) $request->string('date_to', ''),
        ];

        $query = SystemSettingAuditLog::query()
            ->with('changedByUser')
            ->when($filters['setting_key'] !== '', static function (Builder $builder) use ($filters): void {
                $builder->where('setting_key', 'like', '%'.escapeLike($filters['setting_key']).'%');
            })
            ->when($filters['changed_by_user_id'] !== '', static function (Builder $builder) use ($filters): void {
                $builder->where('changed_by_user_id', (int) $filters['changed_by_user_id']);
            })
            ->when($filters['date_from'] !== '', static function (Builder $builder) use ($filters): void {
                $builder->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'] !== '', static function (Builder $builder) use ($filters): void {
                $builder->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->latest('id');

        $logs = $query->paginate((int) config('ui.pagination.admin_table', 20))->appends($request->query());

        $summary = [
            'total' => SystemSettingAuditLog::query()->count(),
            'today' => SystemSettingAuditLog::query()->whereDate('created_at', now()->toDateString())->count(),
            'unique_keys' => SystemSettingAuditLog::query()->distinct('setting_key')->count('setting_key'),
        ];

        return view('admin.system-setting-audits.index', [
            'logs' => $logs,
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }
}
