#!/bin/bash

set -e

echo "🚀 Iniciando TechFish Finance..."

# Aguarda o banco de dados estar pronto
echo "⏳ Aguardando banco de dados..."
MAX_RETRIES=30
RETRY_COUNT=0
until php -r "
    \$host = getenv('DB_HOST') ?: 'db';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db = getenv('DB_DATABASE') ?: 'techfish_finance';
    \$user = getenv('DB_USERNAME') ?: 'techfish';
    \$pass = getenv('DB_PASSWORD') ?: 'secret';
    try {
        new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
        exit(0);
    } catch (PDOException \$e) {
        exit(1);
    }
" 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "❌ Timeout: banco de dados não disponível após $MAX_RETRIES tentativas"
        exit 1
    fi
    echo "  Banco não disponível, aguardando... ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

echo "✅ Banco de dados pronto!"

# Define permissões corretas
echo "🔧 Configurando permissões..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Instala dependências do Composer se necessário
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "📦 Instalando dependências Composer..."
    composer install --no-interaction --optimize-autoloader
fi

# Cria .env se não existir
if [ ! -f /var/www/html/.env ]; then
    echo "📝 Criando .env..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Gera APP_KEY se não existir no .env
if ! grep -q "APP_KEY=base64:" /var/www/html/.env 2>/dev/null; then
    echo "🔑 Gerando APP_KEY..."
    php artisan key:generate --force --no-interaction 2>/dev/null || {
        # Fallback: gera manualmente
        APP_KEY="base64:$(openssl rand -base64 32)"
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" /var/www/html/.env
        echo "  APP_KEY gerada manualmente"
    }
fi

# Executa migrations
if [ -f /var/www/html/artisan ] && [ -f /var/www/html/vendor/autoload.php ]; then
    echo "📦 Executando migrations..."
    php artisan migrate --force --no-interaction 2>/dev/null || {
        echo "⚠️  Migrations já executadas ou erro ao executar"
    }

    # Limpa caches
    echo "🧹 Limpando caches..."
    php artisan config:clear --no-interaction 2>/dev/null || true
    php artisan cache:clear --no-interaction 2>/dev/null || true
    php artisan view:clear --no-interaction 2>/dev/null || true
fi

echo "✅ TechFish Finance pronto!"
echo "🌐 Acesse: http://localhost:${APP_PORT:-80}"

# Executa o comando passado
exec "$@"
