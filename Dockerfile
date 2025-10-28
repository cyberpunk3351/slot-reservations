# Используем официальный образ PHP 8.2 FPM
FROM php:8.2-fpm

ARG UID=1000
ARG GID=1000

USER root

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN if getent group ${GID} >/dev/null; then \
      useradd -m -u ${UID} -g $(getent group ${GID} | cut -d: -f1) -s /bin/bash www; \
    else \
      groupadd -g ${GID} www && \
      useradd -m -u ${UID} -g www -s /bin/bash www; \
    fi

WORKDIR /var/www

COPY . /var/www
RUN chown -R www:www /var/www

RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist

USER www

EXPOSE 9000
CMD ["php-fpm"]
