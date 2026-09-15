<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// 定时任务调度
// 发送定时通知
Schedule::command('notifications:send-scheduled')->everyMinute();

// 每天上午9点发送申请截止提醒
Schedule::command('notifications:send-reminders --type=deadline --days=3')->dailyAt('09:00');

// 每天上午10点发送面试未完成提醒
Schedule::command('notifications:send-reminders --type=interview')->dailyAt('10:00');

// 每周一早上8点发送周报提醒（可选）
// Schedule::command('notifications:weekly-report')->weeklyOn(1, '08:00');

// OAuth 健康巡检（告警模式，不自动处置）
Schedule::command('oauth:diagnose --hours=1 --alert-min-failures=3 --alert-failure-rate=80 --fail-on-alert')
    ->hourly()
    ->withoutOverlapping();

// 会员相关定时任务
// 每天凌晨处理到期订阅
Schedule::command('membership:expire-subscriptions')->dailyAt('00:00');

// 每天上午10点检查即将到期的订阅并发送提醒
Schedule::command('subscriptions:check-expiring --days=3')->dailyAt('10:00');

// 每天凌晨清理过期次卡
Schedule::command('membership:clean-expired-credits')->dailyAt('00:10');

// 每小时处理超时未支付订单（默认超过72小时）
Schedule::command('membership:expire-pending-orders --hours=72')
    ->hourly()
    ->withoutOverlapping();

// 外部招聘数据同步（抓取后自动执行智能审核）
Schedule::command('scrape:offerstar --page=1 --pages=2 --delay=1 --auto-approve=0')
    ->hourly()
    ->withoutOverlapping()
    ->then(function () {
        Artisan::call('external-recruitments:auto-review');
    });

// 求职方舟校招汇总同步（抓取后自动执行智能审核）
Schedule::command('scrape:qiuzhifangzhou-campus --days=7 --auto-approve=0')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->then(function () {
        Artisan::call('external-recruitments:auto-review');
    });

// 求职方舟职位流同步（抓取后自动执行智能审核）
Schedule::command('scrape:qiuzhifangzhou-position --auto-approve=0')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->then(function () {
        Artisan::call('external-recruitments:auto-review');
    });

// 外部投递链接可达性巡检（失效链接自动回流待审核池）
Schedule::command('links:probe-external-recruitments --limit=300 --only-approved=1 --mark-pending=1 --dry-run=0')
    ->everySixHours()
    ->withoutOverlapping();

// 简历异步优化健康巡检与超时回收
Schedule::command('resume:optimize:check-health')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('resume:optimize:recycle-stale --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

// 岗位分析历史与审计日志保留期清理
Schedule::command('job-match:purge-history')
    ->dailyAt('01:30')
    ->withoutOverlapping();

// 每天凌晨4点清理已过冷静期的注销账号
Schedule::command('accounts:purge-deletions')->dailyAt('04:00');

// 每天凌晨2点清理过期封禁规则和旧访问日志
Schedule::command('purge:access-bans-logs --days=30')->dailyAt('02:00');

// 每天凌晨2:30清理定时任务日志（保留7天）和用户操作日志（保留90天）
Schedule::command('purge:system-logs --schedule-days=7 --action-days=90')->dailyAt('02:30');

// 每天凌晨3点清理过期访问记录（保留90天）
Schedule::command('purge:page-visits --days=90')->dailyAt('03:00');

// 每小时清理过期 Session 记录（database 驱动），避免 sessions 表无限增长
Schedule::command('session:gc')->hourly()->withoutOverlapping();

// 每天凌晨4:30校验用户套餐一致性并自动修正
Schedule::command('validate:plan-consistency --fix')->dailyAt('04:30');

// 每5分钟执行健康检查（队列积压/数据库/Redis/存储）
Schedule::command('health:check --threshold=100 --silent')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('健康检查失败，请立即排查');
    });

// 每小时同步需要更新的第三方模板源
Schedule::call(function () {
    \App\Services\Resume\Template\Source\TemplateSourceSyncService::class;
    $syncService = app(\App\Services\Resume\Template\Source\TemplateSourceSyncService::class);
    $syncService->syncAllActiveSources();
})->hourly()->name('template-sources:sync')->withoutOverlapping();

// 每小时扫描超过6小时未活跃的面试，自动结束并生成报告
Schedule::command('interview:auto-finish-stale --hours=6 --limit=100')
    ->hourly()
    ->withoutOverlapping();

// 每天上午9点发送面试提醒（24小时内有面试的用户）
Schedule::command('app:send-interview-reminders')->dailyAt('09:00');

// 每周一上午9点发送求职进度周报摘要
Schedule::command('app:send-weekly-digest')->weeklyOn(1, '09:00');

// 每天凌晨5点为活跃用户生成岗位推荐（队列异步处理）
Schedule::command('recommendations:daily --limit=100')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('每日岗位推荐任务执行失败');
    });
