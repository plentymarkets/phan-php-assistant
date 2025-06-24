FROM php:8.2.0-cli

RUN apt-get clean && apt-get update

RUN apt-get install -y --no-install-recommends \
	apt-transport-https \
	software-properties-common \
	curl \
	ca-certificates \
	wget \
	git \
	gcc \
	make \
    libmcrypt-dev \
    zlib1g-dev \
    libzip-dev \
    unzip \
    libxml2-dev \
    libmcrypt-dev \
    libmagickwand-dev \
    libc-client-dev \
    libkrb5-dev

RUN docker-php-ext-install soap
RUN docker-php-ext-install pcntl

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install -j$(nproc) imap

RUN pecl install mcrypt-1.0.6 && docker-php-ext-enable mcrypt
RUN pecl install imagick && docker-php-ext-enable imagick

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN pecl install ast
RUN echo "extension=ast.so" >> "$PHP_INI_DIR/php.ini"

COPY . /app
WORKDIR /app

RUN cd /app; \
 php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
 php composer-setup.php; \
 php -r "unlink('composer-setup.php');"; \
 php composer.phar install

ENV PHP_PATH=php
ENV PHAN_PATH=/../../vendor/phan/phan/phan
ENV COMPOSER_PATH=/../../composer.phar

CMD ["php", "artisan"]
