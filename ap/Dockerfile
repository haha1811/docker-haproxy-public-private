FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

# 安裝 Apache、PHP 7.4 相關套件
RUN apt-get update && \
    apt-get install -y \
        openssh-client \
        apache2 \
        vim \
        net-tools \
        mysql-client \
        php7.4 \
        libapache2-mod-php7.4 \
        php7.4-mysql && \
    a2enmod php7.4 && \
    rm -rf /var/lib/apt/lists/*

# 放首頁 index.php
COPY *.php /var/www/html/


# Apache 前景啟動
EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
