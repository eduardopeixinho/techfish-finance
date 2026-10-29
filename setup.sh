#!/bin/bash

# Setup inicial do TechFish Finance com Docker

set -e

echo "🐟 TechFish Finance - Setup Inicial"
echo "============================"
echo ""

# Verifica se o Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não encontrado. Instale o Docker primeiro."
    exit 1
fi

# Docker Compose v2 ("docker compose"); cai para o v1 ("docker-compose") se for o único disponível
if docker compose version &> /dev/null; then
    DC="docker compose"
elif command -v docker-compose &> /dev/null; then
    DC="docker-compose"
else
    echo "❌ Docker Compose não encontrado. Instale o plugin 'docker compose'."
    exit 1
fi

# Copia .env.example para .env se não existir
if [ ! -f .env ]; then
    echo "📝 Criando arquivo .env..."
    cp .env.example .env
    echo "✅ Arquivo .env criado!"
else
    echo "✅ Arquivo .env já existe"
fi

# Gera APP_KEY se não existir
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Gerando APP_KEY..."
    APP_KEY="base64:$(openssl rand -base64 32)"
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    echo "✅ APP_KEY gerada!"
else
    echo "✅ APP_KEY já configurada"
fi

# Build e start dos containers
echo ""
echo "🔨 Construindo containers Docker..."
$DC build

echo ""
echo "🚀 Iniciando containers..."
$DC up -d
# Espera o app (que depende de db/redis saudáveis)
if [ "$DC" = "docker compose" ]; then
    $DC up -d --wait app
else
    sleep 10
fi

# Instala dependências do Composer
echo ""
echo "📦 Instalando dependências Composer..."
$DC exec -T app composer install --no-interaction --optimize-autoloader

# Assets: o container não tem Node. public/build já vem versionado no repositório
# (é o que vai para produção); só recompila se houver npm na máquina.
echo ""
if command -v npm &> /dev/null; then
    echo "🎨 Compilando assets com o npm da máquina..."
    npm ci
    npm run build
else
    echo "🎨 npm não encontrado na máquina: usando os assets já compilados em public/build."
fi

# Executa migrations
echo ""
echo "🗄️  Executando migrations..."
$DC exec -T app php artisan migrate --force

# Seed do banco (roles, permissions, types)
echo ""
echo "🌱 Executando seeders..."
$DC exec -T app php artisan db:seed --class=RolePermissionSeeder --force
$DC exec -T app php artisan db:seed --class=InvestmentTypeSeeder --force
$DC exec -T app php artisan db:seed --class=SettingsSeeder --force

# Conta de demonstração com dados fictícios (só com DEMO_MODE=true)
if grep -q "^DEMO_MODE=true" .env; then
    echo "🎭 Criando a conta de demonstração..."
    $DC exec -T app php artisan db:seed --class=DemoSeeder --force
fi

# Limpa caches
echo ""
echo "🧹 Limpando caches..."
$DC exec -T app php artisan config:clear
$DC exec -T app php artisan cache:clear
$DC exec -T app php artisan view:clear

echo ""
echo "============================================"
echo "✅ TechFish Finance configurado com sucesso!"
echo "============================================"
echo ""
echo "🌐 Aplicação:    http://localhost:8000"
echo "📊 phpMyAdmin:   http://localhost:8081"
echo ""
echo "🎭 Sem credenciais do Google? Use o botão \"Ver demonstração\" (DEMO_MODE=true)."
echo ""
echo "👤 Com Google: entre uma vez e rode"
echo "   $DC exec app php artisan db:seed --class=RolePermissionSeeder"
echo "   para o seu usuário virar admin."
echo ""
echo "📋 Comandos úteis:"
echo "  $DC logs -f app        # Ver logs da aplicação"
echo "  $DC exec app bash      # Acessar container"
echo "  $DC exec app php artisan test   # Testes (SQLite em memória, não toca no banco local)"
echo "  $DC restart            # Reiniciar containers"
echo "  $DC down               # Parar containers"
echo ""
