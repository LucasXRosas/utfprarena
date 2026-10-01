FROM php:8.2-fpm-alpine

# Instala dependências do sistema necessárias para compilar as extensões do PostgreSQL e suporte ao Composer
RUN apk add --no-cache \
    postgresql-dev \
    git \
    unzip \
    curl \
    bash

# Instala e habilita extensões do PHP para conexão com o banco relacional
RUN docker-php-ext-install pdo pdo_pgsql

# Copia o binário do Composer mais recente diretamente da imagem oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho padrão do container
WORKDIR /var/www/html

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
