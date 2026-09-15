# 职路通 - 产品路线图

> 文档版本：v1.0  
> 创建时间：2026-06-24  
> 关联文档：[features-development-plan.md](../features-development-plan.md)

---

## 当前阶段：新功能模块开发（Phase 1-7）✅ 已完成

基于 `docs/features-development-plan.md` 的 5 大功能模块开发任务。

### 阶段目标

| Phase | 模块 | 优先级 | 状态 |
|-------|------|--------|------|
| Phase 1 | 公司情报体系 | P1 | ✅ 已完成 |
| Phase 2 | 薪资谈判助手 | P2 | ✅ 已完成 |
| Phase 3 | AI 职业测评 | P2 | ✅ 已完成 |
| Phase 4 | 智能岗位推荐引擎 | P1 | ✅ 已完成 |
| Phase 5 | 技能评估与学习路径 | P3 | ✅ 已完成 |
| Phase 6 | Admin 管理后台扩展 | P2 | ✅ 已完成 |
| Phase 7 | 集成测试 + 联调 + 优化 | P1 | ✅ 已完成 |

### 优先级评分矩阵

参考 `docs/standards/planning.md`（待补建），采用以下评分维度：

- **业务价值**：对用户求职决策的帮助程度（1-5）
- **技术依赖**：是否阻塞其他模块（1-5）
- **实现成本**：开发工作量倒数（1-5，越高越省力）
- **数据准备度**：Migration/Model 是否就绪（1-5）

### 关键路径

```
Phase 1 (公司情报) ─┐
                    ├─→ Phase 4 (岗位推荐) ─┐
Phase 5 (技能评估) ─┘                        ├─→ Phase 7 (集成测试)
Phase 2 (薪资谈判) ──→ Phase 3 (职业测评) ───┘
                                              Phase 6 (Admin) ──┘
```

### 已完成基础设施

- ✅ Migration: `2026_06_24_200000_create_new_features_tables.php`（9 张表）
- ✅ Migration: `2026_06_24_210000_create_company_intelligence_tables.php`（公司点评/黑名单）
- ✅ Migration: `2026_06_25_010000_create_skill_assessment_tables.php`（技能评估/学习路径）
- ✅ Models: `CareerAssessment`, `SalaryNegotiationSession`, `CareerPlan`, `JobRecommendation`, `CompanyProfile`, `CompanyReview`, `CompanyBlacklist`, `SkillAssessment`, `SkillLearningPath`, `SalarySurvey`
- ✅ Routes: `routes/web_user.php` 已新增全部功能路由
- ✅ Routes: `routes/web_admin.php` 已新增公司情报管理路由
- ✅ Controllers: `app/Http/Controllers/User/` 已创建全部业务控制器
- ✅ Controllers: `app/Http/Controllers/Admin/CompanyIntelligenceController.php`
- ✅ Services: `SalaryNegotiationService`, `CareerAssessmentService`, `JobRecommendationService`, `SkillLearningPathService`
- ✅ Jobs: `GenerateJobRecommendations`（队列异步推荐）
- ✅ Console: `GenerateDailyRecommendations`（每日推荐调度）
- ✅ Views: `resources/views/user/` 全部视图已创建
- ✅ Views: `resources/views/admin/companies/` 管理后台视图
- ✅ Sidebar: 用户端与管理端导航已更新
- ✅ Config: `config/assessments/` 题库配置（MBTI/霍兰德/DISC）
- ✅ Quota: `QuotaService` 已扩展 5 个新功能配额键
- ✅ Seeder: `CompanyIntelligenceSeeder` + `AiPromptSeeder`（含 6 个新 Prompt）

---

## 历史阶段

### MVP 阶段（已完成）

- 简历管理（创建/编辑/导出/分享）
- AI 简历优化
- AI 模拟面试
- 岗位投递看板
- 会员订阅与积分体系
- Admin 管理后台基础功能
