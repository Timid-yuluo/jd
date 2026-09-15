<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessBan;
use App\Models\AdminAccessLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

final class AccessBanController extends Controller
{
    public function index(Request $request): View
    {
        $query = AccessBan::query()->with('bannedBy')->latest();

        if ($request->filled('ban_type')) {
            $query->where('ban_type', $request->string('ban_type'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        if ($request->filled('search')) {
            $search = escapeLike($request->string('search'));
            $query->where('ban_value', 'like', "%{$search}%");
        }

        $bans = $query->paginate(20)->appends($request->query());

        $stats = [
            'total_active' => AccessBan::query()->active()->count(),
            'ip_bans' => AccessBan::query()->active()->where('ban_type', 'ip')->count(),
            'ip_range_bans' => AccessBan::query()->active()->where('ban_type', 'ip_range')->count(),
            'device_bans' => AccessBan::query()->active()->where('ban_type', 'device')->count(),
            'browser_bans' => AccessBan::query()->active()->where('ban_type', 'browser')->count(),
            'auto_bans_today' => AccessBan::query()->whereDate('created_at', today())->count(),
            'total_expired' => AccessBan::query()->where('expires_at', '<', now())->count(),
        ];

        $chartData = $this->getAccessTrendData();

        $recentDenied = AdminAccessLog::query()
            ->whereIn('action', ['denied', 'banned', 'suspicious', 'hijack', 'throttled'])
            ->latest('accessed_at')
            ->take(10)
            ->get();

        return view('admin.access-bans.index', compact('bans', 'stats', 'chartData', 'recentDenied'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ban_type' => 'required|in:ip,ip_range,device,browser',
            'ban_value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500',
            'severity' => 'required|in:block,captcha,log',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $validated['banned_by'] = $request->user()->id;

        if ($validated['ban_type'] === 'ip_range') {
            $validated['ban_value'] = $this->normalizeCidr($validated['ban_value']);
        }

        AccessBan::query()->create($validated);

        $this->clearBanCache($validated['ban_type'], $validated['ban_value']);

        return redirect()->route('admin.access-bans.index')
            ->with('success', 'Ban rule added.');
    }

    public function destroy(AccessBan $accessBan): RedirectResponse
    {
        $this->clearBanCache($accessBan->ban_type, $accessBan->ban_value);

        $accessBan->delete();

        return redirect()->route('admin.access-bans.index')
            ->with('success', 'Ban rule removed.');
    }

    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:access_bans,id',
        ]);

        $bans = AccessBan::query()->whereIn('id', $validated['ids'])->get();

        foreach ($bans as $ban) {
            $this->clearBanCache($ban->ban_type, $ban->ban_value);
            $ban->delete();
        }

        return redirect()->route('admin.access-bans.index')
            ->with('success', sprintf('%d ban rules removed.', count($validated['ids'])));
    }

    public function clearExpired(): RedirectResponse
    {
        $count = AccessBan::query()
            ->where('expires_at', '<', now())
            ->delete();

        return redirect()->route('admin.access-bans.index')
            ->with('success', sprintf('%d expired ban rules cleared.', $count));
    }

    public function accessLogs(Request $request): View
    {
        $query = AdminAccessLog::query()->latest('accessed_at');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', $request->string('ip'));
        }

        $logs = $query->paginate(30)->appends($request->query());

        $actionStats = AdminAccessLog::query()
            ->selectRaw('action, count(*) as cnt')
            ->where('accessed_at', '>=', now()->subDay())
            ->groupBy('action')
            ->pluck('cnt', 'action')
            ->toArray();

        return view('admin.access-bans.logs', compact('logs', 'actionStats'));
    }

    private function getAccessTrendData(): array
    {
        $days = 7;
        $labels = [];
        $deniedData = [];
        $accessData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('m/d');

            $deniedData[] = AdminAccessLog::query()
                ->whereIn('action', ['denied', 'banned', 'suspicious', 'hijack'])
                ->whereDate('accessed_at', $date)
                ->count();

            $accessData[] = AdminAccessLog::query()
                ->where('action', 'access')
                ->whereDate('accessed_at', $date)
                ->count();
        }

        return [
            'labels' => $labels,
            'denied' => $deniedData,
            'access' => $accessData,
        ];
    }

    private function normalizeCidr(string $value): string
    {
        $value = trim($value);

        if (str_contains($value, '/')) {
            return $value;
        }

        if (str_contains($value, '*')) {
            $parts = explode('.', $value);
            $mask = 0;
            foreach ($parts as $part) {
                if ($part !== '*') {
                    $mask += 8;
                } else {
                    break;
                }
            }

            $subnet = str_replace('*', '0', $value);

            return $subnet.'/'.$mask;
        }

        return $value.'/32';
    }

    private function clearBanCache(string $type, string $value): void
    {
        Cache::forget("ban_check:{$type}:{$value}");
    }
}
