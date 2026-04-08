FROM richarvey/php-apache-heroku:latest

# Copy seluruh project ke folder /var/www/html
COPY . /var/www/html

# Set root folder ke public (khas Laravel)
ENV WEBROOT /var/www/html/public
ENV APP_ENV production

# Jalankan composer install
RUN composer install --no-dev --optimize-autoloader

# Beri izin folder storage
RUN chmod -R 777 storage bootstrap/cache