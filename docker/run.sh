#!/bin/bash
export $(grep -v '^#' .env | xargs)
    
# Проверка статуса MySQL сервера
if ! service mysql status > /dev/null 2>&1; then
    # Запуск MySQL сервера, если не запущен
    service mysql start
    mysql hestia_helper < /docker-entrypoint-initdb.d/init-db.sql
    mysql -u root --execute="ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';"
else
    echo "MySQL сервер уже запущен."
fi

# Проверка статуса Apache
if ! pgrep -x "apache2" > /dev/null
then
    echo "Запуск Apache сервера..."
    apachectl -D FOREGROUND
else
    echo "Apache сервер уже запущен."
fi
