FROM php:8.2-cli

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY . /app

WORKDIR /app

RUN apt-get update -y \ 
    && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip \
    && composer install --dev

CMD ["php", "-a"]