# 项目全量代码优化建议

> 扫描范围：Controllers / Services / Models / Middleware / Jobs / Providers
> 生成时间：2026-06-12
> 分批次按优先级排列，每项包含：问题描述、影响范围、具体代码位置、优化方案

---

## 第一批：数据库查询优化（P0 - 高优先级）

### 1.1 DashboardService.getAtsDistribution 重复查询

**问题**：`getAtsDistribution()` 对 `resumes` 表执行了 6 次独立查询（1次 count + 1次 avg + 1次 lowScoreCount + 4次分段统计），全部可合并为 1 次 `selectRaw`。

**位置**：`app/Services/Admin/DashboardService.php` L345-L375

**影响**：每次管理员访问 Dashboard 触发，缓存过期时产生 6 次 DB 查询

**方案**：合并为单次聚合查询：
```php
$row = Resume::query()->whereNotNull('ats_score')->selectRaw("
    COUNT(*) as total,
    ROUND(AVG(ats_score)) as avg_score,
    SUM(CASE WHEN ats_score < 60 THEN 1 ELSE 0 END) as seg_0_59,
    SUM(CASE WHEN ats_score BETWEEN 60 AND 71 THEN 1 ELSE 0 END) as seg_60_71,
    SUM(CASE WHEN ats_score BETWEEN 72 AND 84 THEN 1 ELSE 0 END) as seg_72_84,
    SUM(CASE WHEN ats_score >= 85 THEN 1 ELSE 0 END) as seg_85_100,
    SUM(CASE WHEN ats_score < 60 THEN 1 ELSE 0 END) as low_score_count
")->first();
```

### 1.2 DashboardService.buildDashboardData 查询过多

**问题**：`buildDashboardData()` 包含约 15+ 次独立 DB 查询（4个统计 selectRaw + 5个 JD 相关 clone 查询 + 3个营收查询 + 2个 Schema::hasTable + 多个 count 查询），虽有 300s 缓存但缓存 miss 时对 DB 压力大。

**位置**：`app/Services/Admin/DashboardService.php` L44-L310

**方案**：
- 将 4 个统计 selectRaw（users/resumes/interviews/applications）合并为 1 次查询
- JD 相关的 5 次 clone 查询合并为 1 次 selectRaw
- 2 个营收查询合并为 1 次 UNION ALL
- `Schema::hasTable('jobs')` 结果应缓存（表结构不会变），避免每次检查

### 1.3 DashboardService.get7DayTrend 三次独立聚合

**问题**：7日趋势数据分别对 `users`、`interview_sessions`、`resumes` 执行 3 次独立 GROUP BY 查询。

**位置**：`app/Services/Admin/DashboardService.php` L377-L410

**方案**：合并为 1 次查询或使用 UNION ALL：
```php
$trend = DB::select("
    SELECT 'users' as source, DATE(created_at) as d, COUNT(*) as c, NULL as avg_score
    FROM users WHERE created_at >= ? GROUP BY DATE(created_at)
    UNION ALL
    SELECT 'interviews', DATE(created_at), COUNT(*), NULL
    FROM interview_sessions WHERE created_at >= ? GROUP BY DATE(created_at)
    UNION ALL
    SELECT 'ats', DATE(created_at), NULL, AVG(ats_score)
    FROM resumes WHERE created_at >= ? AND ats_score IS NOT NULL GROUP BY DATE(created_at)
", [$startDate, $startDate, $startDate]);
```

### 1.4 ProfileController.checkNewDevice 低效查询

**问题**：`checkNewDevice()` 通过 `->count()` 判断是否新设备，但实际只需要 `->exists()`，count 会扫描所有匹配行。

**位置**：`app/Http/Controllers/User/ProfileController.php` L187

**方案**：将 `->count()` 改为 `->exists()`，性能从 O(n) 降为 O(1)

### 1.5 Resume.buildModulesSnapshot 未利用预加载

**问题**：`buildModulesSnapshot()` 调用 `$this->modules()->get()`，如果 Resume 模型未预加载 modules 关系则产生额外查询。

**位置**：`app/Models/Resume.php` L121

**方案**：改为 `$this->modules->map(...)` 使用已加载的关系，或在调用处确保 `load('modules')` 已执行

### 1.6 JobMatchingController.index 重复查询 UserCredit

**问题**：`index()` 方法先从缓存获取 `availableCreditsCount`，然后又单独执行 `UserCredit::getAvailableCredits($userId, 'job_match')` 获取完整集合，导致同一请求内对 user_credits 表查询 2 次。

**位置**：`app/Http/Controllers/User/JobMatchingController.php` L72-L76

**方案**：将 `availableCredits` 也纳入缓存，或从缓存数据中推断，避免重复查询

---

## 第二批：缓存策略优化（P1 - 中高优先级）

### 2.1 View Composer 每次请求读取 SystemSettingService

**问题**：`AppServiceProvider` 中注册了 `View::composer('*', ...)`，每个视图渲染都调用 `$settingService->all()`。虽有请求级内存缓存，但首次调用仍需从 Redis 读取 ~30 个配置项并解包为 PHP 数组，再提取 ~25 个变量传给每个视图。

**位置**：`app/Providers/AppServiceProvider.php` L131-L180

**方案**：
- 将设置项按用途分组缓存（`site_settings:branding`、`site_settings:seo`、`site_settings:analytics`），避免一次加载全部
- 仅在需要的视图中注入相关分组，而非全局 `*` composer
- 考虑将不常变的设置（ICP号、备案号等）缓存更长时间

### 2.2 VisitTrackingService.touchOnlineUser 使用 Cache 而非 Redis Sorted Set

**问题**：在线用户追踪使用 `Cache::get/put` 操作一个 PHP 数组（所有 session 映射），每次页面访问都要读取→过滤→写回整个数组，并发下容易丢失数据。

**位置**：`app/Services/VisitTrackingService.php` L233-L253

**方案**：改用 Redis Sorted Set（ZSET），score 为时间戳：
```php
Cache::put("visitor:session:{$sessionId}", true, self::ONLINE_TTL);
// 改为：
Redis::zadd('visitor:online_sessions', time(), $sessionId);
Redis::zremrangebyscore('visitor:online_sessions', 0, time() - self::ONLINE_TTL);
$count = Redis::zcard('visitor:online_sessions');
```
优势：原子操作、无并发冲突、O(log N) 复杂度

### 2.3 InterviewController.resolveInterviewPlanSummary 缓存 TTL 过短

**问题**：面试配额摘要缓存仅 30 秒，高频访问面试页面时频繁重建缓存，每次重建涉及 `quotaService->check()` 查询 DB。

**位置**：`app/Http/Controllers/User/InterviewController.php` L218

**方案**：将 TTL 提升至 120 秒，并在配额扣减时主动清除缓存（`Cache::forget`）

### 2.4 DashboardService 中 Schema::hasTable 不应每次检查

**问题**：`buildDashboardData()` 每次缓存 miss 都执行 `Schema::hasTable('jobs')` 和 `Schema::hasTable('failed_jobs')`，表结构在生产环境不会变化。

**位置**：`app/Services/Admin/DashboardService.php` L93-L97

**方案**：将表存在性检查结果缓存 24 小时，或在部署后通过 Artisan 命令预热

---

## 第三批：架构与代码质量优化（P1 - 中优先级）

### 3.1 控制器内联验证应抽取为 FormRequest

**问题**：项目中有 30+ 处 `$request->validate()` 内联验证逻辑，分散在控制器方法中，不符合 Laravel 最佳实践（验证逻辑应集中在 FormRequest 类中），且无法复用。

**位置**：涉及以下控制器（部分）：
- `InterviewController.php` L152, L212, L442, L504
- `ResumeEditorController.php` L96, L237
- `ResumeManageController.php` L250, L270, L351
- `FeedbackController.php` L41, L116, L144
- `JobMatchingController.php` L320, L400
- `AdminController` 多处

**方案**：逐步将内联验证迁移到 `app/Http/Requests/` 目录下的 FormRequest 类，优先处理验证规则复杂或被多处复用的接口

### 3.2 InterviewController 过于臃肿（700+ 行）

**问题**：`InterviewController` 包含 700+ 行代码，承担了面试列表、创建、会话管理、答题提交、心跳、暂停/恢复、报告等多个职责，违反单一职责原则。

**位置**：`app/Http/Controllers/User/InterviewController.php`

**方案**：
- 将会话管理相关逻辑（heartbeat/pause/resume）拆分到 `InterviewSessionController`
- 将报告相关逻辑拆分到 `InterviewReportController`（已有 Trait 但仍耦合在主控制器）
- 将 JD 相关接口（jdSources/jdContent/keywords）拆分到 `InterviewJdController`

### 3.3 JobMatchingController 职责过多（475 行）

**问题**：`JobMatchingController` 同时处理单次分析、批量分析、历史记录、收藏夹等 4 个子功能域。

**位置**：`app/Http/Controllers/User/JobMatchingController.php`

**方案**：
- 拆分 `JobMatchBookmarkController`（bookmarks/storeBookmark/destroyBookmark）
- 拆分 `JobMatchBatchController`（batch/batchProgress/batchSubmit）
- 拆分 `JobMatchHistoryController`（history/showHistory/destroyHistory/batchDestroyHistory）

### 3.4 DashboardService 过于庞大（410 行）

**问题**：`DashboardService` 包含统计查询、营收计算、ATS 分布、7日趋势、系统监控等多个不相关功能，且 `buildDashboardData()` 单方法超过 250 行。

**位置**：`app/Services/Admin/DashboardService.php`

**方案**：
- 拆分 `DashboardStatisticsService`（统计查询）
- 拆分 `DashboardRevenueService`（营收计算）
- 拆分 `DashboardTrendService`（趋势数据）
- 主 Service 通过依赖注入组合各子 Service

### 3.5 缺少 Model::preventLazyLoading 保护

**问题**：项目未在 AppServiceProvider 中启用 `Model::preventLazyLoading()`，N+1 查询问题无法在开发阶段被发现。

**位置**：`app/Providers/AppServiceProvider.php` boot() 方法

**方案**：在非生产环境启用严格模式：
```php
public function boot(): void
{
    Model::preventLazyLoading(!$this->app->isProduction());
    Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());
}
```

---

## 第四批：性能优化（P2 - 中优先级）

### 4.1 VisitTrackingService.recordEvent 未使用缓冲

**问题**：`recordPageview()` 使用了 `$this->buffer` 缓冲机制（满 50 条批量插入），但 `recordEvent()` 直接 `PageVisit::create()` 单条插入，高频事件场景下性能差。

**位置**：`app/Services/VisitTrackingService.php` L114-L140

**方案**：将 `recordEvent()` 也改为缓冲写入，复用 `flushBuffer()` 逻辑

### 4.2 UsageLogger::log 同步写入阻塞 AI 调用

**问题**：`UsageLogger::log()` 在 AI 调用完成后同步写入 `usage_logs` 表，增加了 AI 请求的总延迟。

**位置**：`app/Services/UsageLogger.php`

**方案**：改为 dispatch 异步 Job，或使用 `DB::table('usage_logs')->insert()` + 缓冲批量写入

### 4.3 多处 Admin 控制器搜索未使用 escapeLike

**问题**：以下位置的 LIKE 搜索未对用户输入调用 `escapeLike()`，存在潜在的 LIKE 通配符注入风险（`%`、`_` 未转义）：

**位置**：
- `Admin/InterviewController.php` L47-51（`$keyword` 未转义）
- `Admin/AccessBanController.php` L31（`$search` 未转义）
- `Admin/HelpArticleController.php` L30（`$keyword` 未转义）
- `Admin/JobApplicationController.php` L30-32（`$search` 未转义）
- `Admin/CreditPackController.php` L93-94（`$search` 未转义）
- `Admin/SubscriptionManageController.php` L33-34, L97-98（`$search` 未转义）
- `Public/SiteEventController.php` L26（`$keyword` 未转义）

**方案**：统一使用 `escapeLike()` 函数包裹所有 LIKE 搜索输入

### 4.4 ResumeAtsScoreLog::create 同步写入

**问题**：`ResumeAnalysisController` 中 ATS 评分日志通过 `ResumeAtsScoreLog::create()` 同步写入，增加了评分接口延迟。

**位置**：`app/Http/Controllers/User/ResumeAnalysisController.php` L79

**方案**：改为 dispatch 异步 Job 或延迟到 terminate 阶段写入

### 4.5 QuestionFavoriteController 同步删除

**问题**：`destroy()` 方法先查询再删除（2 次 DB 操作），可直接使用 `where` 条件删除（1 次操作）。

**位置**：`app/Http/Controllers/User/QuestionFavoriteController.php` L36, L87

**方案**：
```php
// 旧：$favorite = InterviewQuestionFavorite::findOrFail($id); $favorite->delete();
// 新：
$deleted = InterviewQuestionFavorite::where('user_id', $user->id)
    ->where('interview_question_id', $questionId)
    ->delete();
```

---

## 第五批：安全优化（P2 - 中优先级）

### 5.1 FeedbackController.store 未对 metadata 做长度限制

**问题**：`store()` 方法将 `User-Agent` 直接存入 `metadata`，但 User-Agent 最长可达数千字符，可能导致 JSON 列膨胀。

**位置**：`app/Http/Controllers/User/FeedbackController.php` L86

**方案**：对 User-Agent 截断：`mb_substr($request->header('User-Agent'), 0, 500)`

### 5.2 ShareController 密码验证未限流

**问题**：简历分享链接密码验证接口无频率限制，可被暴力破解。

**位置**：`app/Http/Controllers/ShareController.php` L42

**方案**：添加 `throttle:5,1` 中间件限制每分钟最多 5 次尝试

### 5.3 Admin 控制器批量删除缺少上限校验

**问题**：`JobMatchingController.batchDestroyHistory()` 接受 `ids` 数组但未限制数量，恶意请求可传入数万个 ID 导致 SQL 过长。

**位置**：`app/Http/Controllers/User/JobMatchingController.php` L262-L272

**方案**：
```php
$ids = $request->validate([
    'ids' => ['required', 'array', 'max:100'],
    'ids.*' => ['integer'],
])['ids'];
```

### 5.4 ProfileController 账号恢复缺少限流

**问题**：`recoverAccount()` 方法虽有手动限流检查，但使用 Cache lock 实现较脆弱，应使用 Laravel 内置 RateLimiter。

**位置**：`app/Http/Controllers/User/ProfileController.php` L415-L445

**方案**：改用 `RateLimiter::attempt()` 替代手动 Cache lock 实现

---

## 第六批：代码规范与可维护性（P3 - 低优先级）

### 6.1 控制器中硬编码分页数量

**问题**：部分控制器硬编码分页数量（如 `paginate(20)`、`paginate(15)`），未使用配置项，与其他地方使用 `config('ui.pagination.*')` 不一致。

**位置**：
- `Api/V1/NotificationController.php` L21：`paginate(20)`
- `User/QuestionFavoriteController.php` L73：`paginate(20)`
- `User/JobMatchingController.php` L393：`paginate(20)`

**方案**：统一使用 `config('ui.pagination.*')` 配置项

### 6.2 Admin 控制器缺少 final 修饰符

**问题**：`Admin/UserController.php`、`Admin/EmailLogController.php` 等多个 Admin 控制器未声明为 `final class`，而 User 端控制器大多已使用 `final`。

**位置**：`app/Http/Controllers/Admin/` 目录下多个文件

**方案**：统一添加 `final` 修饰符，防止不必要的继承

### 6.3 RespondsWithJsonSuccess trait 重复使用

**问题**：`RespondsWithJsonSuccess` trait 被 `InterviewController`、`FeedbackController`、`JobMatchingController` 等多个控制器 use，但 `fail()` 方法签名不统一（有的返回 500，有的返回 422），建议统一错误响应格式。

**位置**：`app/Http/Controllers/User/Traits/RespondsWithJsonSuccess.php`

**方案**：标准化 `fail()` 方法，增加错误码枚举，确保 HTTP 状态码与业务错误码一致

### 6.4 控制器方法缺少返回类型声明

**问题**：部分控制器方法缺少返回类型声明，如 `QuestionFavoriteController::index()` 无返回类型。

**位置**：`app/Http/Controllers/User/QuestionFavoriteController.php` L67

**方案**：补充 `: View|JsonResponse` 等返回类型声明

### 6.5 缺少 PHPStan / Larastan 静态分析配置

**问题**：项目未配置静态分析工具，无法在 CI 阶段捕获类型错误、未使用变量、空值引用等问题。

**方案**：添加 `phpstan.neon` 配置，设置 `level: 5` 起步，逐步提升

---

## 第七批：前端与资源优化（P3 - 低优先级）

### 7.1 View Composer 全局注入过多变量

**问题**：`View::composer('*', ...)` 向所有视图注入 25+ 个变量，包括 admin 视图不需要的用户端配置，增加了每个视图的内存开销。

**位置**：`app/Providers/AppServiceProvider.php` L131-L180

**方案**：
- 拆分为多个 View Composer，按视图分组注入
- 用户端视图注入 `siteName/seoDescription/themeColor` 等
- Admin 视图仅注入 `siteName/adminSiteName`
- 分析代码注入 `analyticsGoogle/analyticsBaidu/analyticsClarity`

### 7.2 公告缓存未区分用户可见性

**问题**：`DashboardController` 中公告查询使用全局缓存 key `user:dashboard:announcements`，所有用户共享同一份缓存，但未来如果公告需要按用户角色/套餐过滤则会失效。

**位置**：`app/Http/Controllers/User/DashboardController.php` L81-L88

**方案**：预留缓存 key 分组能力，如 `user:dashboard:announcements:all` 或按用户类型分组

---

## 实施建议

| 批次 | 优先级 | 预估影响 | 建议时间窗口 |
|------|--------|---------|------------|
| 第一批 | P0 | 减少 15+ 次/页 DB 查询 | 本周 |
| 第二批 | P1 | 降低 Redis/Cache 压力 | 本周-下周 |
| 第三批 | P1 | 提升代码可维护性 | 2 周内 |
| 第四批 | P2 | 降低接口延迟 20-50ms | 2-3 周 |
| 第五批 | P2 | 消除安全风险 | 2-3 周 |
| 第六批 | P3 | 代码规范统一 | 持续迭代 |
| 第七批 | P3 | 内存/渲染优化 | 持续迭代 |
