FROM ubuntu:24.04

# Установка необходимых пакетов и обновление системы
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    libapache2-mod-php \
    php-mysql \
    mysql-server \
    php-curl \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod ssl
COPY apache2.conf /etc/apache2/apache2.conf
RUN mkdir -p /etc/apache2/conf.d/ && mkdir -p /etc/apache2/conf.d/domains/
COPY note.stacksite.conf /etc/apache2/conf.d/domains/

RUN mkdir -p /var/lib/mysql && chown -R mysql:mysql /var/lib/mysql
RUN usermod -d /var/lib/mysql mysql

# Копирование SQL файла для инициализации базы данных
COPY init-db.sql /docker-entrypoint-initdb.d/
COPY backup.sql /docker-entrypoint-initdb.d/
COPY .env .env

# Инициализация базы данных
RUN service mysql start \
    && mysql --default-character-set=utf8mb4 < /docker-entrypoint-initdb.d/init-db.sql \
    && mysql --default-character-set=utf8mb4 < /docker-entrypoint-initdb.d/backup.sql

ADD run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 80

CMD ["/run.sh"]
