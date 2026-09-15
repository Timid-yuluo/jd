# 异步优化 Worker 自恢复与宝塔计划任务

## 适用范围

- 项目路径：`/www/wwwroot/124.221.19.20`
- PHP CLI：`/www/server/php/85/bin/php`
- 本地 supervisor 配置：`/www/wwwroot/124.221.19.20/deploy/supervisor/supervisord.conf`
- Worker PHP 覆盖配置：`/www/wwwroot/124.221.19.20/deploy/php/zz-worker-runtime.ini`

本文档用于说明如何在宝塔面板中为简历异步优化能力配置：

- Worker 自恢复
- Laravel Scheduler 兜底执行
- 健康检查与验收

## 当前架构

当前异步优化运行体系包含以下部分：

- `resume-optimize-default_*`：普通异步优化队列 worker
- `resume-optimize-priority_*`：高优先级异步优化队列 worker
- `resume-optimize-heavy_*`：重任务异步优化队列 worker
- `resume-optimize-scheduler`：本地 supervisor 托管的 `schedule:run`
- `resume:optimize:check-health`：异步优化健康巡检命令
- `resume:optimize:recycle-stale`：超时优化会话自动回收命令

## 项目内关键文件

- Worker 自恢复脚本：`/www/wwwroot/124.221.19.20/deploy/supervisor/ensure-workers.sh`
- Worker 启动脚本：`/www/wwwroot/124.221.19.20/deploy/supervisor/start-workers.sh`
- Worker 停止脚本：`/www/wwwroot/124.221.19.20/deploy/supervisor/stop-workers.sh`
- Scheduler 循环脚本：`/www/wwwroot/124.221.19.20/deploy/supervisor/run-scheduler.sh`
- Supervisor 主配置：`/www/wwwroot/124.221.19.20/deploy/supervisor/supervisord.conf`
- Worker 进程配置：`/www/wwwroot/124.221.19.20/deploy/supervisor/resume-optimize-workers.conf`
- Worker PHP 运行时覆盖：`/www/wwwroot/124.221.19.20/deploy/php/zz-worker-runtime.ini`

## 为什么还需要宝塔计划任务

虽然项目已经通过本地 `supervisord` 托管了 worker 和 scheduler，但仍建议在宝塔中保留 2 条计划任务，原因如下：

- 当本地 `supervisord` 进程异常退出时，宝塔任务可以自动重新拉起
- 当 `resume-optimize-scheduler` 进程异常时，宝塔任务仍可兜底执行 `schedule:run`
- 这样可以形成“双保险”，降低线上异步优化彻底停摆的概率

## 宝塔计划任务配置

建议最终保留 2 条任务。

### 任务 1：异步优化 Worker 自恢复

- 名称：`异步优化Worker自恢复`
- 类型：`Shell脚本`
- 执行周期：`N分钟`
- 周期值：`1`
- 开启进程锁：`是`
- 脚本内容：

```bash
/www/wwwroot/124.221.19.20/deploy/supervisor/ensure-workers.sh
```

作用：

- 每分钟检查本地 `supervisord` 是否可用
- 如果 worker 还在运行，则直接退出
- 如果 `supervisord` 或 worker 已异常退出，则重新启动整套异步优化运行体系

### 任务 2：异步优化 Scheduler 兜底

- 名称：`异步优化Scheduler兜底`
- 类型：`Shell脚本`
- 执行周期：`N分钟`
- 周期值：`1`
- 开启进程锁：`是`
- 脚本内容：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan schedule:run
```

作用：

- 每分钟兜底执行 Laravel Scheduler
- 即使本地 `resume-optimize-scheduler` 进程异常，也能继续执行：
  - `resume:optimize:check-health`
  - `resume:optimize:recycle-stale --limit=100`

## 宝塔面板操作步骤

### 新增任务 1

在宝塔面板进入 `计划任务`，按以下信息填写：

- 任务类型：`Shell脚本`
- 任务名称：`异步优化Worker自恢复`
- 执行周期：`N分钟`
- 分钟：`1`
- 脚本内容：

```bash
/www/wwwroot/124.221.19.20/deploy/supervisor/ensure-workers.sh
```

- 勾选：`进程锁`

保存后，点击一次 `执行任务`。

### 新增任务 2

在宝塔面板进入 `计划任务`，按以下信息填写：

- 任务类型：`Shell脚本`
- 任务名称：`异步优化Scheduler兜底`
- 执行周期：`N分钟`
- 分钟：`1`
- 脚本内容：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan schedule:run
```

- 勾选：`进程锁`

保存后，点击一次 `执行任务`。

## 终端验收步骤

### 1. 检查 worker 状态

执行：

```bash
/www/server/panel/pyenv/bin/supervisorctl -c /www/wwwroot/124.221.19.20/deploy/supervisor/supervisord.conf status
```

预期至少看到以下进程：

```text
resume-optimize-default_00   RUNNING
resume-optimize-default_01   RUNNING
resume-optimize-priority_00  RUNNING
resume-optimize-priority_01  RUNNING
resume-optimize-heavy_00     RUNNING
resume-optimize-scheduler    RUNNING
```

### 2. 检查异步优化健康状态

执行：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan resume:optimize:check-health --json
```

重点关注返回值中的：

- `"healthy": true`
- `"stale_pending_count": 0`
- `"redis_extension_loaded": true`
- `"pcntl_signal_available": true`
- `"proc_open_available": true`

### 3. 检查宝塔任务日志

在宝塔计划任务列表中查看日志，预期应出现：

- `Successful`

不应出现以下错误：

- `Class "Redis" not found`
- `Call to undefined function pcntl_signal()`
- `Call to undefined function pcntl_alarm()`
- `proc_open has been disabled`

## 常用运维命令

### 手动启动 worker

```bash
/www/wwwroot/124.221.19.20/deploy/supervisor/start-workers.sh
```

### 手动停止 worker

```bash
/www/wwwroot/124.221.19.20/deploy/supervisor/stop-workers.sh
```

### 查看 worker 状态

```bash
/www/server/panel/pyenv/bin/supervisorctl -c /www/wwwroot/124.221.19.20/deploy/supervisor/supervisord.conf status
```

### 重启 Laravel 队列

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan queue:restart
```

### 干跑检查超时会话

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan resume:optimize:recycle-stale --dry-run --limit=100
```

## 故障排查

### 情况 1：宝塔任务显示 Successful，但 worker 没起来

优先执行：

```bash
/www/server/panel/pyenv/bin/supervisorctl -c /www/wwwroot/124.221.19.20/deploy/supervisor/supervisord.conf status
```

如果连接失败，再执行：

```bash
/www/wwwroot/124.221.19.20/deploy/supervisor/start-workers.sh
```

然后重新检查状态。

### 情况 2：健康检查返回 unhealthy

执行：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan resume:optimize:check-health --json
```

重点看：

- `stale_pending_count`
- `recent_queue_failures`
- `queue_depths`
- `worker_runtime`

### 情况 3：发现历史卡住会话持续增长

先干跑：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan resume:optimize:recycle-stale --dry-run --limit=100
```

确认无误后执行：

```bash
PHP_INI_SCAN_DIR=/www/wwwroot/124.221.19.20/deploy/php \
/www/server/php/85/bin/php -c /www/server/php/85/etc/php-cli.ini \
/www/wwwroot/124.221.19.20/artisan resume:optimize:recycle-stale --limit=100
```

## 推荐结论

线上环境建议长期保留以下三层保障：

- 本地 `supervisord` 托管 worker
- 本地 `supervisord` 托管 scheduler
- 宝塔计划任务每分钟兜底检查和拉起

这套方案的目标不是让异步优化“永不失败”，而是让它在出现异常时：

- 更快恢复
- 不容易堆积脏会话
- 更少打扰运营
