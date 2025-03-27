FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    zip unzip git curl \
    cron \
    && docker-php-ext-install intl pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Symfony dependencies
RUN composer install --optimize-autoloader

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

RUN echo "0 * * * * root php /var/www/html/bin/console app:sync-recipes >> /var/log/cron.log 2>&1" >> /etc/crontab

# Ensure proper permissions for the crontab file
RUN chmod 0644 /etc/crontab

RUN touch /var/log/cron.log

CMD cron -f && php bin/console doctrine:migrations:migrate && php bin/console app:sync-recipes && php-fpm
