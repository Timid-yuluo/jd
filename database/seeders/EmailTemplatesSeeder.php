<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => '系统通知邮件',
                'slug' => 'system-notification',
                'subject' => '{{ site_name }} - {{ notification_title }}',
                'content' => $this->getSystemNotificationTemplate(),
                'description' => '通用系统通知邮件模板',
            ],
            [
                'name' => '欢迎邮件',
                'slug' => 'welcome',
                'subject' => '欢迎加入 {{ site_name }}！',
                'content' => $this->getWelcomeTemplate(),
                'description' => '新用户注册成功后发送的欢迎邮件',
            ],
            [
                'name' => '维护通知',
                'slug' => 'maintenance',
                'subject' => '{{ site_name }} 维护通知',
                'content' => $this->getMaintenanceTemplate(),
                'description' => '系统维护前发送的通知邮件',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, [
                    'variables' => EmailTemplate::defaultVariables(),
                    'is_active' => true,
                ])
            );
        }
    }

    private function getSystemNotificationTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ notification_title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ site_name }}</h1>
        </div>
        <div class="content">
            <h2>{{ notification_title }}</h2>
            <div>{{ notification_content }}</div>
            <p style="margin-top: 30px;">
                <a href="{{ site_url }}" class="button">访问网站</a>
            </p>
        </div>
        <div class="footer">
            <p>此邮件由 {{ site_name }} 系统自动发送</p>
            <p>&copy; {{ current_date }} {{ site_name }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getWelcomeTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎加入 {{ site_name }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 28px; }
        .content { padding: 40px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .button { display: inline-block; padding: 12px 30px; background: #11998e; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .highlight { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 欢迎加入 {{ site_name }}！</h1>
        </div>
        <div class="content">
            <p>尊敬的 {{ user_name }}，</p>
            <p>感谢您注册 {{ site_name }}！我们很高兴您能加入我们。</p>
            <div class="highlight">
                <strong>您的账户信息：</strong><br>
                用户名：{{ user_name }}<br>
                邮箱：{{ user_email }}
            </div>
            <p>现在您可以：</p>
            <ul>
                <li>创建和管理您的简历</li>
                <li>使用 AI 模拟面试功能</li>
                <li>获取个性化的面试建议</li>
            </ul>
            <p style="text-align: center;">
                <a href="{{ site_url }}" class="button">开始使用</a>
            </p>
        </div>
        <div class="footer">
            <p>如果您有任何问题，请随时联系我们的客服团队。</p>
            <p>&copy; {{ current_date }} {{ site_name }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getMaintenanceTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>维护通知</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ 维护通知</h1>
        </div>
        <div class="content">
            <p>尊敬的 {{ user_name }}，</p>
            <div class="warning-box">
                {{ notification_content }}
            </div>
            <p>维护期间，网站的部分功能可能无法正常使用。我们会在维护完成后第一时间通知您。</p>
            <p>给您带来的不便，敬请谅解。</p>
            <p style="margin-top: 30px;">
                <a href="{{ site_url }}" style="color: #f5576c;">访问网站</a>
            </p>
        </div>
        <div class="footer">
            <p>{{ site_name }} 运营团队</p>
            <p>&copy; {{ current_date }} {{ site_name }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
