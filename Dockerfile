FROM php:8.2-apache

# Install mysqli extension (required for database connection)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy all project files to Apache web directory
COPY . /var/www/html/

# Enable Apache rewrite module
RUN a2enmod rewrite

# Expose port 80 (Render binds to this automatically)
EXPOSE 80
