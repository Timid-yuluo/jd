<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 邮件模板管理控制器
 */
final class EmailTemplateController extends Controller
{
    /**
     * 邮件模板列表
     */
    public function index(): View
    {
        $templates = EmailTemplate::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate((int) config('ui.pagination.user_list', 10));

        return view('admin.notifications.email-templates', compact('templates'));
    }

    /**
     * 创建邮件模板页面
     */
    public function create(): View
    {
        $defaultVariables = EmailTemplate::defaultVariables();

        return view('admin.notifications.email-templates-create', compact('defaultVariables'));
    }

    /**
     * 保存邮件模板
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:email_templates,slug',
            'subject' => 'required|string|max:200',
            'content' => 'required|string|max:50000',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        EmailTemplate::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'description' => $validated['description'],
            'variables' => EmailTemplate::defaultVariables(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.notifications.email-templates')
            ->with('success', '邮件模板已创建。');
    }

    /**
     * 编辑邮件模板页面
     */
    public function edit(EmailTemplate $template): View
    {
        $defaultVariables = EmailTemplate::defaultVariables();

        return view('admin.notifications.email-templates-edit', compact('template', 'defaultVariables'));
    }

    /**
     * 更新邮件模板
     */
    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:email_templates,slug,'.$template->id,
            'subject' => 'required|string|max:200',
            'content' => 'required|string|max:50000',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'description' => $validated['description'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.notifications.email-templates')
            ->with('success', '邮件模板已更新。');
    }

    /**
     * 删除邮件模板
     */
    public function destroy(EmailTemplate $template): RedirectResponse
    {
        // 检查是否有通知使用此模板
        if ($template->notifications()->exists()) {
            return redirect()->back()
                ->with('error', '该模板正在使用中，无法删除。');
        }

        $template->delete();

        return redirect()->route('admin.notifications.email-templates')
            ->with('success', '邮件模板已删除。');
    }

    /**
     * 预览邮件模板
     */
    public function preview(Request $request, EmailTemplate $template): JsonResponse
    {
        $data = $request->input('data', []);

        // 默认预览数据
        $defaultData = [
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
            'user_name' => '张三',
            'user_email' => 'user@example.com',
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'notification_title' => '这是一封测试邮件',
            'notification_content' => '<p>这是邮件的正文内容，支持 <strong>HTML</strong> 格式。</p>',
        ];

        $rendered = $template->render(array_merge($defaultData, $data));

        return response()->json([
            'success' => true,
            'subject' => $rendered['subject'],
            'content' => $rendered['content'],
        ]);
    }
}
