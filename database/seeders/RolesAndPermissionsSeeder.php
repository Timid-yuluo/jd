<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // User management
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.batch_delete',
            // Resume management
            'resumes.view', 'resumes.delete',
            // Interview management
            'interviews.view', 'interviews.delete',
            // Job application management
            'job-applications.view', 'job-applications.edit',
            // AI & logs
            'usage-logs.view',
            'ai-config.view', 'ai-config.edit',
            'ai-prompts.view', 'ai-prompts.edit',
            'ai-analytics.view',
            // System
            'site-settings.view', 'site-settings.edit',
            'system-setting-audits.view',
            'action-logs.view',
            // Access Bans
            'access-bans.view', 'access-bans.edit',
            // Notifications
            'notifications.view', 'notifications.edit',
            // File Manager
            'file-manager.view', 'file-manager.edit', 'file-manager.delete',
            // System Ops
            'system-ops.view', 'system-ops.edit',
            // RBAC
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.edit',
            // Schedule
            'schedule.view', 'schedule.run',
            // Feedback
            'feedbacks.view', 'feedbacks.edit', 'feedbacks.manage', 'feedbacks.reply', 'feedbacks.adopt', 'feedbacks.reward', 'feedbacks.delete',
            // Membership / Plans
            'plans.manage',
            // Help Center
            'help-center.view', 'help-center.manage',
            // Global admin access
            'access admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        /** @var Role $superAdmin */
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);

        /** @var Role $admin */
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(array_diff($permissions, [
            'action-logs.view',
            'system-setting-audits.view',
            'users.batch_delete',
            'system-ops.edit',
        ]));

        /** @var Role $editor */
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->syncPermissions([
            'access admin',
            'resumes.view', 'resumes.delete',
            'interviews.view', 'interviews.delete',
            'job-applications.view', 'job-applications.edit',
            'usage-logs.view',
            'ai-analytics.view',
        ]);

        // Migrate existing admin users
        User::query()->where('is_admin', true)->each(function (User $user): void {
            if (! $user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        });
    }
}
