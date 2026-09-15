# 队列 Worker 与 Supervisor 部署说明

本文用于生产环境部署 Laravel 队列消费进程，覆盖默认队列（`default`）、简历导出异步任务（`resume-export`）与面试评分队列（`interview-eval`）。
如果你不希望引入 Supervisor，可参考 `docs/DEPLOY-systemd队列Worker.md` 使用 systemd 原生方案。

## 1. 前置检查

- 确保已执行数据库迁移（包含 `jobs`、`resume_export_tasks`、`system_settings` 等相关迁移）：

```bash
php artisan migrate --force
```

- 确保 `.env` 关键参数已配置：

```dotenv
QUEUE_CONNECTION=database
RESUME_EXPORT_QUEUE=resume-export
RESUME_EXPORT_MAX_ATTEMPTS=2
RESUME_EXPORT_RETENTION_DAYS=7
INTERVIEW_MAX_QUESTIONS=5
INTERVIEW_SESSION_TIMEOUT_MINUTES=30
INTERVIEW_EVALUATION_QUEUE=interview-eval
INTERVIEW_EVALUATION_POLL_INTERVAL_MS=1200
INTERVIEW_EVALUATION_POLL_FAST_ATTEMPTS=3
INTERVIEW_EVALUATION_POLL_FAST_INTERVAL_MS=600
INTERVIEW_EVALUATION_POLL_SLOW_INTERVAL_MS=1300
INTERVIEW_EVALUATION_POLL_JITTER_MS=120
INTERVIEW_EVALUATION_POLL_REQUEST_TIMEOUT_MS=6000
INTERVIEW_EVALUATION_POLL_MAX_ATTEMPTS=30
INTERVIEW_EVALUATION_RESUME_POLL_DELAY_MS=15000
INTERVIEW_EVALUATION_PENDING_TIMEOUT_SECONDS=90
INTERVIEW_EVALUATION_RESULT_CACHE_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_FORCE_AI_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_TTL_SECONDS=900
INTERVIEW_EVALUATION_RESULT_CACHE_MAX_ANSWER_CHARS=3000
INTERVIEW_EVALUATION_HEAL_ENABLED=true
INTERVIEW_EVALUATION_HEAL_OLDER_SECONDS=90
INTERVIEW_EVALUATION_HEAL_LIMIT=100
INTERVIEW_EVALUATION_HEAL_FALLBACK_AFTER_SECONDS=240
INTERVIEW_EVALUATION_FAST_PATH_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_SHORT_ANSWER_CHARS=20
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ANSWER_CHARS=60
INTERVIEW_AI_FLUENCY_DETECTION_ENABLED=true
INTERVIEW_AI_DIALOGUE_DECISION_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_MAX_ANSWER_CHARS=90
INTERVIEW_AI_DIALOGUE_SYNC_LOW_QUALITY_ONLY=false
INTERVIEW_AI_DIALOGUE_MAX_FOLLOWUPS_PER_ANSWER=2
INTERVIEW_AI_EARLY_TERMINATION_ENABLED=true
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONSECUTIVE_LOW_ANSWERS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_ANSWERED_QUESTIONS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONFIDENCE=0.75
```

说明：

- 面试 AI 评分任务会投递到 `INTERVIEW_EVALUATION_QUEUE` 指定队列。
- 若你改为独立队列（例如 `interview-eval`），需确保 Worker 的 `--queue` 参数包含该队列名。
- `INTERVIEW_EVALUATION_RESULT_CACHE_*` 用于评估结果缓存，同题同答案命中可直接秒回，建议生产默认开启。
- `INTERVIEW_AI_DIALOGUE_SYNC_*` 用于控制同步对话决策触发范围，回答较长时建议走异步评分以降低首包耗时。

## 2. 安装 Supervisor

```bash
sudo apt update
sudo apt install -y supervisor
```

## 3. 安装 Worker 配置

项目已提供模板：

- `deploy/supervisor/laravel-worker.conf`

将配置复制到 Supervisor 目录：

```bash
sudo cp /www/wwwroot/49.232.223.126/deploy/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
```

## 4. 启动与生效

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

若需要手动重启：

```bash
sudo supervisorctl restart laravel-worker-default:*
sudo supervisorctl restart laravel-worker-resume-export:*
sudo supervisorctl restart laravel-worker-interview-eval:*
```

## 5. 发布流程建议（零停机）

每次发布后执行以下步骤：

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

说明：

- `queue:restart` 会优雅重启 Worker，不会立即中断正在处理的任务。
- 新配置生效建议结合 `supervisorctl restart` 使用。

## 6. 运行时观测

- Worker 状态：

```bash
sudo supervisorctl status
```

- 默认队列日志：

```bash
tail -f /www/wwwroot/49.232.223.126/storage/logs/worker-default.log
```

- 导出队列日志：

```bash
tail -f /www/wwwroot/49.232.223.126/storage/logs/worker-resume-export.log
```

- 面试评分队列日志：

```bash
tail -f /www/wwwroot/49.232.223.126/storage/logs/worker-interview-eval.log
```

- 应用日志（任务失败细节）：

```bash
tail -f /www/wwwroot/49.232.223.126/storage/logs/laravel.log
```

## 7. 故障排查

- 队列堆积：
  检查 `jobs` 表积压量、数据库性能、以及 `resume-export` Worker 是否存活。
- 导出任务长期 `processing`：
  检查 `supervisorctl status`、`worker-resume-export.log`、以及 `laravel.log` 中 `resume_export_task_failed` 日志。
- Worker 频繁重启：
  增加 `--timeout`、检查第三方 IO/磁盘空间、确认 PHP 内存限制。
- AI 评分长期停留在“评分中”：
  检查 `supervisorctl status` 与 `worker-interview-eval.log`，确认 `queue:work` 正在消费 `INTERVIEW_EVALUATION_QUEUE`。
  可手动执行一次自愈命令：

```bash
php artisan interview:evaluation:heal
```

## 8. 计划任务（必配）

为保证“待评分题目”可自动修复，请确保服务器已配置 Laravel Scheduler：

```bash
* * * * * cd /www/wwwroot/49.232.223.126 && php artisan schedule:run >> /dev/null 2>&1
```

说明：

- 系统每分钟自动执行 `interview:evaluation:heal`。
- 该任务会扫描长期待评分题目：先重试投递评分队列，超阈值后自动兜底评分，避免用户端长期卡在“AI评分中”。

## 9. 安全与权限建议

- Worker 运行用户使用最小权限账户（示例中为 `www`）。
- `storage/` 与 `bootstrap/cache/` 保持可写，其它目录只读优先。
- 不在配置文件中硬编码密钥，统一由 `.env` 管理。

## 10. 后台增强上线补充（2026-04）

- 本次后台增强新增“网站设置”功能，发布后请确认 `system_settings` 表已创建成功。
- 若在后台看板看不到“评分队列健康”或 AI 监控数据，优先执行：

```bash
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan queue:restart
```

- 若 `failed_jobs` 数值持续增长，请按以下顺序排查：
  1. `worker-interview-eval.log` 是否存在网络超时或第三方限流；
  2. `INTERVIEW_EVALUATION_QUEUE` 是否与 Worker `--queue` 参数一致；
  3. 是否需要增加 `laravel-worker-interview-eval` 进程数量。

## 11. 简历 PDF OCR 部署说明（导入扫描件必看）

当简历导入遇到“图片型 PDF / 扫描件 PDF”时，系统会优先尝试普通文本提取；若提取为空，再自动尝试 OCR。
当前代码的 OCR 依赖以下系统二进制：

- `tesseract`
- `pdftoppm` 或 `pdftocairo`（来自 `poppler-utils`）

### 11.1 Ubuntu / Debian 安装命令

```bash
sudo apt update
sudo apt install -y tesseract-ocr poppler-utils
```

如果你的简历主要是中文，建议同时安装中文语言包：

```bash
sudo apt install -y tesseract-ocr-chi-sim
```

### 11.2 安装后能力校验

```bash
which tesseract
which pdftoppm
which pdftocairo
tesseract --version
pdftoppm -v
```

至少需要满足：

- `tesseract` 可执行
- `pdftoppm` 或 `pdftocairo` 至少存在一个

### 11.3 Laravel 侧发布步骤

安装系统依赖后，建议执行：

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```

说明：

- OCR 导入目前在 Web 请求中同步执行，不依赖单独队列。
- 这里仍建议执行 `queue:restart`，保持发布后的运行环境一致。

### 11.4 导入验收步骤

准备两类样本文件各至少 1 份：

1. 可复制文本的普通 PDF
2. 图片扫描型 PDF

在简历编辑页执行以下验收：

1. 上传普通 PDF，确认能直接识别模块，并且导入预览里不显示 `OCR 兜底已启用`
2. 上传扫描件 PDF，确认预览里显示 `OCR 兜底已启用`
3. 检查预览中的模块数量、目标岗位、个人信息、经历条目是否合理
4. 点击确认导入，确认当前模块被正确覆盖，且旧的 ATS / 优化结果已重置

### 11.5 降级表现说明

若服务器未安装 OCR 依赖，系统行为如下：

- 普通 PDF：仍按原有文本提取流程导入
- 扫描件 PDF：前端预览会提示“扫描件且未启用 OCR”
- 若最终无法提取可用文本，接口会返回明确业务提示，不会静默覆盖原模块

### 11.6 常见问题

- 扫描件导入很慢：
  优先检查 PDF 页数、图片分辨率，以及服务器 CPU 使用率。
- OCR 可执行但仍无法识别中文：
  检查是否安装了 `tesseract-ocr-chi-sim`。
- 导入后字段错位：
  优先在导入预览中核对“字段级差异高亮”，必要时人工微调模块内容。

## 12. 生产参数模板（可直接复制）

以下给出两套推荐参数，你可按服务器规模与并发量二选一。

### 标准并发（推荐默认）

适用：单机或轻量集群，面试并发 20~80。

```dotenv
INTERVIEW_EVALUATION_QUEUE=interview-eval
INTERVIEW_EVALUATION_POLL_INTERVAL_MS=1200
INTERVIEW_EVALUATION_POLL_FAST_ATTEMPTS=3
INTERVIEW_EVALUATION_POLL_FAST_INTERVAL_MS=600
INTERVIEW_EVALUATION_POLL_SLOW_INTERVAL_MS=1300
INTERVIEW_EVALUATION_POLL_JITTER_MS=120
INTERVIEW_EVALUATION_POLL_REQUEST_TIMEOUT_MS=6000
INTERVIEW_EVALUATION_POLL_MAX_ATTEMPTS=30
INTERVIEW_EVALUATION_RESUME_POLL_DELAY_MS=15000
INTERVIEW_EVALUATION_PENDING_TIMEOUT_SECONDS=90

INTERVIEW_EVALUATION_RESULT_CACHE_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_FORCE_AI_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_TTL_SECONDS=900
INTERVIEW_EVALUATION_RESULT_CACHE_MAX_ANSWER_CHARS=3000

INTERVIEW_EVALUATION_FAST_PATH_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_SHORT_ANSWER_CHARS=20
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ANSWER_CHARS=60

INTERVIEW_AI_DIALOGUE_DECISION_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_MAX_ANSWER_CHARS=90
INTERVIEW_AI_DIALOGUE_SYNC_LOW_QUALITY_ONLY=false
INTERVIEW_AI_DIALOGUE_MAX_FOLLOWUPS_PER_ANSWER=2

INTERVIEW_AI_EARLY_TERMINATION_ENABLED=true
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONSECUTIVE_LOW_ANSWERS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_ANSWERED_QUESTIONS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONFIDENCE=0.75
```

### 高并发（性能优先）

适用：多机集群或峰值场景，面试并发 80+。

```dotenv
INTERVIEW_EVALUATION_QUEUE=interview-eval
INTERVIEW_EVALUATION_POLL_INTERVAL_MS=1000
INTERVIEW_EVALUATION_POLL_FAST_ATTEMPTS=2
INTERVIEW_EVALUATION_POLL_FAST_INTERVAL_MS=450
INTERVIEW_EVALUATION_POLL_SLOW_INTERVAL_MS=1200
INTERVIEW_EVALUATION_POLL_JITTER_MS=180
INTERVIEW_EVALUATION_POLL_REQUEST_TIMEOUT_MS=4500
INTERVIEW_EVALUATION_POLL_MAX_ATTEMPTS=24
INTERVIEW_EVALUATION_RESUME_POLL_DELAY_MS=12000
INTERVIEW_EVALUATION_PENDING_TIMEOUT_SECONDS=70

INTERVIEW_EVALUATION_RESULT_CACHE_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_FORCE_AI_ENABLED=true
INTERVIEW_EVALUATION_RESULT_CACHE_TTL_SECONDS=1800
INTERVIEW_EVALUATION_RESULT_CACHE_MAX_ANSWER_CHARS=3500

INTERVIEW_EVALUATION_FAST_PATH_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_SHORT_ANSWER_CHARS=26
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ENABLED=true
INTERVIEW_EVALUATION_FAST_PATH_MEDIUM_ANSWER_CHARS=90

INTERVIEW_AI_DIALOGUE_DECISION_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_ENABLED=true
INTERVIEW_AI_DIALOGUE_SYNC_MAX_ANSWER_CHARS=70
INTERVIEW_AI_DIALOGUE_SYNC_LOW_QUALITY_ONLY=true
INTERVIEW_AI_DIALOGUE_MAX_FOLLOWUPS_PER_ANSWER=2

INTERVIEW_AI_EARLY_TERMINATION_ENABLED=true
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONSECUTIVE_LOW_ANSWERS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_ANSWERED_QUESTIONS=2
INTERVIEW_AI_EARLY_TERMINATION_MIN_CONFIDENCE=0.80
```

### 套用后立即执行

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```
