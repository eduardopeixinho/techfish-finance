FROM php:8.4-apache

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        xml \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita módulos do Apache necessários
RUN a2enmod rewrite headers

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia configuração do Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copia configuração PHP personalizada
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copia o script de entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Define permissões iniciais
RUN chown -R www-data:www-data /var/www/html

# Expõe a porta 80
EXPOSE 80

# Define o entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Comando padrão para iniciar o Apache
CMD ["apache2-foreground"]
