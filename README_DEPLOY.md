# 🚀 Deploy F2B API - Laravel 10

## ✅ Status Atual
- ✅ MongoDB temporariamente comentado
- ✅ Configurado para MySQL + Redis
- ✅ Docker build testado e funcionando
- ✅ Otimizado para produção

## 🐳 Dokploy Deploy

### Pré-requisitos
- Dokploy configurado e funcionando
- Traefik configurado
- Rede `traefik` criada

### Variáveis de Ambiente Necessárias
```bash
# Banco de dados MySQL
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=f2b-mysql
DB_USERNAME=admin
DB_PASSWORD=476de5e30f4c3efffd73879125503321

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### Build Status
✅ **Docker Build**: Funcionando
✅ **Composer Install**: Sem erros
✅ **Dependências**: Otimizadas
✅ **Extensões PHP**: Redis habilitado

## 🔄 Para Reativar MongoDB (Futuro)

### 1. Dockerfile
Descomente as linhas:
```dockerfile
# && pecl install -o -f mongodb 2>/dev/null \
# && docker-php-ext-enable mongodb
```

### 2. docker-compose.yml
Descomente as variáveis:
```yaml
# - DB_MONGO_HOST=${DB_MONGO_HOST:-mongodb}
# - DB_MONGO_PORT=${DB_MONGO_PORT:-27017}
# etc...
```

### 3. composer.json
Adicione de volta:
```json
"mongodb/laravel-mongodb": "^4.0"
```

### 4. config/database.php
Descomente a configuração do MongoDB.

## 🏗️ Arquitetura Atual
```
┌─────────────────┐
│   Traefik       │ (Proxy reverso + SSL)
└─────┬───────────┘
      │
┌─────▼───────────┐
│ Laravel 10 App  │ (PHP 8.2 + FPM)
└─────┬───────────┘
      │
┌─────▼───────────┐    ┌─────────────────┐
│     MySQL       │    │     Redis       │
│  (Banco principal)│    │ (Cache + Sessões)│
└─────────────────┘    └─────────────────┘
```

## 🔧 Comandos Úteis

### Rebuild da aplicação
```bash
docker-compose up --build -d
```

### Ver logs
```bash
docker-compose logs -f app
```

### Entrar no container
```bash
docker exec -it f2b-api-laravel10 bash
```

### Rodar migrações
```bash
docker exec -it f2b-api-laravel10 php artisan migrate
```
