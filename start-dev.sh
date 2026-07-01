#!/bin/bash
# 启动青园营地本地开发环境
# 用法: bash ./start-dev.sh

PROJECT_DIR="/Applications/phpstudy/WWW/shop.auba.cn"
MYSQL_DIR="/Applications/phpstudy/Extensions/MySQL5.7.28"
PHP_PORT=9000
MYSQL_PORT=3307

echo "🚀 启动青园营地本地开发环境..."

# 1. 启动 MySQL（如果未运行）
if lsof -i :$MYSQL_PORT > /dev/null 2>&1; then
    echo "  ✅ MySQL 已在端口 $MYSQL_PORT 运行"
else
    echo "  🔧 启动 MySQL ($MYSQL_PORT)..."
    rm -f /tmp/mysql.sock.lock 2>/dev/null
    $MYSQL_DIR/bin/mysqld_safe \
        --defaults-file=$MYSQL_DIR/my.cnf \
        --socket=/tmp/mysql_3307.sock \
        --port=$MYSQL_PORT > /dev/null 2>&1 &
    sleep 2
    if lsof -i :$MYSQL_PORT > /dev/null 2>&1; then
        echo "  ✅ MySQL 已启动"
    else
        echo "  ❌ MySQL 启动失败，检查 $MYSQL_DIR/data.err"
        exit 1
    fi
fi

# 2. 启动 PHP 内置服务器（如果未运行）
if lsof -i :$PHP_PORT > /dev/null 2>&1; then
    echo "  ✅ PHP 已在端口 $PHP_PORT 运行"
else
    echo "  🔧 启动 PHP 内置服务器 ($PHP_PORT)..."
    php -S 127.0.0.1:$PHP_PORT -t "$PROJECT_DIR" -c "$PROJECT_DIR/php.ini" > /dev/null 2>&1 &
    PHP_PID=$!
    sleep 1
    if lsof -i :$PHP_PORT > /dev/null 2>&1; then
        echo "  ✅ PHP 内置服务器已启动 (PID: $PHP_PID)"
    else
        echo "  ❌ PHP 启动失败"
        exit 1
    fi
fi

echo ""
echo "🌐 访问地址:"
echo "    http://127.0.0.1:$PHP_PORT/admin/"
echo "    http://127.0.0.1:$PHP_PORT/admin/member/member_points.php"
echo ""
echo "  关闭: pkill -f 'php -S 127.0.0.1:$PHP_PORT' && $MYSQL_DIR/bin/mysqladmin -h 127.0.0.1 -P $MYSQL_PORT -u root shutdown"
echo "✅ 启动完成"
