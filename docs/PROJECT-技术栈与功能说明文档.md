# 职路通 — 技术栈与功能说明文档

> 项目名称：职路通（hq35.cn）
> 框架版本：Laravel 13.x / PHP 8.3+
> 文档更新：2026-05-20

---

## 一、项目概述

职路通是一款面向求职者的 AI 驱动智能求职辅助平台，核心能力包括：AI 简历优化、模拟面试、职位匹配分析、看板式求职管理、会员订阅与积分体系。平台采用前后端不分离的 Laravel Blade + Vite 架构，同时提供 RESTful API 供移动端/第三方调用。

---

## 二、技术栈总览

### 2.1 后端技术栈

| 分类 | 技术 | 版本 | 说明 |
|------|------|------|------|
| 运行时 | PHP | ^8.3 | 强类型声明，启用严格模式 |
| 框架 | Laravel | ^13.0 | 主框架，路由/中间件/Eloquent/队列/事件等 |
| 权限管理 | spatie/laravel-permission | ^7.3 | RBAC 角色权限系统 |
| PDF 生成 | barryvdh/laravel-dompdf | * | 简历 PDF 导出 |
| PDF 解析 | smalot/pdfparser | ^2.12 | 上传 PDF 简历解析导入 |
| 交互式终端 | laravel/tinker | ^3.0 | 开发调试 |
| 日志查看 | laravel/pail | ^1.2.5 | 实时日志流（dev） |
| 代码风格 | laravel/pint | ^1.27 | PSR 代码格式化 |
| 测试 | phpunit/phpunit | ^12.5 | 单元/功能测试 |
| 数据库 | MySQL | 8.x | 主数据存储 |
| 缓存/队列/Session | Redis | 7.x | 缓存、队列驱动、Session 存储 |
| AI 服务 | 智谱 GLM / DeepSeek / 火山引擎 | — | 多供应商 AI，支持故障转移链 |

### 2.2 前端技术栈

| 分类 | 技术 | 版本 | 说明 |
|------|------|------|------|
| 构建工具 | Vite | ^8.0 | 前端资源编译与 HMR |
| CSS 框架 | Tailwind CSS | ^4.0 | 原子化 CSS |
| UI 组件库 | @tabler/core | ^1.2 | Tabler Admin UI 组件 |
| 图标 | @tabler/icons-webfont | ^3.0 | Tabler 图标字体 |
| 富文本编辑器 | TinyMCE | ^8.5 | 邮件模板等富文本编辑 |
| TinyMCE 国际化 | tinymce-i18n | ^26.4 | 中文语言包 |
| CSS 预处理 | Sass | ^1.77 | 样式预处理 |
| Laravel 前端桥 | laravel-vite-plugin | ^3.0 | Blade 模板与 Vite 集成 |

### 2.3 基础设施与运维

| 分类 | 技术 | 说明 |
|------|------|------|
| Web 服务器 | Nginx | 反向代理，SSL 终止 |
| PHP 运行时 | PHP-FPM | FastCGI 进程管理 |
| 队列 Worker | Laravel Queue Worker | Redis 驱动，处理异步任务 |
| 定时调度 | Laravel Scheduler | Cron 驱动的计划任务 |
| 支付 | 支付宝当面付（F2F） | 扫码支付 |
| OAuth 登录 | GitHub / 支付宝 | 第三方账号绑定与登录 |
| 邮件 | SMTP | 邮件通知与验证 |

---

## 三、系统架构

### 3.1 分层架构

```
┌─────────────────────────────────────────────────┐
│                   前端视图层                       │
│   Blade 模板 + Tailwind CSS + Tabler UI          │
│   TinyMCE + 原生 JavaScript                      │
├─────────────────────────────────────────────────┤
│                   路由层                          │
│   web_public / web_user / web_admin / api_v1     │
├─────────────────────────────────────────────────┤
│                   中间件层                        │
│   Auth / Admin / Quota / Throttle / Log / ...    │
├─────────────────────────────────────────────────┤
│                   控制器层                        │
│   Public / User / Admin / Api\V1 / Auth          │
├─────────────────────────────────────────────────┤
│                   服务层                          │
│   Resume / Interview / Membership / Admin / ...  │
├─────────────────────────────────────────────────┤
│                   模型层                          │
│   Eloquent ORM — 43+ 数据模型                    │
├─────────────────────────────────────────────────┤
│                   数据层                          │
│   MySQL + Redis (Cache/Queue/Session)            │
└─────────────────────────────────────────────────┘
```

### 3.2 路由组织

| 路由文件 | 前缀 | 中间件 | 说明 |
|----------|------|--------|------|
| `routes/web_public.php` | `/` | maintenance, mobile | 公开页面（首页、登录、注册、OAuth 回调、帮助中心、公告） |
| `routes/web_user.php` | `/user` | auth, maintenance, user.log, email.verification.required, mobile | 用户功能（简历、面试、求职、会员、通知等） |
| `routes/web_admin.php` | `/admin` | auth, admin, admin.log | 后台管理（用户/简历/面试/系统/权限等全部管理功能） |
| `routes/api_v1.php` | `/api/v1` | force.json, request.id, throttle:api, api.token | RESTful API（Token 认证，供移动端/第三方调用） |
| `routes/console.php` | — | — | Artisan 命令与计划任务注册 |

### 3.3 中间件清单

| 中间件 | 说明 |
|--------|------|
| `SecurityHeaders` | 注入安全响应头（X-Content-Type-Options, CSP 等） |
| `CheckQuota` | 用户配额检查（AI 调用次数限制） |
| `LimitRequestBody` | 请求体大小限制 |
| `EnsureEmailVerificationRequired` | 强制邮箱验证 |
| `LogUserActions` | 用户操作日志记录 |
| `CheckMaintenanceMode` | 维护模式检查 |
| `LogAdminActions` | 管理员操作审计日志 |
| `RedirectIfMobile` | 移动端访问提示/重定向 |
| `ApiTokenAuth` | API Token 认证 |
| `EnsureUserIsActive` | 检查用户是否被封禁/停用 |
| `ApplyDynamicSessionLifetime` | 动态 Session 生命周期 |
| `EnsureIsAdmin` | 管理员身份验证 |
| `RequestIdMiddleware` | 请求追踪 ID 注入 |
| `ForceJsonResponse` | 强制 JSON 响应（API） |

---

## 四、功能模块详解

### 4.1 用户认证与账号体系

**涉及文件：**
- 控制器：`Auth/AuthenticatedSessionController`, `Auth/RegisteredUserController`, `Auth/OAuthController`, `Auth/PasswordResetLinkController`, `Auth/NewPasswordController`, `Auth/ConfirmablePasswordController`
- 服务：`Auth/OAuthUserResolutionService`, `Auth/OAuthProviderConfigService`, `Auth/OAuthStateService`, `Auth/OAuthDiagnosticsService`, `Auth/Providers/GithubOAuthProvider`, `Auth/Providers/AlipayOAuthProvider`
- 模型：`User`, `UserOAuthAccount`, `PasswordHistory`, `LoginHistory`, `AccountRecoveryToken`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 邮箱注册/登录 | 标准邮箱密码注册与登录 |
| OAuth 登录 | 支持 GitHub、支付宝第三方登录 |
| OAuth 账号绑定 | 已登录用户可绑定/解绑第三方账号 |
| 密码重置 | 邮件链接重置密码 |
| 密码确认 | 敏感操作前二次密码验证 |
| 邮箱验证 | 注册后强制邮箱验证才可使用功能 |
| 登录历史 | 记录用户登录 IP、时间等 |
| 密码历史 | 防止重复使用近期密码 |
| 账号注销与恢复 | 用户可申请注销，30 天内可恢复 |
| 全设备登出 | 一键踢出所有其他设备会话 |
| 账号停用/封禁 | 管理员可暂停/恢复用户账号 |

### 4.2 简历管理

**涉及文件：**
- 控制器：`User/ResumeController`, `User/ResumeManageController`, `User/ResumeEditorController`, `User/ResumeImportController`, `User/ResumeExportController`, `User/ResumeStreamController`, `User/ResumeTemplateController`
- 服务：`Resume/ResumeFileImportService`, `Resume/ResumeModuleDisplayService`, `Resume/ResumeExportHandlerService`, `Resume/ResumeExportTaskService`, `Resume/ResumeCreateQuotaService`, `Resume/ResumeOptimizeUiService`, `Resume/Builders/ResumePdfBuilder`, `Resume/Builders/ResumeDocxBuilder`, `Resume/Builders/DocxXmlBuilder`, `Resume/Builders/DocxTemplateService`, `Resume/Builders/DocxMediaService`, `Resume/Parsing/ResumeModuleParserService`, `Resume/Parsing/ResumeEntryParser`, `Resume/Parsing/ResumeEducationParser`, `Resume/Parsing/ResumeSectionSplitter`, `Resume/Template/*` (5 个模板服务)
- 模型：`Resume`, `ResumeModule`, `ResumeExportTask`, `ResumeTemplate`
- 请求：`ResumeStoreRequest`, `ResumeUpdateRequest`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 简历 CRUD | 创建、查看、编辑、删除简历 |
| 模块化编辑器 | 可视化拖拽式简历编辑器，支持个人信息/教育/工作经历/项目/技能/证书/求职意向/自我评价等模块 |
| 简历模板 | 内置 Classic / Modern / Elegant / Minimal / Creative / Timeline 等模板，支持一键应用与撤销 |
| 模板推荐 | 根据用户行业/岗位推荐最佳模板 |
| 模板分析 | 模板使用统计与热度分析 |
| PDF 导出 | 基于 DomPDF 的简历 PDF 导出，支持异步队列导出 |
| DOCX 导出 | 基于 PHPWord 的 Word 文档导出 |
| PDF 导入解析 | 上传 PDF 简历自动解析为结构化数据 |
| 文档草稿导入 | 上传文档生成简历草稿 |
| 头像上传 | 简历头像上传与管理 |
| 软删除与回收站 | 简历删除后进入回收站，可恢复或永久删除 |
| 打印预览 | 浏览器端打印预览 |
| 简历主题 | 支持浅色/深色主题切换 |
| 实时保存 | 编辑器自动保存模块数据 |
| 撤销/重做 | 编辑器操作撤销与重做 |

### 4.3 AI 简历优化

**涉及文件：**
- 控制器：`User/ResumeOptimizeController`, `User/ResumeOptimizeSessionController`, `User/ResumeOptimizeInsightsController`, `User/ResumeAnalysisController`, `User/ResumeKeywordController`
- 服务：`Resume/ResumeOptimizationHandlerService`, `Resume/ResumeOptimizeStreamService`, `Resume/ResumeSectionAiService`, `Resume/ResumeOptimizedContentApplyService`, `Resume/ResumeOptimizeSessionService`, `Resume/ResumeOptimizeHealthService`, `Resume/ResumeOptimizeSelectionService`, `Resume/ResumeOptimizeGainService`, `Resume/Support/ResumeOptimizeSelectionApplier`, `Resume/Support/ResumeOptimizeDiffAnalyzer`, `Resume/Support/ResumeOptimizeResultParser`, `Resume/Support/ResumeOptimizationPayloadSanitizer`, `Resume/PromptStrategy/PromptInstructionBuilder`, `Resume/PromptStrategy/PromptStrategyResolver`, `Resume/OptimizeAbBucketService`, `Resume/Keyword/SimpleKeywordExtractor`, `Resume/Keyword/KeywordExtractorFactory`, `Resume/Keyword/KeywordNormalizer`, `Resume/Keyword/KeywordSynonymDictionary`
- 模型：`ResumeOptimizeSession`, `ResumeOptimizeVersion`, `ResumeOptimizeApplyLog`
- 任务：`Jobs/OptimizeResumeSessionJob`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 全文 AI 优化 | 基于目标岗位的全文简历 AI 优化 |
| 分模块 AI 优化 | 针对单个模块（如工作经历、项目经验）的 AI 优化 |
| AI 内容生成 | 根据提示自动生成简历模块内容 |
| 流式输出 | SSE 流式实时展示 AI 优化过程 |
| 优化预热 | 优化前预加载 AI 连接，减少首字延迟 |
| 优化会话管理 | 每次优化创建独立会话，支持历史查看与重试 |
| 优化对比 | 优化前后内容对比展示（Diff 视图） |
| 选择性应用 | 可选择性地应用 AI 优化建议到指定模块 |
| 优化策略 | 多种 Prompt 策略（应届生/社招/技术岗等），自动匹配 |
| A/B 测试 | 优化策略 A/B 分桶测试 |
| 策略收益分析 | 不同优化策略的效果对比与收益统计 |
| ATS 评分 | AI 模拟 ATS 系统对简历进行评分 |
| ATS 报告 | 详细的 ATS 兼容性分析报告 |
| 关键词提取 | 从简历中提取核心关键词 |
| 关键词同义词 | 关键词同义词词典，扩展匹配范围 |
| 优化健康检查 | 定时检查优化会话状态，自动回收异常会话 |
| 新手引导 | 编辑器内置新手引导流程 |

### 4.4 模拟面试

**涉及文件：**
- 控制器：`User/InterviewController`
- 服务：`Interview/StartInterviewService`, `Interview/InterviewSessionService`, `Interview/InterviewQuestionGeneratorService`, `Interview/InterviewAnswerSubmissionService`, `Interview/InterviewAnswerEvaluationService`, `Interview/InterviewFastEvaluator`, `Interview/InterviewReportService`, `Interview/InterviewResumeContextBuilder`, `Interview/InterviewFallbackQuestionBuilder`
- 模型：`InterviewSession`, `InterviewQuestion`
- 任务：`Jobs/EvaluateInterviewAnswerJob`
- 请求：`InterviewStoreRequest`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 创建面试 | 选择简历+岗位描述，AI 生成面试题目 |
| 面试模式 | 支持文字面试与语音面试两种模式 |
| 语音面试 | 移动端语音面试，支持二维码扫码进入 |
| 实时答题 | 逐题回答，支持心跳保活 |
| AI 评估 | 每道回答异步提交 AI 评估，返回评分与反馈 |
| 快速评估 | 面试结束后快速生成整体评估 |
| 面试报告 | 完整的面试评估报告，含各维度评分 |
| 面试心跳 | 防止面试会话超时 |
| 重试评估 | 单题评估失败可重试 |
| 候选人画像 | 面试中展示候选人关键信息 |
| 降级问题 | AI 服务不可用时自动生成备选问题 |

### 4.5 职位匹配分析

**涉及文件：**
- 控制器：`User/JobMatchingController`
- 服务：`JobMatchingService`, `JobMatching/JobMatchAuditLogger`
- 模型：`JobMatchAnalysis`, `JobMatchAuditLog`
- 请求：`AnalyzeJobMatchRequest`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 岗位匹配分析 | 输入岗位描述，AI 分析简历与岗位的匹配度 |
| 匹配评分 | 多维度匹配度评分（技能/经验/教育等） |
| 优化建议 | 根据匹配分析给出简历改进建议 |
| 历史记录 | 匹配分析历史查看与管理 |
| 审计日志 | 匹配分析操作审计记录 |

### 4.6 看板式求职管理

**涉及文件：**
- 控制器：`User/KanbanController`, `Api/V1/KanbanController`
- 服务：`Api/V1/KanbanService`
- 模型：`JobApplication`
- 请求：`Api/V1/KanbanStoreRequest`, `Api/V1/KanbanUpdateRequest`, `Api/V1/KanbanStatusUpdateRequest`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 看板视图 | 拖拽式求职进度看板（待投递/已投递/面试中/已录用等） |
| 求职记录 | 创建/编辑/删除求职申请记录 |
| 状态流转 | 拖拽或点击更新求职状态 |
| 统计面板 | 求职数据统计（投递数/面试数/通过率等） |
| 截止日期提醒 | 职位申请截止日期邮件提醒 |

### 4.7 会员与积分体系

**涉及文件：**
- 控制器：`User/MembershipController`, `AlipayPayController`, `Admin/PlanController`, `Admin/CreditPackController`, `Admin/SubscriptionManageController`
- 服务：`Membership/SubscriptionService`, `Membership/QuotaService`, `Membership/CreditService`, `Membership/PlanFeatureService`, `Membership/AlipayF2FPaymentService`
- 模型：`Plan`, `Subscription`, `Order`, `CreditPack`, `CreditPackOrder`, `UserCredit`, `QuotaUsage`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 套餐管理 | 管理员创建/编辑/删除会员套餐 |
| 积分包管理 | 管理员创建/编辑/删除积分包 |
| 订阅购买 | 用户选择套餐并支付 |
| 积分包购买 | 用户购买额外积分包 |
| 支付宝支付 | 支付宝当面付扫码支付 |
| 订单管理 | 订单创建/查询/过期处理 |
| 积分系统 | 用户积分余额、消费记录、过期清理 |
| 配额管理 | AI 功能调用配额检查与扣减 |
| 会员激活 | 管理员为用户手动激活套餐 |
| 会员取消 | 管理员/用户取消订阅 |
| 订阅到期 | 自动到期处理 |
| 积分赠送 | 管理员为用户手动赠送积分 |
| 使用记录 | 用户 AI 功能使用量统计 |

### 4.8 通知系统

**涉及文件：**
- 控制器：`User/NotificationController`, `User/NotificationPreferenceController`, `Admin/NotificationController`, `Admin/EmailTemplateController`, `Admin/EmailLogController`, `Admin/MailConfigController`
- 服务：`Notification/UserNotificationService`, `Notification/EmailNotificationService`
- 模型：`UserNotification`, `AdminNotification`, `EmailTemplate`, `EmailLog`
- 任务：`Jobs/SendNotificationJob`, `Jobs/ResendEmailJob`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 站内通知 | 用户站内通知推送与查看 |
| 邮件通知 | 邮件通知发送（注册/简历导出/面试/求职等） |
| 通知偏好 | 用户自定义通知接收偏好 |
| 管理员通知 | 管理员创建/编辑/发送全局通知 |
| 定时发送 | 通知可设置定时发送 |
| 邮件模板 | 可视化邮件模板管理（TinyMCE 编辑） |
| 邮件预览 | 邮件模板实时预览 |
| 邮件日志 | 邮件发送记录与状态追踪 |
| 邮件重发 | 失败邮件一键重发/批量重发 |
| 邮件配置 | 管理员动态配置 SMTP 参数 |
| 邮件统计 | 邮件发送成功率/失败率统计 |
| 邮件测试 | 发送测试邮件验证配置 |
| 未读计数 | 实时未读通知数量 |

### 4.9 反馈系统

**涉及文件：**
- 控制器：`User/FeedbackController`, `Admin/FeedbackController`
- 服务：`Feedback/FeedbackAuditLogger`, `Feedback/FeedbackRewardService`
- 模型：`Feedback`, `FeedbackReply`, `FeedbackAuditLog`, `FeedbackReward`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 提交反馈 | 用户提交功能建议/Bug 报告 |
| 反馈回复 | 管理员回复用户反馈 |
| 状态管理 | 反馈状态流转（待处理/处理中/已解决/已关闭） |
| 内部备注 | 管理员添加内部备注 |
| 采纳标记 | 标记反馈为已采纳 |
| 奖励发放 | 采纳反馈后发放积分奖励 |
| 审计日志 | 反馈操作审计记录 |
| 软删除 | 反馈支持软删除 |

### 4.10 后台管理系统

**涉及文件：**
- 控制器：`Admin/DashboardController`, `Admin/UserController`, `Admin/ResumeController`, `Admin/InterviewController`, `Admin/JobApplicationController`, `Admin/AiConfigController`, `Admin/AiPromptController`, `Admin/AiAnalyticsController`, `Admin/SiteSettingController`, `Admin/SystemOpsController`, `Admin/FileManagerController`, `Admin/DataExportController`, `Admin/RolePermissionController`, `Admin/ScheduleController`, `Admin/ActionLogController`, `Admin/UsageLogController`, `Admin/SystemSettingAuditController`, `Admin/OAuthDiagnosticsController`, `Admin/ExternalRecruitmentController`, `Admin/SiteEventController`, `Admin/HelpArticleController`, `Admin/HelpCategoryController`
- 服务：`Admin/DashboardService`, `Admin/AiConfigService`, `Admin/AiConfigUpdateService`, `Admin/AiConfigVerifier`, `Admin/AiConfigDashboardService`, `Admin/AiBenchmarkService`, `Admin/SystemSettingService`, `Admin/SystemSettingUpdateService`, `Admin/SystemOpsService`, `Admin/FileManagerService`, `Admin/DataExportService`, `Admin/CsvExportService`, `Admin/MailRuntimeConfigService`
- 模型：`SystemSetting`, `SystemSettingAuditLog`, `AiPrompt`, `AiConfigHistory`, `AdminActionLog`, `UsageLog`, `ScheduleLog`, `ExternalRecruitment`, `SiteEvent`, `HelpArticle`, `HelpCategory`

**功能清单：**

| 模块 | 功能 |
|------|------|
| 仪表盘 | 系统概览（用户数/简历数/面试数/收入等关键指标） |
| 用户管理 | 用户列表/创建/编辑/删除/批量删除/角色分配/停用/激活 |
| 简历管理 | 查看用户简历/删除/触发 AI 优化/ATS 评分 |
| 面试管理 | 面试列表/查看/删除/重试评估 |
| 求职管理 | 求职申请列表/查看/编辑/删除 |
| AI 配置 | AI 供应商配置/模型选择/密钥管理/故障转移链 |
| AI 测试 | AI 连接测试与基准测试 |
| AI 配置历史 | AI 配置变更历史追溯 |
| AI Prompt 管理 | Prompt 模板 CRUD/测试/复制 |
| AI 分析 | AI 调用量/成本/性能分析面板 |
| 站点设置 | 站点基础配置/SEO/自定义 Head 代码/缓存清理 |
| 设置审计 | 系统设置变更审计日志 |
| 角色权限 | RBAC 角色/权限管理，支持权限同步 |
| 文件管理 | 服务器文件浏览/上传/创建文件夹/重命名/删除/下载 |
| 数据导出 | CSV 数据导出（用户/简历/面试等）+ 预览 |
| 邮件配置 | SMTP 动态配置/测试/统计/日志/重发 |
| 通知管理 | 管理员通知 CRUD/发送/定时发送 |
| 计划任务 | 查看计划任务列表/手动执行/日志查看 |
| 系统运维 | 缓存清理/优化/队列管理/数据库迁移/日志查看 |
| OAuth 诊断 | OAuth 配置诊断/自动修复 |
| 操作日志 | 管理员操作审计日志 |
| 使用日志 | 用户 AI 功能使用日志 |
| 套餐管理 | 会员套餐 CRUD |
| 积分包管理 | 积分包 CRUD |
| 订阅管理 | 用户订阅查看/手动激活/取消 |
| 外部招聘 | 外部招聘信息同步/审核（求职方舟/OfferStar 爬取） |
| 公告管理 | 站点公告/活动 CRUD |
| 帮助中心 | 帮助分类/文章 CRUD |

### 4.11 RESTful API

**涉及文件：**
- 控制器：`Api/V1/AuthController`, `Api/V1/ResumeController`, `Api/V1/ResumeAiController`, `Api/V1/InterviewController`, `Api/V1/KanbanController`
- 服务：`Api/V1/AuthService`, `Api/V1/ResumeService`, `Api/V1/InterviewService`, `Api/V1/KanbanService`, `Api/Concerns/AssertsOwnership`
- 请求：`Api/V1/ResumeStoreRequest`, `Api/V1/ResumeUpdateRequest`, `Api/V1/ResumeImportRequest`, `Api/V1/ResumeOptimizeRequest`, `Api/V1/InterviewStoreRequest`, `Api/V1/InterviewSubmitAnswerRequest`, `Api/V1/KanbanStoreRequest`, `Api/V1/KanbanUpdateRequest`, `Api/V1/KanbanStatusUpdateRequest`
- 中间件：`ApiTokenAuth`, `ForceJsonResponse`, `RequestIdMiddleware`

**功能清单：**

| 功能 | 说明 |
|------|------|
| Token 认证 | API Token 认证与刷新 |
| 简历 API | 简历 CRUD / 导入 / AI 优化 / ATS 评分 |
| 面试 API | 面试创建/答题/完成/报告 |
| 看板 API | 求职看板 CRUD / 状态更新 / 统计 |
| 健康检查 | `/api/health` 服务健康检查端点 |

### 4.12 公共页面

**涉及文件：**
- 控制器：`Public/HelpController`, `Public/SiteEventController`
- 模型：`HelpArticle`, `HelpCategory`, `SiteEvent`

**功能清单：**

| 功能 | 说明 |
|------|------|
| 首页 | 产品介绍/定价/最新公告 |
| 帮助中心 | 帮助分类/搜索/文章详情 |
| 公告列表 | 站点公告/活动列表 |
| 公告详情 | 公告/活动详情页 |
| 移动端提示 | 移动端访问提示页 |

---

## 五、数据模型总览

| 模型 | 说明 | 核心字段 |
|------|------|----------|
| `User` | 用户 | email, password, is_admin, current_plan_slug, suspended_at |
| `Resume` | 简历 | title, raw_text, optimized_text, template, theme, soft_deletes |
| `ResumeModule` | 简历模块 | type, content, sort_order |
| `ResumeExportTask` | 导出任务 | format, status, file_path, retry_count |
| `ResumeTemplate` | 简历模板 | name, slug, category, thumbnail |
| `ResumeOptimizeSession` | 优化会话 | status, strategy, result |
| `ResumeOptimizeVersion` | 优化版本 | original_content, optimized_content, diff |
| `ResumeOptimizeApplyLog` | 优化应用日志 | module_type, applied_fields |
| `InterviewSession` | 面试会话 | mode, status, overall_score, job_description |
| `InterviewQuestion` | 面试题目 | question, answer, score, feedback, dimension |
| `JobApplication` | 求职申请 | company, position, status, deadline |
| `JobMatchAnalysis` | 岗位匹配 | job_description, match_score, suggestions |
| `JobMatchAuditLog` | 匹配审计 | action, details |
| `Plan` | 会员套餐 | name, slug, price, features, quota_limits |
| `Subscription` | 用户订阅 | plan_id, status, expires_at |
| `Order` | 订单 | order_no, amount, status, payment_method |
| `CreditPack` | 积分包 | name, credits, price |
| `CreditPackOrder` | 积分包订单 | order_no, credits, amount |
| `UserCredit` | 用户积分 | balance, total_earned, total_spent |
| `QuotaUsage` | 配额使用 | resource_type, used_count, limit |
| `UserNotification` | 用户通知 | type, title, body, read_at |
| `AdminNotification` | 管理员通知 | title, body, scheduled_at, sent_at |
| `EmailTemplate` | 邮件模板 | name, slug, subject, body_html |
| `EmailLog` | 邮件日志 | to, subject, status, error_message |
| `Feedback` | 用户反馈 | type, title, content, status, adoption |
| `FeedbackReply` | 反馈回复 | content, is_admin |
| `FeedbackAuditLog` | 反馈审计 | action, details |
| `FeedbackReward` | 反馈奖励 | credits, reason |
| `SystemSetting` | 系统设置 | key, value, type |
| `SystemSettingAuditLog` | 设置审计 | setting_key, old_value, new_value |
| `AiPrompt` | AI Prompt | name, slug, system_prompt, user_prompt_template |
| `AiConfigHistory` | AI 配置历史 | provider, model, changed_fields |
| `UserOAuthAccount` | OAuth 账号 | provider, provider_id, access_token |
| `PasswordHistory` | 密码历史 | password_hash |
| `LoginHistory` | 登录历史 | ip, user_agent, login_at |
| `AccountRecoveryToken` | 账号恢复 | token, expires_at |
| `UserActionLog` | 用户操作日志 | action, details, ip |
| `AdminActionLog` | 管理员操作日志 | action, details, ip |
| `UsageLog` | 使用日志 | resource_type, credits_used |
| `ScheduleLog` | 计划任务日志 | task, status, output |
| `ExternalRecruitment` | 外部招聘 | source, title, company, url, status |
| `SiteEvent` | 站点公告 | title, content, published_at |
| `HelpCategory` | 帮助分类 | name, slug, description |
| `HelpArticle` | 帮助文章 | title, slug, content, category_id |

---

## 六、异步任务与计划任务

### 6.1 队列任务（Jobs）

| 任务 | 队列 | 说明 |
|------|------|------|
| `OptimizeResumeSessionJob` | resume-export | 异步执行简历 AI 优化 |
| `ProcessResumeExportTaskJob` | resume-export | 异步处理简历导出（PDF/DOCX） |
| `EvaluateInterviewAnswerJob` | interview-eval | 异步评估面试回答 |
| `SendNotificationJob` | default | 异步发送通知 |
| `ResendEmailJob` | default | 异步重发邮件 |

### 6.2 计划任务（Schedule / Console Commands）

| 命令 | 说明 |
|------|------|
| `ExpirePendingMembershipOrders` | 过期未支付会员订单 |
| `ExpireSubscriptions` | 到期订阅处理 |
| `CleanExpiredCredits` | 清理过期积分 |
| `CheckResumeOptimizeHealth` | 检查简历优化会话健康状态 |
| `RecycleStaleResumeOptimizeSessions` | 回收超时优化会话 |
| `PurgeJobMatchHistory` | 清理过期匹配分析历史 |
| `SendBusinessReminders` | 发送业务提醒（截止日期等） |
| `SendScheduledNotifications` | 发送定时通知 |
| `DatabaseBackup` | 数据库备份 |
| `ScrapeQiuzhifangzhouPositionRecruitments` | 爬取求职方舟社招信息 |
| `ScrapeQiuzhifangzhouCampusRecruitments` | 爬取求职方舟校招信息 |
| `ScrapeOfferstarRecruitments` | 爬取 OfferStar 招聘信息 |
| `ProbeExternalRecruitmentLinks` | 检测外部招聘链接有效性 |
| `OAuthDiagnostics` | OAuth 配置诊断 |

---

## 七、安全机制

| 安全措施 | 说明 |
|----------|------|
| CSRF 保护 | 全局 CSRF Token 验证 |
| Session 加密 | SESSION_ENCRYPT=true |
| Secure Cookie | SESSION_SECURE_COOKIE=true |
| 安全响应头 | SecurityHeaders 中间件注入 X-Content-Type-Options/X-Frame-Options/CSP |
| 请求体限制 | LimitRequestBody 中间件 |
| 速率限制 | 多级 Throttle（登录/API/简历写入/AI 调用/管理操作等） |
| 密码确认 | 敏感操作前二次密码验证 |
| 密码历史 | 防止重复使用近期密码 |
| RBAC 权限 | spatie/laravel-permission 细粒度权限控制 |
| Policy 授权 | Eloquent Policy（Resume/Interview/JobApplication/Feedback/Notification） |
| 邮箱验证 | 注册后强制邮箱验证 |
| 账号停用 | 管理员可停用/封禁用户 |
| 输入验证 | FormRequest 全量输入验证 |
| SQL 注入防护 | Eloquent ORM 参数绑定 |
| XSS 防护 | Blade 模板自动转义 + 输入过滤 |
| 审计日志 | 管理员操作/系统设置/反馈操作全量审计 |
| 请求追踪 | RequestIdMiddleware 注入唯一请求 ID |
| API Token 认证 | API 端点 Token 认证 |
| 维护模式 | 支持全局维护模式开关 |

---

## 八、自定义配置文件

| 配置文件 | 说明 |
|----------|------|
| `config/ai.php` | AI 供应商/模型/密钥/故障转移链配置（支持数据库动态覆盖） |
| `config/quota.php` | 用户配额限制配置 |
| `config/membership.php` | 会员体系配置 |
| `config/resume.php` | 简历模块配置 |
| `config/resume_prompt_strategies.php` | 简历优化 Prompt 策略配置 |
| `config/interview.php` | 面试模块配置 |
| `config/job-matching.php` | 职位匹配配置 |
| `config/export.php` | 导出任务配置 |
| `config/security.php` | 安全策略配置 |
| `config/admin.php` | 后台管理配置 |
| `config/ui.php` | UI 界面配置 |
| `config/cache_ttl.php` | 缓存 TTL 配置 |
| `config/request.php` | 请求限制配置 |

---

## 九、数据库 Seeder

| Seeder | 说明 |
|--------|------|
| `DatabaseSeeder` | 主 Seeder 入口 |
| `RolesAndPermissionsSeeder` | 初始化角色与权限数据 |
| `MembershipSeeder` | 初始化会员套餐与积分包数据 |
| `AiPromptSeeder` | 初始化 AI Prompt 模板数据 |
| `EmailTemplatesSeeder` | 初始化邮件模板数据 |
| `HelpCenterSeeder` | 初始化帮助中心分类与文章 |
| `ResumeTemplatesSeeder` | 初始化简历模板数据 |

---

## 十、邮件模板

| 模板 | 说明 |
|------|------|
| `emails/welcome` | 欢迎邮件 |
| `emails/verify-email` | 邮箱验证 |
| `emails/reset-password` | 密码重置 |
| `emails/feedback-reply` | 反馈回复通知 |
| `emails/account-deletion-requested` | 账号注销确认 |
| `emails/resume-export/started` | 简历导出开始 |
| `emails/resume-export/completed` | 简历导出完成 |
| `emails/resume-export/failed` | 简历导出失败 |
| `emails/resume/completed` | 简历优化完成 |
| `emails/interview/started` | 面试开始 |
| `emails/interview/completed` | 面试完成 |
| `emails/interview/reminder` | 面试提醒 |
| `emails/interview/feedback` | 面试反馈 |
| `emails/job-application/applied` | 求职申请已提交 |
| `emails/job-application/status-update` | 求职状态更新 |
| `emails/job-application/deadline` | 截止日期提醒 |
| `emails/job-application/suggestion` | 求职建议 |

---

## 十一、错误页面

| 页面 | 说明 |
|------|------|
| `errors/403` | 无权限 |
| `errors/404` | 页面不存在 |
| `errors/419` | CSRF Token 过期 |
| `errors/500` | 服务器错误 |
| `errors/maintenance` | 维护模式 |

---

## 十二、项目目录结构（关键路径）

```
app/
├── Console/Commands/          # 14 个 Artisan 命令
├── Http/
│   ├── Controllers/
│   │   ├── Admin/             # 28 个后台控制器
│   │   ├── Api/V1/            # 5 个 API 控制器
│   │   ├── Auth/              # 7 个认证控制器
│   │   ├── Public/            # 2 个公共页面控制器
│   │   └── User/              # 20 个用户端控制器 + 7 个 Trait
│   ├── Middleware/             # 14 个自定义中间件
│   └── Requests/               # 16 个表单请求验证类
├── Jobs/                       # 5 个异步队列任务
├── Models/                     # 43 个 Eloquent 模型
├── Policies/                   # 5 个授权策略
├── Services/
│   ├── Admin/                  # 13 个后台服务
│   ├── Api/V1/                 # 4 个 API 服务
│   ├── Auth/                   # 6 个认证服务
│   ├── Feedback/               # 3 个反馈服务
│   ├── Interview/              # 9 个面试服务
│   ├── JobMatching/            # 1 个职位匹配服务
│   ├── Membership/             # 5 个会员服务
│   ├── Notification/           # 2 个通知服务
│   └── Resume/                 # 30+ 个简历服务
│       ├── Builders/           # PDF/DOCX 构建器
│       ├── Keyword/            # 关键词提取
│       ├── Parsing/            # 简历解析
│       ├── PromptStrategy/     # Prompt 策略
│       ├── Support/            # 优化辅助工具
│       └── Template/           # 模板服务
├── helpers.php                 # 全局辅助函数
config/                         # 26 个配置文件
database/
├── migrations/                 # 70+ 个数据库迁移
└── seeders/                    # 7 个数据填充器
resources/views/
├── admin/                      # 后台视图
├── auth/                       # 认证视图
├── emails/                     # 邮件模板
├── errors/                     # 错误页面
├── partials/                   # 公共组件
├── public/                     # 公共页面视图
└── user/                       # 用户端视图
    ├── dashboard/              # 仪表盘
    ├── feedbacks/              # 反馈
    ├── interviews/             # 面试
    ├── jobs/                   # 职位匹配
    ├── kanban/                 # 看板
    ├── membership/             # 会员
    ├── notifications/          # 通知
    ├── profile/                # 个人资料
    ├── resume-templates/       # 简历模板
    └── resumes/                # 简历管理（含编辑器）
routes/
├── web_public.php              # 公开路由
├── web_user.php                # 用户路由
├── web_admin.php               # 管理后台路由
├── api_v1.php                  # API 路由
└── console.php                 # 命令路由
```

---

## 十三、AI 服务架构

```
┌──────────────────────────────────────────────┐
│              AI 配置层（动态）                  │
│   config/ai.php + 数据库 SystemSetting 覆盖    │
├──────────────────────────────────────────────┤
│              供应商适配层                      │
│   ┌──────────┐ ┌──────────┐ ┌──────────┐    │
│   │  智谱GLM  │ │ DeepSeek │ │ 火山引擎  │    │
│   └──────────┘ └──────────┘ └──────────┘    │
├──────────────────────────────────────────────┤
│              故障转移链                        │
│   Primary → Fallback1 → Fallback2           │
├──────────────────────────────────────────────┤
│              Prompt 策略层                     │
│   PromptStrategyResolver → PromptInstruction │
│   Builder（应届生/社招/技术岗/管理岗...）       │
├──────────────────────────────────────────────┤
│              业务调用层                        │
│   ResumeOptimization / Interview / JobMatch  │
│   KeywordExtract / ATS Score                 │
└──────────────────────────────────────────────┘
```

**支持的 AI 模型：**

| 供应商 | 模型 | 说明 |
|--------|------|------|
| 智谱 | glm-4.5-flash | 默认供应商，中文优化 |
| DeepSeek | deepseek-v4-flash | 备选供应商 |
| 火山引擎 | ep-20260327162351-d8l62 | 备选供应商 |

---

## 十四、开发与部署

### 14.1 开发环境启动

```bash
# 一键安装
composer setup

# 开发模式（同时启动 Server + Queue + Pail + Vite）
composer dev
```

### 14.2 生产部署检查清单

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`
- [ ] Redis 作为 Cache/Queue/Session 驱动
- [ ] 队列 Worker 常驻运行（Supervisor 管理）
- [ ] Laravel Scheduler Cron 配置（`* * * * * php artisan schedule:run`）
- [ ] 存储目录权限（`storage/` 和 `bootstrap/cache/`）
- [ ] HTTPS 证书配置
- [ ] 支付宝密钥配置
- [ ] AI 供应商密钥配置
- [ ] SMTP 邮件配置
- [ ] OAuth 应用配置（GitHub/支付宝）

### 14.3 代码质量工具

| 工具 | 命令 | 说明 |
|------|------|------|
| Laravel Pint | `./vendor/bin/pint` | PSR 代码风格检查与修复 |
| PHPUnit | `composer test` | 单元/功能测试 |
| Laravel Pail | `php artisan pail` | 实时日志查看 |
