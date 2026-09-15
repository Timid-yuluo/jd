# 职路通 - 新功能模块开发任务清单

> 关联文档：[features-development-plan.md](../features-development-plan.md)  
> 路线图：[roadmap.md](./roadmap.md)  
> 创建时间：2026-06-24  
> 完成时间：2026-06-25  
> 状态：**全部完成 ✅**

---

## 任务状态说明

- `[ ]` 待开始
- `[~]` 进行中
- `[x]` 已完成
- `[!]` 阻塞

优先级：**P0**（阻塞性）/ **P1**（高）/ **P2**（中）/ **P3**（低）

---

## Phase 1：公司情报体系（P1）

### 1.1 数据层

- [x]**T1.1** [P0] 创建 Migration：`company_reviews` + `company_blacklists` 表
  - 文件：`database/migrations/2026_06_24_210000_create_company_intelligence_tables.php`
  - 参考：features-development-plan.md §6.3
  - 包含：评分维度、文字评价、审核状态、面试信息、黑名单去重唯一键

- [x]**T1.2** [P0] 创建 Model：`CompanyReview`
  - 文件：`app/Models/CompanyReview.php`
  - 关联：belongsTo CompanyProfile, belongsTo User
  - Cast：rating 字段为 integer，status 为 enum
  - Scope：`approved()` 已审核通过

- [x]**T1.3** [P0] 创建 Model：`CompanyBlacklist`
  - 文件：`app/Models/CompanyBlacklist.php`
  - 关联：belongsTo CompanyProfile, belongsTo User
  - 唯一约束：user_id + company_profile_id

- [x]**T1.4** [P1] 完善 Model：`CompanyProfile`
  - 文件：`app/Models/CompanyProfile.php`（已存在，需补充关联）
  - 新增：hasMany CompanyReview, hasMany CompanyBlacklist
  - 新增 Scope：`withApprovedReviews()`
  - 新增访问器：`average_rating`, `review_count`

### 1.2 业务层

- [x]**T1.5** [P1] 创建 Controller：`User\CompanyController`
  - 文件：`app/Http/Controllers/User/CompanyController.php`
  - 方法：`index()` 列表、`search()` AJAX搜索、`show()` 详情
  - 详情页聚合：评分统计、点评列表、面试难度、黑名单预警

- [x]**T1.6** [P1] 创建 Controller：`User\CompanyReviewController`
  - 文件：`app/Http/Controllers/User/CompanyReviewController.php`
  - 方法：`index()` 点评列表、`store()` 提交点评、`like()` 点赞、`rateInterview()` 面试评级
  - store 需审核流程（写入 pending 状态）

- [x]**T1.7** [P1] 创建 Controller：`User\CompanyBlacklistController`
  - 文件：`app/Http/Controllers/User/CompanyBlacklistController.php`
  - 方法：`toggle()` 标记/取消、`index()` 我的避坑列表
  - 防滥用：同一公司同一用户只能标记一次

- [x]**T1.8** [P1] 创建 FormRequest：`StoreCompanyReviewRequest`
  - 文件：`app/Http/Requests/User/StoreCompanyReviewRequest.php`
  - 验证：5 维评分 1-5、pros/cons/advice 必填、面试信息可选

- [x]**T1.9** [P2] 创建 FormRequest：`ToggleBlacklistRequest`
  - 文件：`app/Http/Requests/User/ToggleBlacklistRequest.php`
  - 验证：reason 枚举、detail 可选

### 1.3 表现层

- [x]**T1.10** [P1] 创建 View：公司列表页
  - 文件：`resources/views/user/companies/index.blade.php`
  - 内容：搜索框 + 行业/规模/阶段筛选 + 公司卡片网格

- [x]**T1.11** [P1] 创建 View：公司详情页
  - 文件：`resources/views/user/companies/show.blade.php`
  - 布局参考：features-development-plan.md §6.7
  - 包含：基本信息、评分汇总、薪资分布、点评列表、黑名单警告横幅

- [x]**T1.12** [P2] 创建 View：点评相关
  - 文件：
    - `resources/views/user/companies/reviews/list.blade.php`
    - `resources/views/user/companies/reviews/create-modal.blade.php`

- [x]**T1.13** [P2] 创建 View：黑名单列表
  - 文件：`resources/views/user/companies/blacklists/index.blade.php`

- [x]**T1.14** [P2] 创建 View：partials 组件
  - 文件：
    - `resources/views/user/companies/partials/company-card.blade.php`
    - `resources/views/user/companies/partials/rating-stars.blade.php`
    - `resources/views/user/companies/partials/review-item.blade.php`
    - `resources/views/user/companies/partials/salary-range-chart.blade.php`
    - `resources/views/user/companies/partials/difficulty-meter.blade.php`
    - `resources/views/user/companies/partials/blacklist-warning.blade.php`

### 1.4 路由与导航

- [x]**T1.15** [P0] 添加路由：公司情报模块
  - 文件：`routes/web_user.php`
  - 路由组：`companies.*`（参考 §6.4）

- [x]**T1.16** [P0] 更新导航菜单
  - 文件：`resources/views/layouts/user.blade.php`
  - 新增：公司情报导航项（参考 §7.1）

---

## Phase 2：薪资谈判助手（P2）

### 2.1 业务层

- [x]**T2.1** [P1] 创建 Controller：`User\SalaryController`
  - 文件：`app/Http/Controllers/User/SalaryController.php`
  - 方法：`index()` 查询首页、`chartData()` 图表JSON、`report()` 上报、`negotiate()` 谈判输入、`startNegotiation()` 调用AI、`negotiationResult()` 结果、`negotiationHistory()` 历史
  - 参考：§2.5

- [x]**T2.2** [P1] 创建 FormRequest：薪资相关
  - 文件：
    - `app/Http/Requests/User/SalaryReportRequest.php`（上报薪资）
    - `app/Http/Requests/User/SalaryNegotiationRequest.php`（谈判参数）
  - 验证：job_title 必填、薪资为正整数、experience_years 枚举

- [x]**T2.3** [P2] 创建 Service：`SalaryNegotiationService`
  - 文件：`app/Services/SalaryNegotiationService.php`
  - 职责：构造 AI Prompt、调用 AI 服务、解析返回结果、写入 session
  - 复用现有 AI 服务（智谱 GLM-4-Flash / DeepSeek V4）

### 2.2 表现层

- [x]**T2.4** [P1] 创建 View：薪资查询首页
  - 文件：`resources/views/user/salary/index.blade.php`
  - 内容：搜索表单 + 热门查询 + 薪资区间展示

- [x]**T2.5** [P1] 创建 View：谈判输入页
  - 文件：`resources/views/user/salary/negotiate.blade.php`

- [x]**T2.6** [P1] 创建 View：谈判结果页
  - 文件：`resources/views/user/salary/negotiate-result.blade.php`
  - 展示：策略卡片、对话脚本、薪资对比

- [x]**T2.7** [P2] 创建 View：历史记录
  - 文件：`resources/views/user/salary/negotiate-history.blade.php`

- [x]**T2.8** [P2] 创建 View：partials 组件
  - 文件：
    - `resources/views/user/salary/partials/salary-chart.blade.php`
    - `resources/views/user/salary/partials/strategy-card.blade.php`
    - `resources/views/user/salary/partials/dialogue-card.blade.php`
    - `resources/views/user/salary/report-modal.blade.php`

### 2.3 路由与配置

- [x]**T2.9** [P0] 添加路由：薪资模块
  - 文件：`routes/web_user.php`（参考 §2.4）

- [x]**T2.10** [P1] 注册 AI Prompt：`salary_negotiation`
  - 通过 Seeder 或后台写入 `ai_prompts` 表
  - Prompt 模板参考：§2.7

---

## Phase 3：AI 职业测评（P2）

### 3.1 题库配置

- [x]**T3.1** [P0] 创建题库配置：MBTI
  - 文件：`config/assessments/mbti.php`（28题精简版）
  - 结构：题目 + 选项 + 维度映射（E/I、S/N、T/F、J/P）

- [x]**T3.2** [P0] 创建题库配置：霍兰德 RIASEC
  - 文件：`config/assessments/holland.php`（30题版）
  - 结构：题目 + 选项 + 维度映射（R/I/A/S/E/C）

- [x]**T3.3** [P0] 创建题库配置：DISC
  - 文件：`config/assessments/disc.php`（24题）
  - 结构：题目 + 选项 + 维度映射（D/I/S/C）

### 3.2 业务层

- [x]**T3.4** [P1] 创建 Service：`AssessmentService`
  - 文件：`app/Services/AssessmentService.php`
  - 职责：加载题库、计算维度分数、生成结果代码、调用 AI 解读
  - 支持：MBTI 16型、霍兰德 3字母代码、DISC 4型

- [x]**T3.5** [P1] 创建 Controller：`User\AssessmentController`
  - 文件：`app/Http/Controllers/User/AssessmentController.php`
  - 方法：`index()` 首页、`start()` 开始、`questions()` 题目、`submit()` 提交、`result()` 报告、`history()` 历史
  - 参考：§3.5

- [x]**T3.6** [P1] 创建 FormRequest：`AssessmentSubmitRequest`
  - 文件：`app/Http/Requests/User/AssessmentSubmitRequest.php`
  - 验证：answers 数组、每题 option 必填

### 3.3 表现层

- [x]**T3.7** [P1] 创建 View：测评中心首页
  - 文件：`resources/views/user/assessments/index.blade.php`
  - 内容：三种测评入口卡片

- [x]**T3.8** [P1] 创建 View：答题页
  - 文件：`resources/views/user/assessments/test.blade.php`
  - 支持：断点续答、进度条、动态加载题库

- [x]**T3.9** [P1] 创建 View：结果报告页
  - 文件：`resources/views/user/assessments/result.blade.php`

- [x]**T3.10** [P2] 创建 View：历史记录
  - 文件：`resources/views/user/assessments/history.blade.php`

- [x]**T3.11** [P2] 创建 View：partials 图表组件
  - 文件：
    - `resources/views/user/assessments/partials/mbti-chart.blade.php`
    - `resources/views/user/assessments/partials/holland-radar.blade.php`
    - `resources/views/user/assessments/partials/disc-wheel.blade.php`
    - `resources/views/user/assessments/partials/career-match.blade.php`

### 3.4 路由与配置

- [x]**T3.12** [P0] 添加路由：测评模块
  - 文件：`routes/web_user.php`（参考 §3.4）

- [x]**T3.13** [P1] 注册 AI Prompt：三种测评解读
  - `career_assessment_mbti`
  - `career_assessment_holland`
  - `career_assessment_disc`

---

## Phase 4：智能岗位推荐引擎（P1）

### 4.1 异步任务

- [x]**T4.1** [P0] 创建 Queue Job：`GenerateJobRecommendations`
  - 文件：`app/Jobs/GenerateJobRecommendations.php`
  - 职责：获取简历、查询未推荐岗位、AI 匹配分析、写入推荐表、高分推送通知
  - 参考：§5.6

### 4.2 业务层

- [x]**T4.2** [P1] 创建 Service：`JobRecommendationService`
  - 文件：`app/Services/JobRecommendationService.php`
  - 职责：匹配算法（技能40%+经验25%+学历15%+地点10%+薪资10%）、生成匹配原因、技能差距分析

- [x]**T4.3** [P1] 创建 Controller：`User\JobRecommendationController`
  - 文件：`app/Http/Controllers/User/JobRecommendationController.php`
  - 方法：`index()` 列表、`generate()` 触发、`progress()` 轮询、`markApplied()`、`dismiss()`、`batchDismiss()`
  - 参考：§5.5

- [x]**T4.4** [P2] 创建 FormRequest：推荐相关
  - 文件：`app/Http/Requests/User/JobRecommendationGenerateRequest.php`

### 4.3 表现层

- [x]**T4.5** [P1] 创建 View：推荐列表页
  - 文件：`resources/views/user/job-recommendations/index.blade.php`
  - 内容：岗位卡片 + 匹配分 + 匹配原因标签 + 技能差距提示

- [x]**T4.6** [P2] 创建 View：生成进度页
  - 文件：`resources/views/user/job-recommendations/generate.blade.php`

- [x]**T4.7** [P2] 创建 View：partials 组件
  - 文件：
    - `resources/views/user/job-recommendations/partials/job-card.blade.php`
    - `resources/views/user/job-recommendations/partials/match-badge.blade.php`
    - `resources/views/user/job-recommendations/partials/skill-gap-list.blade.php`

### 4.4 路由与配置

- [x]**T4.8** [P0] 添加路由：推荐模块
  - 文件：`routes/web_user.php`（参考 §5.4）

- [x]**T4.9** [P1] 注册 AI Prompt：`job_recommendation_match`

- [x]**T4.10** [P2] 创建 Console Command：每日推荐任务
  - 文件：`app/Console/Commands/GenerateDailyRecommendations.php`
  - 调度：每日凌晨为新岗位生成推荐

---

## Phase 5：技能评估与学习路径（P3）

### 5.1 数据层

- [x]**T5.1** [P0] 创建 Migration：`skill_assessments` + `skill_learning_paths` 表
  - 文件：`database/migrations/2026_06_24_220000_create_skill_assessment_tables.php`
  - 参考：§4.3

- [x]**T5.2** [P0] 创建 Model：`SkillAssessment`
  - 文件：`app/Models/SkillAssessment.php`
  - 关联：belongsTo User

- [x]**T5.3** [P0] 创建 Model：`SkillLearningPath`
  - 文件：`app/Models/SkillLearningPath.php`
  - 关联：belongsTo User

### 5.2 业务层

- [x]**T5.4** [P1] 创建 Service：`SkillAnalysisService`
  - 文件：`app/Services/SkillAnalysisService.php`
  - 职责：技能差距分析、学习路径生成、调用 AI

- [x]**T5.5** [P1] 创建 Controller：`User\SkillAssessmentController`
  - 文件：`app/Http/Controllers/User/SkillAssessmentController.php`
  - 方法：`index()` 清单、`store()` 新增、`update()` 修改、`destroy()` 删除、`radar()` 雷达图

- [x]**T5.6** [P1] 创建 Controller：`User\LearningPathController`
  - 文件：`app/Http/Controllers/User/LearningPathController.php`
  - 方法：`index()` 入口、`analyze()` 差距分析、`show()` 路径详情、`history()` 历史

- [x]**T5.7** [P2] 创建 FormRequest：技能相关
  - 文件：
    - `app/Http/Requests/User/SkillAssessmentRequest.php`
    - `app/Http/Requests/User/LearningPathAnalyzeRequest.php`

### 5.3 表现层

- [x]**T5.8** [P1] 创建 View：技能清单页
  - 文件：`resources/views/user/skills/index.blade.php`

- [x]**T5.9** [P1] 创建 View：雷达图页
  - 文件：`resources/views/user/skills/radar.blade.php`

- [x]**T5.10** [P2] 创建 View：学习路径相关
  - 文件：
    - `resources/views/user/skills/learn/index.blade.php`
    - `resources/views/user/skills/learn/analyze.blade.php`
    - `resources/views/user/skills/learn/path.blade.php`
    - `resources/views/user/skills/learn/history.blade.php`

- [x]**T5.11** [P2] 创建 View：partials 组件
  - 文件：
    - `resources/views/user/skills/partials/skill-item.blade.php`
    - `resources/views/user/skills/partials/gap-chart.blade.php`
    - `resources/views/user/skills/partials/path-timeline.blade.php`

### 5.4 路由与配置

- [x]**T5.12** [P0] 添加路由：技能模块
  - 文件：`routes/web_user.php`（参考 §4.4）

- [x]**T5.13** [P1] 注册 AI Prompt
  - `skill_gap_analysis`
  - `learning_path_generate`

---

## Phase 6：Admin 管理后台扩展（P2）

### 6.1 业务层

- [x]**T6.1** [P1] 创建 Admin Controller：`Admin\CompanyController`
  - 文件：`app/Http/Controllers/Admin/CompanyController.php`
  - 方法：resource CRUD + `uploadLogo()` + `import()`

- [x]**T6.2** [P1] 创建 Admin Controller：`Admin\CompanyReviewController`
  - 文件：`app/Http/Controllers/Admin/CompanyReviewController.php`
  - 方法：`pending()` 待审核、`approve()`、`reject()`

- [x]**T6.3** [P2] 创建 Admin Controller：`Admin\CompanyBlacklistController`
  - 文件：`app/Http/Controllers/Admin/CompanyBlacklistController.php`
  - 方法：resource CRUD

### 6.2 表现层

- [x]**T6.4** [P2] 创建 Admin View：公司管理
  - 文件：`resources/views/admin/companies/` 下的 index/create/edit

- [x]**T6.5** [P2] 创建 Admin View：点评审核
  - 文件：`resources/views/admin/company-reviews/` 下的 pending 等

- [x]**T6.6** [P3] 创建 Admin View：黑名单管理
  - 文件：`resources/views/admin/company-blacklists/` 下的 index

### 6.3 路由

- [x]**T6.7** [P0] 添加 Admin 路由
  - 文件：`routes/web_admin.php`（参考 §7.6）

---

## Phase 7：公共模块与集成（P1）

### 7.1 公共配置

- [x]**T7.1** [P1] 配额系统扩展
  - 文件：`config/quota.php` 或 `QuotaService`
  - 新增配额类型：`salary_negotiate`、`assessment_report`、`skill_analyze`、`job_recommend`
  - 参考：§7.2

- [x]**T7.2** [P1] 权限扩展 Seeder
  - 文件：`database/seeders/PermissionSeeder.php`（或新增）
  - 新增权限：`view companies`、`create company reviews`、`manage company profiles`、`view blacklist`
  - 参考：§7.3

- [x]**T7.3** [P2] 数据 Seeder：公司数据 + 题库种子
  - 文件：`database/seeders/CompanyProfileSeeder.php`

### 7.2 集成测试

- [x]**T7.4** [P1] 集成测试与联调
  - 验证所有路由可访问
  - 验证 AI Prompt 调用链路
  - 验证配额扣减
  - 验证权限控制
  - 验证 Queue Job 执行

---

## 任务统计

| Phase | 任务数 | P0 | P1 | P2 | P3 | 状态 |
|-------|--------|----|----|----|----|------|
| Phase 1 | 16 | 5 | 7 | 4 | 0 | ✅ 完成 |
| Phase 2 | 10 | 1 | 4 | 5 | 0 | ✅ 完成 |
| Phase 3 | 13 | 4 | 5 | 4 | 0 | ✅ 完成 |
| Phase 4 | 10 | 2 | 4 | 4 | 0 | ✅ 完成 |
| Phase 5 | 13 | 4 | 5 | 4 | 0 | ✅ 完成 |
| Phase 6 | 7 | 1 | 2 | 3 | 1 | ✅ 完成 |
| Phase 7 | 4 | 0 | 3 | 1 | 0 | ✅ 完成 |
| **合计** | **73** | **17** | **30** | **25** | **1** | **✅ 全部完成** |

---

## 开发顺序建议

```
1. Phase 1 (公司情报 P1) ──→ Phase 4 (岗位推荐 P1)
2. Phase 2 (薪资谈判 P2) ──→ Phase 3 (职业测评 P2)
3. Phase 5 (技能评估 P3)
4. Phase 6 (Admin 后台 P2)
5. Phase 7 (集成测试 P1)
```

## 部署步骤

```bash
# 1. 执行数据库迁移
php artisan migrate

# 2. 填充 AI Prompt（含 6 个新功能 Prompt）
php artisan db:seed --class=AiPromptSeeder

# 3. 填充公司情报示例数据
php artisan db:seed --class=CompanyIntelligenceSeeder

# 4. 刷新权限缓存
php artisan permission:cache-reset

# 5. 清理视图缓存
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

> 文档结束。全部 73 项任务已完成，可进入部署阶段。
