<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = [
            [
                'name' => '简历优化完成通知',
                'slug' => 'resume-completed',
                'subject' => '🎉 {{ user_name }}，您的简历"{{ resume_title }}"优化完成！',
                'content' => $this->getResumeCompletedTemplate(),
                'description' => '简历优化完成后发送给用户的通知邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'user_email' => '用户邮箱',
                    'resume_title' => '简历标题',
                    'target_job' => '目标职位',
                    'target_company' => '目标公司',
                    'ats_score' => 'ATS评分',
                    'view_url' => '查看链接',
                ]),
                'is_active' => true,
            ],
            [
                'name' => '面试模拟完成报告',
                'slug' => 'interview-completed',
                'subject' => '📊 {{ user_name }}，您的面试模拟报告已生成',
                'content' => $this->getInterviewCompletedTemplate(),
                'description' => '面试模拟完成后发送给用户的报告邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'company' => '公司名称',
                    'position' => '应聘职位',
                    'type_label' => '面试类型',
                    'overall_score' => '综合得分',
                    'session_url' => '报告链接',
                ]),
                'is_active' => true,
            ],
            [
                'name' => '面试准备提醒',
                'slug' => 'interview-reminder',
                'subject' => '⏰ {{ user_name }}，别忘了继续您的面试准备',
                'content' => $this->getInterviewReminderTemplate(),
                'description' => '面试模拟未完成的提醒邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'company' => '公司名称',
                    'position' => '应聘职位',
                    'progress' => '当前进度',
                    'session_url' => '继续面试链接',
                ]),
                'is_active' => true,
            ],
            [
                'name' => '职位申请确认',
                'slug' => 'job-application-confirmation',
                'subject' => '✅ {{ user_name }}，您已向 {{ company }} 提交职位申请',
                'content' => $this->getJobApplicationTemplate(),
                'description' => '用户添加职位申请后发送的确认邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'company' => '公司名称',
                    'position' => '应聘职位',
                    'status_label' => '申请状态',
                    'application_url' => '申请管理链接',
                ]),
                'is_active' => true,
            ],
            [
                'name' => '申请截止提醒',
                'slug' => 'application-deadline',
                'subject' => '⏰ {{ user_name }}，{{ company }} 的申请即将截止',
                'content' => $this->getDeadlineReminderTemplate(),
                'description' => '职位申请截止前的提醒邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'company' => '公司名称',
                    'position' => '应聘职位',
                    'deadline' => '截止日期',
                    'days_left' => '剩余天数',
                    'application_url' => '申请链接',
                ]),
                'is_active' => true,
            ],
            [
                'name' => '简历导出完成',
                'slug' => 'resume-export-completed',
                'subject' => '✅ {{ user_name }}，您的简历导出已完成',
                'content' => $this->getResumeExportTemplate(),
                'description' => '简历导出完成后发送的邮件',
                'variables' => json_encode([
                    'site_name' => '网站名称',
                    'site_url' => '网站地址',
                    'user_name' => '用户名称',
                    'format_label' => '导出格式',
                    'file_url' => '下载链接',
                    'tasks_url' => '导出管理链接',
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'resume-completed',
            'interview-completed',
            'interview-reminder',
            'job-application-confirmation',
            'application-deadline',
            'resume-export-completed',
        ];

        EmailTemplate::whereIn('slug', $slugs)->delete();
    }

    /**
     * 获取简历优化完成模板
     */
    private function getResumeCompletedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #10b981;">🎉 恭喜！您的简历优化已完成</h1>
        <p>亲爱的 {{ user_name }}，您的简历已经成功优化</p>
    </div>

    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
        <div>ATS 兼容性评分</div>
        <div style="font-size: 48px; font-weight: bold; margin: 10px 0;">{{ ats_score }}</div>
        <div>满分 100 分</div>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">📄 简历信息</h3>
        <p><strong>简历标题：</strong>{{ resume_title }}</p>
        <p><strong>目标职位：</strong><span style="background: #fef3c7; padding: 2px 6px; border-radius: 3px;">{{ target_job }}</span></p>
        <p><strong>目标公司：</strong>{{ target_company }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ view_url }}" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">查看优化后的简历</a>
    </div>

    <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #065f46;">💡 后续建议</h3>
        <ul>
            <li>仔细查看优化后的内容，确保信息准确无误</li>
            <li>根据目标职位特点，针对性调整关键词</li>
            <li>可以使用我们的面试模拟功能准备面试</li>
            <li>定期更新简历，保持信息时效性</li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }

    /**
     * 获取面试完成模板
     */
    private function getInterviewCompletedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #3b82f6;">📊 面试模拟完成报告</h1>
        <p>亲爱的 {{ user_name }}，您的面试模拟已经结束</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">🎯 面试信息</h3>
        <p><strong>公司名称：</strong>{{ company }}</p>
        <p><strong>应聘职位：</strong>{{ position }}</p>
        <p><strong>面试类型：</strong>{{ type_label }}</p>
    </div>

    <div style="display: flex; justify-content: space-around; margin: 20px 0;">
        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; min-width: 100px;">
            <div style="font-size: 32px; font-weight: bold; color: #3b82f6;">{{ overall_score }}</div>
            <div style="font-size: 12px; color: #6b7280;">综合得分</div>
        </div>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ session_url }}" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">查看详细报告</a>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }

    /**
     * 获取面试提醒模板
     */
    private function getInterviewReminderTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #f59e0b;">⏰ 面试准备提醒</h1>
        <p>亲爱的 {{ user_name }}，您的面试模拟还未完成</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">📋 会话信息</h3>
        <p><strong>公司名称：</strong>{{ company }}</p>
        <p><strong>应聘职位：</strong>{{ position }}</p>
        <p><strong>当前进度：</strong>{{ progress }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ session_url }}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">继续面试</a>
    </div>

    <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #92400e;">✅ 面试前检查清单</h3>
        <ul>
            <li>回顾目标公司的业务和文化</li>
            <li>研究应聘职位的要求和职责</li>
            <li>准备 STAR 法则的案例</li>
            <li>准备好要问面试官的问题</li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }

    /**
     * 获取职位申请模板
     */
    private function getJobApplicationTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #10b981;">✅ 申请已记录</h1>
        <p>亲爱的 {{ user_name }}，您的职位申请已成功记录</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">📋 申请信息</h3>
        <p><strong>公司名称：</strong>{{ company }}</p>
        <p><strong>应聘职位：</strong>{{ position }}</p>
        <p><strong>当前状态：</strong><span style="background: #10b981; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">{{ status_label }}</span></p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ application_url }}" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">管理申请</a>
    </div>

    <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6;">
        <h3 style="margin-top: 0; color: #1e40af;">💡 后续建议</h3>
        <ul>
            <li>关注申请状态变化，及时跟进</li>
            <li>准备面试，可以使用我们的面试模拟功能</li>
            <li>继续投递其他感兴趣的职位</li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }

    /**
     * 获取截止提醒模板
     */
    private function getDeadlineReminderTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #f59e0b;">⏰ 申请截止提醒</h1>
        <p>亲爱的 {{ user_name }}，您的职位申请即将截止</p>
    </div>

    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
        <div>距离截止还有</div>
        <div style="font-size: 48px; font-weight: bold;">{{ days_left }} 天</div>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">📋 申请信息</h3>
        <p><strong>公司名称：</strong>{{ company }}</p>
        <p><strong>应聘职位：</strong>{{ position }}</p>
        <p><strong>截止日期：</strong>{{ deadline }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ application_url }}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">查看申请</a>
    </div>

    <div style="background: #fffbeb; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #92400e;">✅ 截止前检查清单</h3>
        <ul>
            <li>确认申请材料是否完整</li>
            <li>检查简历是否为最新版本</li>
            <li>准备求职信（如有需要）</li>
            <li>关注后续通知</li>
        </ul>
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }

    /**
     * 获取简历导出模板
     */
    private function getResumeExportTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; padding: 30px 0;">
        <h1 style="color: #10b981;">✅ 简历导出完成</h1>
        <p>亲爱的 {{ user_name }}，您的简历已成功导出</p>
    </div>

    <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
        <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
        <h3 style="margin: 0; color: #065f46;">导出成功！</h3>
        <p style="margin: 10px 0 0 0;">您的简历已经准备好，可以直接下载使用</p>
    </div>

    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #4b5563;">📋 导出信息</h3>
        <p><strong>导出格式：</strong>{{ format_label }}</p>
        <p><strong>导出时间：</strong>{{ current_date }} {{ current_time }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ file_url }}" style="display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px;">下载简历</a>
    </div>

    <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px;">
        <strong>💡 提示：</strong>文件将在服务器保存30天，请及时下载保存。
    </div>

    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>此邮件由系统自动发送，请勿直接回复</p>
        <p>&copy; {{ current_date }} {{ site_name }} 保留所有权利</p>
    </div>
</div>
HTML;
    }
};
