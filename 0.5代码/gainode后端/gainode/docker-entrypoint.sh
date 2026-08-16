#!/usr/bin/env bash
# Webman 容器启动脚本
set -e

cd "$(dirname "$0")"

# 若挂载了外部 .env 则优先使用（docker 已通过 -v 挂载时）
if [ -f ".env" ]; then
    echo "[entrypoint] 使用容器内 .env 配置"
fi

# 生产环境关闭文件监控，避免容器内 inotify 异常 / 重复 reload
export APP_PROCESS_LIST="${APP_PROCESS_LIST:-task_server,crontab_task,arb_task}"

echo "[entrypoint] 启动 webman ..."
echo "[entrypoint] APP_PROCESS_LIST=${APP_PROCESS_LIST}"

# 前台运行，便于容器管理进程生命周期
# -d 守护参数在容器里不使用，直接前台运行
exec php webman start
