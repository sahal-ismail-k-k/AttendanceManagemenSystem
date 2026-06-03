FROM php:8.2-apache

# Install SQLite dependencies and extensions
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy all project files to Apache's default web directory
COPY . /var/www/html/

# Set the correct permissions so PHP/Apache can write to SQLite database and upload directories
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache default)
EXPOSE 80
