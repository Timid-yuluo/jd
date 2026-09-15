<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionController extends Controller
{
    /**
     * 权限分组配置
     */
    private const PERMISSION_GROUPS = [
        'users' => [
            'label' => '用户管理',
            'icon' => 'ti-users',
            'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete', 'users.batch_delete'],
        ],
        'resumes' => [
            'label' => '简历管理',
            'icon' => 'ti-file-text',
            'permissions' => ['resumes.view', 'resumes.delete'],
        ],
        'interviews' => [
            'label' => '面试记录',
            'icon' => 'ti-message-chatbot',
            'permissions' => ['interviews.view', 'interviews.delete'],
        ],
        'job-applications' => [
            'label' => '投递看板',
            'icon' => 'ti-layout-kanban',
            'permissions' => ['job-applications.view', 'job-applications.edit'],
        ],
        'ai' => [
            'label' => 'AI 管理',
            'icon' => 'ti-brain',
            'permissions' => ['usage-logs.view', 'ai-config.view', 'ai-config.edit', 'ai-prompts.view', 'ai-prompts.edit', 'ai-analytics.view'],
        ],
        'system' => [
            'label' => '系统管理',
            'icon' => 'ti-settings',
            'permissions' => ['site-settings.view', 'site-settings.edit', 'system-setting-audits.view', 'action-logs.view', 'system-ops.view', 'system-ops.edit'],
        ],
        'notifications' => [
            'label' => '通知中心',
            'icon' => 'ti-bell',
            'permissions' => ['notifications.view', 'notifications.edit'],
        ],
        'file-manager' => [
            'label' => '文件管理',
            'icon' => 'ti-folder',
            'permissions' => ['file-manager.view', 'file-manager.edit', 'file-manager.delete'],
        ],
        'rbac' => [
            'label' => '权限管理',
            'icon' => 'ti-shield',
            'permissions' => ['roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'permissions.view', 'permissions.edit'],
        ],
        'schedule' => [
            'label' => '定时任务',
            'icon' => 'ti-clock',
            'permissions' => ['schedule.view', 'schedule.run'],
        ],
        'feedbacks' => [
            'label' => '意见反馈',
            'icon' => 'ti-message-report',
            'permissions' => ['feedbacks.view', 'feedbacks.edit', 'feedbacks.manage', 'feedbacks.reply', 'feedbacks.adopt', 'feedbacks.reward', 'feedbacks.delete'],
        ],
    ];

    /**
     * 角色列表
     */
    public function index(): View
    {
        $roles = Role::withCount('users', 'permissions')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_roles' => $roles->count(),
            'total_permissions' => Permission::count(),
            'super_admins' => Role::where('name', 'super-admin')->first()?->users_count ?? 0,
        ];

        return view('admin.roles-permissions.index', compact('roles', 'stats'));
    }

    /**
     * 创建角色页面
     */
    public function create(): View
    {
        $groups = $this->getPermissionGroups();

        return view('admin.roles-permissions.create', compact('groups'));
    }

    /**
     * 存储角色
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'display_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // 保存显示名称和描述到数据库（通过settings或额外字段）
        $this->saveRoleMeta($role, $validated);

        return redirect()->route('admin.roles-permissions.index')
            ->with('success', "角色 \"{$validated['name']}\" 创建成功。");
    }

    /**
     * 编辑角色页面
     */
    public function edit(Role $role): View
    {
        $role->load('permissions');
        $groups = $this->getPermissionGroups();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles-permissions.edit', compact('role', 'groups', 'rolePermissions'));
    }

    /**
     * 更新角色
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        // 保护系统角色
        if (in_array($role->name, ['super-admin', 'admin', 'editor'])) {
            return back()->with('error', '系统预设角色不能修改名称。');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,'.$role->id,
            'display_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        $this->saveRoleMeta($role, $validated);

        return redirect()->route('admin.roles-permissions.index')
            ->with('success', "角色 \"{$role->name}\" 更新成功。");
    }

    /**
     * 删除角色
     */
    public function destroy(Role $role): RedirectResponse
    {
        // 保护系统角色
        if (in_array($role->name, ['super-admin', 'admin', 'editor'])) {
            return back()->with('error', '系统预设角色不能删除。');
        }

        // 检查是否有用户使用此角色
        if ($role->users()->count() > 0) {
            return back()->with('error', '该角色下还有用户，无法删除。请先转移用户角色。');
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('admin.roles-permissions.index')
            ->with('success', "角色 \"{$roleName}\" 已删除。");
    }

    /**
     * 权限列表
     */
    public function permissions(): View
    {
        $groups = $this->getPermissionGroups();
        $allPermissions = Permission::all()->keyBy('name');

        // 统计每个权限被多少角色使用
        $permissionStats = DB::table('role_has_permissions')
            ->select('permission_id', DB::raw('COUNT(*) as count'))
            ->groupBy('permission_id')
            ->pluck('count', 'permission_id')
            ->toArray();

        return view('admin.roles-permissions.permissions', compact('groups', 'allPermissions', 'permissionStats'));
    }

    /**
     * 同步所有权限（从配置）
     */
    public function syncPermissions(): RedirectResponse
    {
        $allPermissions = [];
        foreach (self::PERMISSION_GROUPS as $group) {
            $allPermissions = array_merge($allPermissions, $group['permissions']);
        }

        $created = 0;
        foreach ($allPermissions as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'web']);
                $created++;
            }
        }

        return redirect()->route('admin.roles-permissions.permissions')
            ->with('success', "权限同步完成，新增 {$created} 个权限。");
    }

    /**
     * 获取权限分组
     */
    private function getPermissionGroups(): array
    {
        $permissions = Permission::all()->keyBy('name');
        $groups = [];

        foreach (self::PERMISSION_GROUPS as $key => $config) {
            $groupPermissions = [];
            foreach ($config['permissions'] as $permName) {
                $perm = $permissions->get($permName);
                if ($perm) {
                    $groupPermissions[] = [
                        'name' => $permName,
                        'id' => $perm->id,
                        'label' => $this->getPermissionLabel($permName),
                    ];
                }
            }

            $groups[$key] = [
                'label' => $config['label'],
                'icon' => $config['icon'],
                'permissions' => $groupPermissions,
            ];
        }

        return $groups;
    }

    /**
     * 获取权限显示名称
     */
    private function getPermissionLabel(string $permission): string
    {
        $labels = [
            'users.view' => '查看用户',
            'users.create' => '创建用户',
            'users.edit' => '编辑用户',
            'users.delete' => '删除用户',
            'users.batch_delete' => '批量删除用户',
            'resumes.view' => '查看简历',
            'resumes.delete' => '删除简历',
            'interviews.view' => '查看面试',
            'interviews.delete' => '删除面试',
            'job-applications.view' => '查看投递',
            'job-applications.edit' => '编辑投递',
            'usage-logs.view' => '查看用量日志',
            'ai-config.view' => '查看AI配置',
            'ai-config.edit' => '编辑AI配置',
            'ai-prompts.view' => '查看Prompt模板',
            'ai-prompts.edit' => '编辑Prompt模板',
            'ai-analytics.view' => '查看用量分析',
            'site-settings.view' => '查看网站设置',
            'site-settings.edit' => '编辑网站设置',
            'system-setting-audits.view' => '查看系统审计',
            'action-logs.view' => '查看操作日志',
            'system-ops.view' => '查看系统运维',
            'system-ops.edit' => '执行运维操作',
            'notifications.view' => '查看通知',
            'notifications.edit' => '管理通知',
            'file-manager.view' => '查看文件',
            'file-manager.edit' => '上传/编辑文件',
            'file-manager.delete' => '删除文件',
            'roles.view' => '查看角色',
            'roles.create' => '创建角色',
            'roles.edit' => '编辑角色',
            'roles.delete' => '删除角色',
            'permissions.view' => '查看权限',
            'permissions.edit' => '管理权限',
            'schedule.view' => '查看定时任务',
            'schedule.run' => '手动执行任务',
            'feedbacks.view' => '查看反馈',
            'feedbacks.edit' => '反馈通用编辑',
            'feedbacks.manage' => '修改反馈状态/备注',
            'feedbacks.reply' => '回复反馈',
            'feedbacks.adopt' => '修改采纳结果',
            'feedbacks.reward' => '发放反馈奖励',
            'feedbacks.delete' => '删除反馈',
        ];

        return $labels[$permission] ?? $permission;
    }

    /**
     * 保存角色元数据到缓存
     */
    private function saveRoleMeta(Role $role, array $data): void
    {
        $meta = [];
        if (isset($data['display_name'])) {
            $meta['display_name'] = $data['display_name'];
        }
        if (isset($data['description'])) {
            $meta['description'] = $data['description'];
        }

        if (! empty($meta)) {
            Cache::put("role_meta:{$role->id}", $meta);
        }

        activity('role')
            ->performedOn($role)
            ->log('updated');
    }
}
