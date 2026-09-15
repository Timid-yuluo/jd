# 职路通 - 新功能模块开发文档

> 文档版本：v1.0  
> 创建时间：2026-06-24  
> 项目状态：Model + Migration 已完成，Controller / Route / View 待开发

---

## 目录

1. [总体概览](#1-总体概览)
2. [功能一：AI 薪资谈判助手](#2-功能一ai-薪资谈判助手)
3. [功能二：AI 职业测评](#3-功能二ai-职业测评)
4. [功能三：技能评估与学习路径](#4-功能三技能评估与学习路径)
5. [功能四：智能岗位推荐引擎](#5-功能四智能岗位推荐引擎)
6. [功能五：公司情报体系](#6-功能五公司情报体系)
7. [公共模块](#7-公共模块)
8. [实施计划](#8-实施计划)

---

## 1. 总体概览

### 1.1 现有基础设施状态

| 组件 | 状态 |
|------|------|
| Migration: `2026_06_24_200000_create_new_features_tables.php` | ✅ 已创建 |
| Models: `CareerAssessment`, `SalaryNegotiationSession`, `CareerPlan`, `JobRecommendation`, `CompanyProfile` 等 | ✅ 已创建 |
| Routes: `routes/web_user.php` | ❌ 需新增路由 |
| Controllers: `app/Http/Controllers/User/` | ❌ 需创建 |
| Views: `resources/views/user/` | ❌ 目录已建但为空 |
| Sidebar: `resources/views/layouts/user.blade.php` | ❌ 需新增导航项 |

### 1.2 需新增的数据库表

| 表名 | 是否需要新建 Migration | 说明 |
|------|----------------------|------|
| `company_reviews` | **是** | 公司点评/面试评价 |
| `interview_difficulty_ratings` | **是** | 面试难度评级 |
| `company_blacklists` | **是** | 黑名单预警 |
| `skill_assessments` | **是** | 技能自评 |
| `skill_learning_paths` | **是** | AI 学习路径 |
| `user_skill_gaps` | **是** | 用户技能差距分析 |

### 1.3 技术栈约定

- **框架**：Laravel 13.x + PHP 8.3
- **前端**：Blade + Tailwind CSS + Tabler UI
- **AI 接口**：复用现有 AI 服务（智谱 GLM-4-Flash / DeepSeek V4）
- **配额控制**：复用 Middleware `quota` + `QuotaService`
- **RBAC**：spatie/laravel-permission
- **异步任务**：Laravel Queue (Redis)

---

## 2. 功能一：AI 薪资谈判助手

### 2.1 功能概述

帮助用户了解行业薪资水平，并提供 AI 驱动的薪资谈判策略和模拟对话。

### 2.2 功能详情

#### 2.2.1 薪资查询
- **数据来源**：`salary_surveys` 表，支持用户自行上报 + 管理员导入
- **查询维度**：岗位名称 → 城市 → 经验年限 → 行业
- **展示内容**：薪资区间（P25/P50/P75）、样本数量、更新时间
- **图表**：柱状图展示不同维度的薪资对比

#### 2.2.2 AI 谈判助手
- **输入**：岗位名称、公司、当前薪资、期望薪资、城市、经验年限、其他上下文（如已有 Offer 详情）
- **AI 分析**：
  - 薪资合理区间评估
  - 谈判空间分析
  - 谈判话术生成
  - 风险点提示
- **输出**：
  - 谈判策略卡片（策略名称 + 执行步骤）
  - 模拟对话脚本（HR 话术 → 你的应对）
  - 薪资对比雷达图
- **历史记录**：保存历史会话，支持回顾

### 2.3 数据模型（已有）

```php
// app/Models/SalaryNegotiationSession.php ✅ 已存在
// 表: salary_negotiation_sessions
- user_id           FK → users
- job_title         岗位名称
- company           公司名称
- current_salary    当前薪资
- target_salary     期望薪资
- city              城市
- experience_years  经验年限
- context           JSON 上下文信息
- ai_strategy       JSON AI 策略（策略名称、步骤、建议）
- ai_dialogue       JSON AI 对话脚本（HR→你 话术对）
```

```php
// app/Models/SalarySurvey.php ✅ 已存在
// 表: salary_surveys
- user_id           FK → users (nullable)
- job_title         岗位名称
- company           公司
- city              城市
- industry          行业
- salary_min        薪资下限
- salary_max        薪资上限
- currency          币种
- experience_level  经验级别
- source            来源
- source_hash       去重 hash
- reported_at       数据日期
```

### 2.4 路由设计

```php
// routes/web_user.php 新增

// 薪资查询
Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
Route::get('/salary/chart', [SalaryController::class, 'chartData'])->name('salary.chart');
Route::post('/salary/report', [SalaryController::class, 'report'])->name('salary.report');

// 薪资谈判
Route::get('/salary/negotiate', [SalaryController::class, 'negotiate'])->name('salary.negotiate');
Route::post('/salary/negotiate', [SalaryController::class, 'startNegotiation'])
    ->middleware(['throttle:salary-analyze', 'quota'])
    ->name('salary.negotiate.start');
Route::get('/salary/negotiate/{session}', [SalaryController::class, 'negotiationResult'])
    ->name('salary.negotiate.result');
Route::get('/salary/negotiate/history', [SalaryController::class, 'negotiationHistory'])
    ->name('salary.negotiate.history');
```

### 2.5 Controller

| 方法 | 说明 |
|------|------|
| `index()` | 薪资查询首页，展示搜索表单 + 热门查询 |
| `chartData()` | 返回 JSON 图表数据 |
| `report()` | 用户提交薪资数据 |
| `negotiate()` | 谈判助手输入表单页 |
| `startNegotiation()` | 提交谈判参数，调用 AI，存储结果 |
| `negotiationResult()` | 查看某次谈判结果详情 |
| `negotiationHistory()` | 谈判历史列表 |

### 2.6 View 文件

```
resources/views/user/salary/
├── index.blade.php              # 薪资查询首页
├── negotiate.blade.php          # 谈判输入页
├── negotiate-result.blade.php   # 谈判结果页
├── negotiate-history.blade.php  # 历史记录
├── partials/
│   ├── salary-chart.blade.php   # 薪资图表组件
│   ├── strategy-card.blade.php  # 策略卡片组件
│   └── dialogue-card.blade.php  # 对话卡片组件
└── report-modal.blade.php       # 薪资上报弹窗
```

### 2.7 AI Prompt 设计

```
系统 Prompt:
"你是资深HR薪酬顾问，拥有10年+大厂谈薪经验。
请基于以下信息，给出薪资谈判建议：

岗位：{job_title}
公司：{company}
城市：{city}
经验：{experience_years}年
当前薪资：{current_salary}
期望薪资：{target_salary}
补充信息：{context}

请结构化输出：
1. 薪资合理性评估（合理/偏高/偏低，及原因）
2. 谈判建议策略（至少2种策略，包含话术）
3. 风险提示
4. 模拟对话示例（HR提问 → 求职者回答）
"
```

---

## 3. 功能二：AI 职业测评

### 3.1 功能概述

提供 MBTI、霍兰德（RIASEC）、DISC 三种经典职业性格测评，AI 生成个性化解读和职业推荐。

### 3.2 功能详情

#### 3.2.1 测评类型

| 测评 | 题数 | 时长 | 结果类型 |
|------|------|------|----------|
| MBTI | 93题（可简化至28题） | 10-15分钟 | 16型人格 |
| 霍兰德 RIASEC | 60题（可简化至30题） | 5-10分钟 | 6维雷达图 + 3字母代码 |
| DISC | 24题 | 3-5分钟 | 4型行为风格 |

#### 3.2.2 测评流程

```
选择测评类型 → 逐题作答（支持断点续答）→ 提交评分 → AI 解读 → 结果报告
```

#### 3.2.3 AI 解读内容
- 人格/风格特征描述
- 优势与劣势分析
- 适合的岗位方向推荐
- 团队协作建议
- 职业发展路径建议
- 与目标岗位的匹配度分析（结合简历数据）

### 3.3 数据模型（已有）

```php
// app/Models/CareerAssessment.php ✅ 已存在
// 表: career_assessments
- user_id              FK → users
- test_type            'mbti' | 'holland' | 'disc'
- answers              JSON [{question_id, option, timestamp}]
- result_code          结果代码 (e.g. 'INTJ', 'RIA', 'D')
- result_label         结果标签
- dimensions           JSON 各维度分数
- ai_analysis          JSON AI 解读
- recommended_careers  JSON 推荐职业
- team_roles           JSON 团队角色
```

### 3.4 路由设计

```php
// routes/web_user.php 新增

Route::prefix('assessments')->name('assessments.')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('index');
    Route::get('/{type}/start', [AssessmentController::class, 'start'])->name('start');
    Route::get('/{type}/questions', [AssessmentController::class, 'questions'])->name('questions');
    Route::post('/{type}/submit', [AssessmentController::class, 'submit'])
        ->middleware(['throttle:assessment-submit', 'quota'])
        ->name('submit');
    Route::get('/result/{assessment}', [AssessmentController::class, 'result'])->name('result');
    Route::get('/history', [AssessmentController::class, 'history'])->name('history');
});
```

### 3.5 Controller

| 方法 | 说明 |
|------|------|
| `index()` | 测评中心首页（三种测评入口卡片） |
| `start($type)` | 开始测评（选择语言/版本） |
| `questions($type)` | 返回测评题目（JSON 分页） |
| `submit($type)` | 提交答案，计算结果，调用 AI |
| `result(assessment)` | 查看测评报告 |
| `history()` | 历史测评记录 |

### 3.6 View 文件

```
resources/views/user/assessments/
├── index.blade.php           # 测评中心首页
├── test.blade.php            # 答题页（统一样式，动态加载题库）
├── result.blade.php          # 结果报告页
├── history.blade.php         # 历史记录
└── partials/
    ├── mbti-chart.blade.php   # MBTI 类型图表
    ├── holland-radar.blade.php# 霍兰德雷达图
    ├── disc-wheel.blade.php   # DISC 圆盘图
    └── career-match.blade.php # 职业匹配卡片
```

### 3.7 题库数据

题库以 PHP 配置文件存储，避免数据库查询开销：

```
config/assessments/
├── mbti.php      # MBTI 28题精简版
├── holland.php   # 霍兰德 30题版
└── disc.php      # DISC 24题
```

---

## 4. 功能三：技能评估与学习路径

### 4.1 功能概述

用户评估自身技能水平，AI 对比目标岗位要求生成技能差距分析，并推荐个性化学习路径。

### 4.2 功能详情

#### 4.2.1 技能自评
- **技能分类**：硬技能（编程语言/工具）+ 软技能（沟通/领导力/时间管理）
- **自评等级**：入门 / 基础 / 熟练 / 精通 / 专家
- **关联信息**：使用年限、最近使用时间、证书/项目证明

#### 4.2.2 技能差距分析
- 选择目标岗位 → AI 抓取该岗位常见技能要求
- 对比用户当前技能 → 生成差距雷达图
- 标注：必须补充 / 建议补充 / 加分项

#### 4.2.3 AI 学习路径
- 基于差距分析，生成阶段性学习计划
- 推荐学习资源（书籍/课程/项目实践）
- 时间预估 + 里程碑

### 4.3 数据模型（需新建）

```sql
-- skill_assessments
CREATE TABLE skill_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    skill_name VARCHAR(80) NOT NULL,
    skill_category VARCHAR(40) NOT NULL DEFAULT 'hard',  -- hard / soft
    proficiency TINYINT UNSIGNED NOT NULL DEFAULT 1,       -- 1-5
    years_used TINYINT UNSIGNED DEFAULT 0,
    last_used_at DATE NULL,
    evidence TEXT NULL,                                    -- 证书/项目
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_skill (user_id, skill_category)
);

-- skill_learning_paths  
CREATE TABLE skill_learning_paths (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    target_job VARCHAR(120) NOT NULL,
    current_snapshot JSON NULL,        -- 当前技能快照
    required_skills JSON NULL,         -- AI 分析的目标岗位要求技能
    gap_analysis JSON NULL,            -- 差距分析 [{skill, gap_level, importance}]
    ai_path JSON NULL,                 -- AI 学习路径 [{phase, topic, resources, duration}]
    total_duration_weeks INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_path (user_id, status)
);
```

### 4.4 路由设计

```php
// routes/web_user.php 新增

// 技能评估
Route::get('/skills', [SkillAssessmentController::class, 'index'])->name('skills.index');
Route::post('/skills', [SkillAssessmentController::class, 'store'])->name('skills.store');
Route::put('/skills/{skill}', [SkillAssessmentController::class, 'update'])->name('skills.update');
Route::delete('/skills/{skill}', [SkillAssessmentController::class, 'destroy'])->name('skills.destroy');
Route::get('/skills/radar', [SkillAssessmentController::class, 'radar'])->name('skills.radar');

// 学习路径
Route::get('/skills/learn', [LearningPathController::class, 'index'])->name('skills.learn');
Route::post('/skills/learn/analyze', [LearningPathController::class, 'analyze'])
    ->middleware(['throttle:skill-analyze', 'quota'])
    ->name('skills.learn.analyze');
Route::get('/skills/learn/{path}', [LearningPathController::class, 'show'])->name('skills.learn.show');
Route::get('/skills/learn/history', [LearningPathController::class, 'history'])->name('skills.learn.history');
```

### 4.5 View 文件

```
resources/views/user/skills/
├── index.blade.php            # 技能清单 + 自评表单
├── radar.blade.php            # 技能雷达图页
├── learn/
│   ├── index.blade.php        # 学习路径入口
│   ├── analyze.blade.php      # 差距分析结果
│   ├── path.blade.php         # 学习路径详情
│   └── history.blade.php      # 历史路径
└── partials/
    ├── skill-item.blade.php   # 技能条目组件
    ├── gap-chart.blade.php    # 差距图表
    └── path-timeline.blade.php# 学习时间线
```

---

## 5. 功能四：智能岗位推荐引擎

### 5.1 功能概述

基于用户简历 + 行为数据 + 偏好设置，从 `external_recruitments` 中智能匹配并推荐岗位。

### 5.2 功能详情

#### 5.2.1 推荐算法（简化版）
```
匹配分 = 技能匹配(40%) + 经验匹配(25%) + 学历匹配(15%) + 地点匹配(10%) + 薪资匹配(10%)
```
- **技能匹配**：简历技能词 vs JD 要求词汇，向量相似度
- **经验匹配**：简历经验年限 vs JD 要求年限
- **学历匹配**：简历学历 vs JD 要求学历
- **地点匹配**：期望城市 vs 岗位所在地
- **薪资匹配**：期望薪资 vs 岗位薪资范围

#### 5.2.2 推荐列表
- 展示岗位标题、公司、匹配分、匹配原因（标签）
- 技能差距提示（缺少什么技能）
- 操作：查看详情 / 标记感兴趣 / 忽略

#### 5.2.3 推荐方式
- **手动触发**：用户点击"智能推荐"，从 external_recruitments 批量分析
- **定时任务**：每日凌晨为新岗位生成推荐（通过 Queue Job）
- **新岗位通知**：有新匹配的岗位时推送通知

### 5.3 数据模型（已有）

```php
// app/Models/JobRecommendation.php ✅ 已存在
// 表: job_recommendations
- user_id                  FK → users
- resume_id                FK → resumes (nullable)
- external_recruitment_id  FK → external_recruitments (nullable)
- job_title                岗位
- company                  公司
- city                     城市
- match_score              匹配分数 (0-100)
- match_reasons            JSON 匹配原因
- skill_gaps               JSON 技能差距
- status                   'new' | 'viewed' | 'applied' | 'dismissed'
- dismissed_at             忽略时间
```

### 5.4 路由设计

```php
// routes/web_user.php 新增

Route::prefix('recommendations')->name('recommendations.')->group(function () {
    Route::get('/', [JobRecommendationController::class, 'index'])->name('index');
    Route::post('/generate', [JobRecommendationController::class, 'generate'])
        ->middleware(['throttle:recommend-generate', 'quota'])
        ->name('generate');
    Route::get('/progress', [JobRecommendationController::class, 'progress'])
        ->middleware('throttle:30,1')
        ->name('progress');
    Route::post('/{recommendation}/apply', [JobRecommendationController::class, 'markApplied'])
        ->name('apply');
    Route::post('/{recommendation}/dismiss', [JobRecommendationController::class, 'dismiss'])
        ->name('dismiss');
    Route::post('/batch-dismiss', [JobRecommendationController::class, 'batchDismiss'])
        ->name('batch-dismiss');
});
```

### 5.5 Controller

| 方法 | 说明 |
|------|------|
| `index()` | 推荐列表页（支持按匹配分/时间/城市筛选） |
| `generate()` | 触发推荐生成（队列异步） |
| `progress()` | 轮询生成进度 |
| `markApplied()` | 标记已投递 |
| `dismiss()` | 忽略单条 |
| `batchDismiss()` | 批量忽略 |

### 5.6 Queue Job

```php
// app/Jobs/GenerateJobRecommendations.php
class GenerateJobRecommendations implements ShouldQueue
{
    - 获取用户最新简历
    - 查询 external_recruitments（未过期 + 未推荐过）
    - 逐条调用 AI 匹配分析
    - 写入 job_recommendations 表
    - 超过 80 分的自动推送通知
}
```

### 5.7 View 文件

```
resources/views/user/job-recommendations/
├── index.blade.php                 # 推荐列表
├── generate.blade.php              # 生成推荐（加载中/进度）
└── partials/
    ├── job-card.blade.php          # 岗位推荐卡片
    ├── match-badge.blade.php       # 匹配分徽章
    └── skill-gap-list.blade.php    # 技能差距列表
```

---

## 6. 功能五：公司情报体系

### 6.1 功能概述

建立全面的公司信息库，包含公司档案、用户点评、面试难度评级和黑名单预警。

### 6.2 功能详情

#### 6.2.1 公司档案（P1）
- **基本信息**：名称、Logo、官网、行业、规模、融资阶段、总部
- **技术栈**：主要使用的技术/工具
- **文化标签**：扁平化管理 / 结果导向 / 技术氛围浓 等
- **福利待遇**：五险一金 / 补充医疗 / 房补 / 餐补 / 股票期权 等
- **数据来源**：管理员录入 + AI 自动抓取 + 用户贡献

#### 6.2.2 公司点评
- **匿名点评**：用户可选择完全匿名
- **点评维度**：
  - 公司文化（1-5星）
  - 管理风格（1-5星）
  - 薪资福利（1-5星）
  - 成长空间（1-5星）
  - 工作生活平衡（1-5星）
- **文字评价**：优点 / 缺点 / 给求职者的建议
- **审核机制**：用户提交后需管理员审核通过才展示
- **统计展示**：各维度平均分 + 点评数量

#### 6.2.3 面试难度评级
- **评级维度**：难度星级（1-5）、面试轮次、面试周期（天）
- **数据聚合**：显示该公司的平均面试难度和常见面试环节
- **关联**：与公司档案关联

#### 6.2.4 黑名单预警
- **标记功能**：用户可标记"避坑"公司
- **原因分类**：拖欠工资 / 虚假招聘 / 职场PUA / 违法裁员 / 其他
- **预警展示**：标记人数达到阈值后在页面醒目展示
- **防滥用**：同一公司同一用户只能标记一次，支持申诉

### 6.3 数据模型

#### 已有表

```php
// app/Models/CompanyProfile.php ✅ 已存在
// 表: company_profiles
- name              公司名称
- slug              唯一标识
- industry          行业
- size              规模 (1-50/51-200/201-500/501-1000/1001-10000/10001+)
- stage             阶段 (天使轮/A轮/B轮/C轮/D轮+/上市/不需要融资)
- headquarters      总部
- website           官网
- description       简介
- tech_stack        JSON 技术栈
- culture_tags      JSON 文化标签
- salary_stats      JSON 薪资统计
- interview_stats   JSON 面试统计
- benefits          JSON 福利
- difficulty_rating 面试难度(1-5)
- offer_rate        Offer 发放率(0-100)
- view_count        浏览量
```

#### 需新建表

```sql
-- company_reviews: 公司点评
CREATE TABLE company_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_profile_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    -- 评分维度 (1-5)
    rating_culture TINYINT UNSIGNED DEFAULT 0,
    rating_management TINYINT UNSIGNED DEFAULT 0,
    rating_salary TINYINT UNSIGNED DEFAULT 0,
    rating_growth TINYINT UNSIGNED DEFAULT 0,
    rating_work_life TINYINT UNSIGNED DEFAULT 0,
    -- 文字评价
    pros TEXT NULL,              -- 优点
    cons TEXT NULL,              -- 缺点
    advice TEXT NULL,            -- 建议
    -- 状态
    status VARCHAR(20) NOT NULL DEFAULT 'pending',  -- pending/approved/rejected
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    like_count INT UNSIGNED DEFAULT 0,
    -- 面试信息（可选）
    interview_difficulty TINYINT UNSIGNED NULL,      -- 面试难度 1-5
    interview_rounds TINYINT UNSIGNED NULL,           -- 面试轮次
    interview_duration_days SMALLINT UNSIGNED NULL,   -- 面试周期(天)
    interview_result ENUM('offer', 'rejected', 'withdrew', 'negotiating') NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_profile_id) REFERENCES company_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_status (company_profile_id, status),
    INDEX idx_user_company (user_id, company_profile_id)
);

-- company_blacklists: 黑名单预警
CREATE TABLE company_blacklists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_profile_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(30) NOT NULL,      -- unpaid_salary / fake_recruitment / workplace_pua / illegal_layoff / other
    detail TEXT NULL,                  -- 详细说明
    status VARCHAR(20) NOT NULL DEFAULT 'active',  -- active / resolved / appealed
    report_count INT UNSIGNED DEFAULT 1,            -- 标记去重计数（冗余字段）
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_profile_id) REFERENCES company_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_company (user_id, company_profile_id),
    INDEX idx_company_status (company_profile_id, status)
);
```

### 6.4 路由设计

```php
// routes/web_user.php 新增

Route::prefix('companies')->name('companies.')->group(function () {
    // 公司档案
    Route::get('/', [CompanyController::class, 'index'])->name('index');
    Route::get('/search', [CompanyController::class, 'search'])->name('search');
    Route::get('/{company}', [CompanyController::class, 'show'])->name('show');

    // 公司点评
    Route::get('/{company}/reviews', [CompanyReviewController::class, 'index'])
        ->name('reviews.index');
    Route::post('/{company}/reviews', [CompanyReviewController::class, 'store'])
        ->middleware('throttle:review-create')
        ->name('reviews.store');
    Route::post('/{company}/reviews/{review}/like', [CompanyReviewController::class, 'like'])
        ->name('reviews.like');

    // 面试难度
    Route::post('/{company}/interview-rating', [CompanyReviewController::class, 'rateInterview'])
        ->name('interview-rating.rate');

    // 黑名单
    Route::post('/{company}/blacklist', [CompanyBlacklistController::class, 'toggle'])
        ->name('blacklist.toggle');
    Route::get('/blacklists', [CompanyBlacklistController::class, 'index'])
        ->name('blacklists.index');
});
```

### 6.5 Controller

**CompanyController**
| 方法 | 说明 |
|------|------|
| `index()` | 公司列表（支持按行业/规模/阶段筛选） |
| `search()` | AJAX 搜索公司名称 |
| `show($company)` | 公司详情页（档案 + 评分 + 点评列表 + 面试难度汇总） |

**CompanyReviewController**
| 方法 | 说明 |
|------|------|
| `index()` | 公司点评列表 |
| `store()` | 提交点评（含面试难度信息） |
| `like()` | 点赞点评 |

**CompanyBlacklistController**
| 方法 | 说明 |
|------|------|
| `toggle()` | 标记/取消标记避坑 |
| `index()` | 我的避坑列表 |

### 6.6 View 文件

```
resources/views/user/companies/
├── index.blade.php                     # 公司列表页（搜索 + 筛选 + 卡片）
├── show.blade.php                      # 公司详情页
├── search-results.blade.php            # 搜索结果
├── reviews/
│   ├── list.blade.php                  # 点评列表
│   └── create-modal.blade.php          # 写点评弹窗
├── blacklists/
│   └── index.blade.php                 # 我的避坑列表
└── partials/
    ├── company-card.blade.php           # 公司卡片
    ├── rating-stars.blade.php           # 星级评分组件
    ├── review-item.blade.php            # 点评条目
    ├── salary-range-chart.blade.php     # 薪资范围图
    ├── difficulty-meter.blade.php       # 面试难度仪
    └── blacklist-warning.blade.php      # 黑名单警告横幅
```

### 6.7 公司详情页布局

```
┌────────────────────────────────────────────────────┐
│ 公司名称 + Logo      │ 黑名单标记（若被大量标记则警告）│
├──────────┬──────────┬──────────┬───────────────────┤
│ 基本信息  │ 面试难度  │ Offer率  │ 综合评分（4维）    │
│ (行业/规模│ ⭐⭐⭐⭐  │  35%     │ ⭐⭐⭐⭐ 4.2       │
│ /阶段/总部│          │          │ (N条点评)          │
├──────────┴──────────┴──────────┴───────────────────┤
│ 📊 薪资分布图（若数据足够）                           │
├────────────────────────────────────────────────────┤
│ 💬 用户点评 (筛选：全部/最新/有帮助)                   │
│ ┌──────────────────────────────────────────────┐   │
│ │ 匿名用户  | 2026-06-20                        │   │
│ │ 文化4★ 管理3★ 薪资5★ 成长3★ 平衡4★           │   │
│ │ 👍优点：...  👎缺点：...                       │   │
│ │ 面试经验：难度4★ | 3轮 | 2周 | 拿到Offer      │   │
│ └──────────────────────────────────────────────┘   │
│                            [写点评] 按钮              │
└────────────────────────────────────────────────────┘
```

---

## 7. 公共模块

### 7.1 导航菜单更新

在 `resources/views/layouts/user.blade.php` 第 154-205 行的 `<ul class="navbar-nav">` 中新增：

```php
{{-- 公司情报 --}}
<li class="nav-item {{ request()->routeIs('user.companies.*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('user.companies.index') }}">
        <i class="ti ti-building me-1"></i>
        <span>公司情报</span>
    </a>
</li>

{{-- 岗位推荐 --}}
<li class="nav-item {{ request()->routeIs('user.recommendations.*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('user.recommendations.index') }}">
        <i class="ti ti-target-arrow me-1"></i>
        <span>智能推荐</span>
    </a>
</li>

{{-- 更多工具 下拉菜单 --}}
<li class="nav-item dropdown {{ request()->routeIs('user.salary.*') || request()->routeIs('user.assessments.*') || request()->routeIs('user.skills.*') ? 'active' : '' }}">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button">
        <i class="ti ti-tool me-1"></i>
        <span>更多工具</span>
    </a>
    <div class="dropdown-menu">
        <a class="dropdown-item" href="{{ route('user.salary.index') }}">
            <i class="ti ti-coin me-2"></i>薪资查询 & 谈判
        </a>
        <a class="dropdown-item" href="{{ route('user.assessments.index') }}">
            <i class="ti ti-license me-2"></i>职业测评
        </a>
        <a class="dropdown-item" href="{{ route('user.skills.index') }}">
            <i class="ti ti-chart-radar me-2"></i>技能评估 & 学习
        </a>
    </div>
</li>
```

### 7.2 配额系统扩展

在 `quota` middleware 或 `QuotaService` 中注册新的 AI 操作类型：

```php
// config/quota.php 或 QuotaService 中的常量
'salary_negotiate'  => ['free' => 1, 'pro' => 5],    // 薪资谈判
'assessment_report' => ['free' => 1, 'pro' => 3],    // 测评报告
'skill_analyze'     => ['free' => 1, 'pro' => 5],    // 技能分析
'job_recommend'     => ['free' => 3, 'pro' => 20],   // 岗位推荐
```

### 7.3 权限扩展

```php
// 需要新增的 Permissions（seeder 中注册）
'view companies'
'create company reviews'
'manage company profiles'
'view blacklist'
```

### 7.4 新增 Migration

需要新建一个 migration `2026_06_24_210000_create_company_intelligence_tables.php`：

```php
// 包含：
// - company_reviews
// - company_blacklists  
```

需要新建一个 migration `2026_06_24_220000_create_skill_assessment_tables.php`：

```php
// 包含：
// - skill_assessments
// - skill_learning_paths
```

### 7.5 新增 AI Prompt 配置

需要在 `ai_prompts` 表中注册以下 prompt 模板：

| prompt_key | 功能 | 必传参数 |
|------------|------|----------|
| `salary_negotiation` | 薪资谈判 | job_title, company, current_salary, target_salary, experience_years, city |
| `career_assessment_mbti` | MBTI 解读 | result_code, dimensions |
| `career_assessment_holland` | 霍兰德解读 | result_code, dimensions |
| `career_assessment_disc` | DISC 解读 | result_code, dimensions |
| `skill_gap_analysis` | 技能差距分析 | current_skills, target_job |
| `learning_path_generate` | 学习路径 | gap_analysis, target_job |
| `job_recommendation_match` | 岗位匹配推荐 | resume_content, job_description |

### 7.6 Admin 管理后台扩展

需要在 `routes/web_admin.php` 中新增以下管理路由：

```php
// 公司管理
Route::resource('companies', Admin\CompanyController::class);
Route::post('companies/{company}/upload-logo', ...);
Route::post('companies/import', ...);

// 点评审核
Route::get('company-reviews/pending', [Admin\CompanyReviewController::class, 'pending']);
Route::post('company-reviews/{review}/approve', ...);
Route::post('company-reviews/{review}/reject', ...);

// 黑名单管理
Route::resource('company-blacklists', Admin\CompanyBlacklistController::class);
```

### 7.7 前端组件

需要的 JavaScript 增强：

| 功能 | 所需库/组件 |
|------|-----------|
| 薪资柱状图 | Chart.js (已有) |
| 技能雷达图 | Chart.js Radar |
| MBTI 类型图 | SVG/CSS 手绘 |
| 面试难度星级 | CSS 纯实现 |
| 图片上传（Logo） | 复用现有上传组件 |
| 无刷新搜索 | Alpine.js + fetch |

---

## 8. 实施计划

### 8.1 开发阶段

| 阶段 | 内容 | 预估工期 |
|------|------|----------|
| **Phase 1** | 公司情报体系（表 → Model → Controller → View → 路由 → 导航） | 2天 |
| **Phase 2** | 薪资谈判助手（Controller → View → AI Prompt → 路由 → 导航） | 1.5天 |
| **Phase 3** | 职业测评（题库 → Controller → View → AI Prompt → 路由） | 1.5天 |
| **Phase 4** | 智能岗位推荐（Queue Job → Controller → View → 路由） | 1.5天 |
| **Phase 5** | 技能评估与学习路径（表 → Model → Controller → View → 路由） | 1.5天 |
| **Phase 6** | Admin 管理后台（公司/点评/黑名单管理） | 1天 |
| **Phase 7** | 集成测试 + 联调 + 优化 | 1天 |

### 8.2 文件清单

| 类型 | 数量 | 说明 |
|------|------|------|
| Migration | 2 | company reviews/blacklists + skills |
| Model | 4 | CompanyReview, CompanyBlacklist, SkillAssessment, LearningPath |
| Controller (User) | 7 | Salary, Assessment, Skill, LearningPath, Company, Review, Blacklist, Recommend |
| Controller (Admin) | 3 | Company, Review, Blacklist |
| Request (Validation) | ~10 | 各模块 FormRequest |
| View (Blade) | ~35 | 视图文件 |
| Route Groups | 5 | 5个功能模块路由 |
| Queue Job | 1 | GenerateRecommendations |
| Config | 1 | assessments.php 题库 |
| Seeders | 1-2 | 公司数据 + 题库种子 |

### 8.3 开发顺序建议

```
1. 公司情报体系 (P1) → 2. 智能推荐 (P1)
3. 职业测评 (P2)    → 4. 薪资谈判 (P2)
5. 技能评估 (P3)
```

---

> 文档结束。确认后将按 Phase 顺序开始开发。
