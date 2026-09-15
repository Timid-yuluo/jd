# 应届生AI求职助手 — MVP 全栈实施总文档

> 版本：v1.0  
> 适用阶段：MVP（uni-app 微信小程序优先，后续扩展 H5 / PC）  
> 适用团队：后端（Laravel 13）+ 前端（uni-app）+ 测试

---

## 1. 文档说明

本文件为以下 6 份文档的合并版（统一结构、去重整合）：

- `uni-app-页面接口映射表（小程序）.md`
- `uni-app-小程序初始化任务清单.md`
- `uni-app-跨端开发红线清单.md`
- `MVP-接口清单（Laravel13）.md`
- `Laravel13-项目初始化任务清单.md`
- `接口Mock数据规范与联调检查清单.md`

目标是形成“一份即可开工”的执行文档，供前后端与测试并行推进。

---

## 2. 技术基线与范围

### 2.1 技术基线

- 后端：`Laravel 13` + `PHP 8.3+` + `MySQL 8` + `Redis` + `Sanctum`
- 前端：`uni-app` + `Vue3` + `TypeScript` + `Pinia` + `Vite`
- 接口：`/api/v1` + Bearer Token + 统一响应结构

### 2.2 架构基线

- 后端分层：`Controller -> Request -> Service -> Action -> Repository -> Model`
- 前端分层：`Page -> Component -> Composable -> Store -> API`

### 2.3 MVP 模块范围

- `Auth`（登录/登出/用户信息）
- `Resumes`（简历 CRUD、优化、ATS）
- `Interviews`（会话、答题、报告）
- `Kanban`（投递看板、状态流转、统计）
- `Mine`（用户基础信息与退出）

---

## 3. API 统一规范（后端与前端共识）

### 3.1 基本约定

- 前缀：`/api/v1`
- 鉴权：`Authorization: Bearer {token}`
- 类型：`Content-Type: application/json`

### 3.2 通用响应

成功：

```json
{
  "code": 0,
  "message": "ok",
  "data": {},
  "meta": {}
}
```

失败：

```json
{
  "code": 10001,
  "message": "参数校验失败",
  "errors": [
    {
      "field": "phone",
      "message": "手机号格式不正确"
    }
  ],
  "trace_id": "req_20260422_xxx"
}
```

### 3.3 分页规范

- 请求：`page[number]`、`page[size]`
- 响应：`meta.pagination`

### 3.4 错误码（MVP）

| code | 含义 |
|------|------|
| 0 | 成功 |
| 10001 | 参数校验失败 |
| 10002 | 未登录或 token 无效 |
| 10003 | 无权限访问 |
| 10004 | 资源不存在 |
| 10005 | 请求过于频繁（限流） |
| 20001 | AI 服务繁忙，请稍后重试 |
| 20002 | AI 结果解析失败 |
| 30001 | 业务状态不允许（如面试已结束） |
| 50000 | 系统内部错误 |

---

## 4. MVP 接口清单（最终对齐版）

### 4.1 Auth

- `POST /api/v1/auth/refresh`（微信登录已下线）
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`

### 4.2 Resumes

- `POST /api/v1/resumes`
- `GET /api/v1/resumes`
- `GET /api/v1/resumes/{id}`
- `PUT /api/v1/resumes/{id}`
- `DELETE /api/v1/resumes/{id}`
- `POST /api/v1/resumes/import`
- `POST /api/v1/resumes/{id}/optimize`
- `POST /api/v1/resumes/{id}/ats-score`

### 4.3 Interviews

- `POST /api/v1/interviews`
- `GET /api/v1/interviews`
- `GET /api/v1/interviews/{id}`
- `GET /api/v1/interviews/{id}/current-question`
- `POST /api/v1/interviews/{id}/submit-answer`
- `POST /api/v1/interviews/{id}/finish`
- `GET /api/v1/interviews/{id}/report`

### 4.4 Kanban

- `GET /api/v1/kanban`
- `POST /api/v1/kanban`
- `PUT /api/v1/kanban/{id}`
- `PATCH /api/v1/kanban/{id}/status`
- `DELETE /api/v1/kanban/{id}`
- `GET /api/v1/kanban/statistics`

---

## 5. Laravel 13 初始化执行清单

### 5.1 目录建议

```text
app/
  Http/
    Controllers/Api/V1/
    Requests/Api/V1/
    Resources/Api/V1/
  Application/
    Services/
    Actions/
  Domain/
    Auth/
    Resume/
    Interview/
    Kanban/
  Infrastructure/
    Repositories/
    AI/
routes/
  api_v1.php
tests/
  Feature/Api/V1/
```

### 5.2 首批依赖

```bash
composer require laravel/sanctum
composer require predis/predis
composer require spatie/laravel-permission
composer require spatie/laravel-query-builder
composer require dedoc/scramble --dev
composer require larastan/larastan --dev
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
composer require laravel/pint --dev
composer require nunomaduro/collision --dev
```

### 5.3 初始化命令

```bash
php artisan install:api
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
php artisan make:middleware ForceJsonResponse
php artisan make:middleware RequestIdMiddleware
```

### 5.4 首批 Migration

1. `users`
2. `personal_access_tokens`
3. `resumes`
4. `interview_sessions`
5. `interview_questions`
6. `job_applications`
7. `usage_logs`

### 5.5 路由骨架

`routes/api_v1.php`：

```php
Route::prefix('v1')->group(function () {
    // 微信登录已下线，改为站内账号体系与令牌刷新接口

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('resumes', ResumeController::class);
        Route::post('/resumes/{resume}/optimize', [ResumeAiController::class, 'optimize']);
        Route::post('/resumes/{resume}/ats-score', [ResumeAiController::class, 'atsScore']);

        Route::apiResource('interviews', InterviewController::class)->only(['index', 'store', 'show']);
        Route::get('/interviews/{interview}/current-question', [InterviewController::class, 'currentQuestion']);
        Route::post('/interviews/{interview}/submit-answer', [InterviewController::class, 'submitAnswer']);
        Route::post('/interviews/{interview}/finish', [InterviewController::class, 'finish']);
        Route::get('/interviews/{interview}/report', [InterviewController::class, 'report']);

        Route::get('/kanban', [KanbanController::class, 'index']);
        Route::post('/kanban', [KanbanController::class, 'store']);
        Route::put('/kanban/{jobApplication}', [KanbanController::class, 'update']);
        Route::patch('/kanban/{jobApplication}/status', [KanbanController::class, 'updateStatus']);
        Route::delete('/kanban/{jobApplication}', [KanbanController::class, 'destroy']);
        Route::get('/kanban/statistics', [KanbanController::class, 'statistics']);
    });
});
```

---

## 6. uni-app 初始化执行清单

### 6.1 目录建议

```text
src/
  pages/
    auth/
    home/
    resume/
    interview/
    kanban/
    mine/
  components/
    common/
    biz/
  composables/
  stores/
  api/
  adapters/
  constants/
  utils/
  types/
```

### 6.2 初始化命令与依赖

```bash
npm create vite@latest job-assistant-mini -- --template vue-ts
npm i pinia @dcloudio/uni-app dayjs zod lodash-es
npm i -D @types/lodash-es eslint prettier eslint-config-prettier eslint-plugin-vue @vue/eslint-config-typescript
npm i -D husky lint-staged
npx husky init
```

### 6.3 package scripts 建议

```json
{
  "scripts": {
    "dev:mp-weixin": "uni -p mp-weixin",
    "build:mp-weixin": "uni build -p mp-weixin",
    "lint": "eslint . --ext .ts,.vue",
    "format": "prettier --write .",
    "type-check": "vue-tsc --noEmit"
  }
}
```

### 6.4 页面骨架（12 个）

1. `pages/auth/login`
2. `pages/home/index`
3. `pages/resume/list`
4. `pages/resume/edit`
5. `pages/resume/optimize`
6. `pages/resume/ats-report`
7. `pages/interview/create`
8. `pages/interview/session`
9. `pages/interview/report`
10. `pages/kanban/index`
11. `pages/kanban/edit`
12. `pages/mine/index`

---

## 7. 页面与接口映射（前端联调主表）

| 页面 | 核心接口 | 关键交互 |
|------|----------|----------|
| `pages/auth/login` | `POST /api/v1/auth/refresh` | 使用现有令牌刷新会话 |
| `pages/resume/list` | `GET/POST/DELETE /resumes` | 刷新分页、创建、删除 |
| `pages/resume/edit` | `GET/PUT /resumes/{id}` | 草稿缓存、未保存拦截 |
| `pages/resume/optimize` | `POST /resumes/{id}/optimize` | 展示前后对比、一键应用 |
| `pages/resume/ats-report` | `POST /resumes/{id}/ats-score` | 展示总分与维度分 |
| `pages/interview/create` | `POST /interviews` | 创建后进入会话页 |
| `pages/interview/session` | `GET current-question` / `POST submit-answer` / `POST finish` | 防重提交、AI点评loading |
| `pages/interview/report` | `GET /interviews/{id}/report` | 展示复盘报告 |
| `pages/kanban/index` | `GET /kanban` / `PATCH status` / `GET statistics` | 状态流转与统计刷新 |
| `pages/kanban/edit` | `POST/PUT /kanban` | 新增与编辑投递 |
| `pages/mine/index` | `GET /auth/me` / `POST /auth/logout` | 退出后清空登录态 |

---

## 8. 跨端开发红线（强制）

1. 禁止业务代码直接调用 `wx.*` / `my.*`。  
2. 禁止平台判断散落页面，条件编译集中在 `adapters/*`。  
3. 禁止接口返回仅小程序可用字段且无通用替代。  
4. 禁止页面层依赖 snake_case 原始字段。  
5. 禁止样式只适配小程序触屏，不考虑 H5/PC。  
6. 禁止登录态存取分散在页面。  
7. 禁止无替代方案地深度绑定小程序专属组件。  
8. 禁止上传/预览仅验证单端行为。  
9. 禁止把复杂对象塞路由 query 传递。  
10. 禁止忽略 SEO 边界（uni-app H5 不等价 SSR）。  

---

## 9. Mock 规范与联调策略

### 9.1 Mock 命名

- `{module}.{action}.{scenario}.json`
- 示例：`resume.optimize.ai_busy.json`

### 9.2 最小样本要求

- 每个接口至少 3 类：成功、业务失败、异常场景（限流/AI 繁忙）

### 9.3 联调阶段

- `phase-a`：纯 Mock
- `phase-b`：半联调（登录/列表真实，AI 类 Mock）
- `phase-c`：全联调（真实接口，异常场景保留 Mock 回归）

### 9.4 三方检查清单

- 后端：路由/方法/鉴权一致，错误码稳定，trace_id 可观测，限流可验证
- 前端：统一错误处理，loading/空态/错误态齐全，防重复提交，埋点完整
- 测试：覆盖成功+参数错+未授权+限流+AI 繁忙+弱网+幂等

---

## 10. 里程碑与排期（首周）

### Day 1

- 后端：项目骨架、依赖、中间件、路由占位、Auth/Resume 空实现
- 前端：工程骨架、请求层、鉴权、登录/首页/我的

### Day 2

- 后端：`Resumes` CRUD、`Kanban` CRUD + status
- 前端：简历列表/编辑、看板列表/编辑联调

### Day 3

- 后端：`Interviews` 会话/答题/报告骨架、AI Provider 占位
- 前端：简历优化、ATS 报告（先 Mock）

### Day 4

- 后端：AI 核心接口联调，限流与日志
- 前端：面试创建/会话/报告联调（先 Mock 后真实）

### Day 5

- 前后端测试回归、缺陷修复、输出接口变更记录

---

## 11. 验收门槛（统一）

### 11.1 后端

- `php artisan test` 全绿
- `./vendor/bin/pint --test` 通过
- `./vendor/bin/phpstan analyse` 无阻断级错误
- 核心接口具备成功/参数错/未授权测试

### 11.2 前端

- `npm run lint` 通过
- `npm run type-check` 通过
- 12 个 MVP 页面可访问无阻断
- 3 条核心链路跑通：
- 登录 -> 简历编辑 -> AI 优化
- 创建面试 -> 提交答案 -> 查看报告
- 新增投递 -> 更新状态 -> 查看统计

---

## 12. 最终交付物（MVP 启动）

- 可运行 Laravel 13 项目骨架与核心 migration
- 可运行 uni-app 小程序骨架与 12 页面路由
- 统一接口规范（前缀/响应/错误码/分页）
- 页面-接口映射主表
- 跨端红线与 PR Checklist
- Mock 规范、联调分阶段策略、冒烟用例清单

