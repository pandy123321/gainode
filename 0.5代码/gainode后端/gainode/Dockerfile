# Webman (Workerman) PHP 容器镜像
# 适用于 CrmProject/server —— 常驻进程服务，PHP >= 8.1
FROM php:8.2-cli

# 时区 / 系统基础工具
ENV TZ=Asia/Shanghai \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8

RUN set -eux; \
    apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        ca-certificates \
        libzip-dev \
        zlib1g-dev \
        libssl-dev \
    && rm -rf /var/lib/apt/lists/* \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# PHP 扩展：webman 必需 + 项目依赖
# pcntl/posix 为 Workerman 必需；pdo_mysql/redis/bcmath/mysqli 为业务必需
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" \
        pcntl \
        posix \
        pdo_mysql \
        mysqli \
        bcmath \
        sockets \
        zip \
        opcache \
    && pecl install redis && docker-php-ext-enable redis

# 可选：event 扩展（Workerman 高性能事件循环，非必需）
RUN set -eux; \
    pecl install event && docker-php-ext-enable event || true

WORKDIR /var/www

# 先拷依赖清单，利用层缓存（仅在 composer 文件变化时重装）
COPY composer.json composer.lock ./

# 复制项目源码（vendor 已随仓库，直接拷贝，免去联网 composer install）
COPY . .

# 加载自定义 php.ini
COPY php.ini /usr/local/etc/php/conf.d/zz-webman.ini

# 运行时目录权限（webman 需要 runtime 可写）
RUN set -eux; \
    mkdir -p runtime/logs runtime/cache && \
    chmod -R 777 runtime && \
    chmod +x webman docker-entrypoint.sh && \
    php -v

# 环境变量（可被 docker run -e 覆盖，也可挂载 .env 文件）
ENV APP_LISTEN="http://0.0.0.0:8787" \
    APP_ENV="production" \
    APP_DEBUG="false"

# 暴露 webman 及相关进程端口（按需开启）
EXPOSE 8787 8786 8686 2206 8989 8888

# 健康检查：探测主服务端口
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8787/ || exit 1

ENTRYPOINT ["./docker-entrypoint.sh"]
