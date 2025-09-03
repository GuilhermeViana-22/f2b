# 🐳 Laravel 10 F2B API - Docker Setup Otimizado

## 📋 Resumo das Configurações

Este projeto foi configurado com **Docker otimizado** para Laravel 10, incluindo:

- ✅ **Porta 8007** em todos os serviços
- ✅ **MongoDB** com extensão PHP nativa
- ✅ **Redis** para cache e sessões
- ✅ **Supervisord** com queue workers e scheduler
- ✅ **PHP 8.2** otimizado para produção

## 🚀 Como Usar

### 1. Build e Deploy no Dokploy

```bash
# Dokploy vai usar automaticamente:
# - Dockerfile (otimizado)
# - docker-compose.yml
# - .dockerignore (para builds rápidos)
```

### 2. Variáveis de Ambiente

Certifique-se de que o `.env` contém:

```env
# MongoDB
DB_MONGO_HOST=mongodb
DB_MONGO_PORT=27017
DB_MONGO_DATABASE=f2b
DB_MONGO_USERNAME=admin
DB_MONGO_PASSWORD=sua_senha
DB_MONGO_AUTHDATABASE=admin

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 3. Acesso

- **API**: `https://f2b.guilhermeviana.com`
- **Porta Local**: `8007`
- **Health Check**: `/api/health`

## 🔧 Recursos Incluídos

### Supervisord Processos
- **laravel-server**: Servidor principal (porta 8007)
- **laravel-queue**: Worker para jobs Redis
- **laravel-scheduler**: Executa tarefas agendadas
- **laravel-init**: Inicialização (migrations, seeds, passport)

### Otimizações PHP
- **OPcache** habilitado
- **Realpath cache** configurado
- **Memory limit** 512MB
- **Session** via Redis

### Cache de Docker
- **Multi-layer caching**
- **Composer cache** otimizado
- **.dockerignore** configurado

## 🛠️ Troubleshooting

### Build Lento na Primeira Vez
- É normal! MongoDB precisa ser compilado
- Builds subsequentes são mais rápidos (cache)

### Erro de Permissões
```bash
# Dentro do container
chown -R www-data:www-data /var/www/html
chmod -R 775 storage bootstrap/cache
```

### MongoDB Connection
- Verifique variáveis `DB_MONGO_*` no .env
- Teste conexão: `php artisan tinker`

## 📊 Performance

### Tempo de Build
- **Primeira vez**: ~8-12 minutos
- **Builds incrementais**: ~2-5 minutos
- **Deploy**: ~30-60 segundos

### Recursos
- **RAM**: 512MB limit (256MB reserved)
- **CPU**: 1.0 limit (0.5 reserved)
- **Redis**: 256MB max memory

## 🔍 Logs

```bash
# Ver logs do container
docker logs f2b-api-laravel10

# Logs do supervisor
docker exec f2b-api-laravel10 tail -f /var/log/supervisor/supervisord.log

# Logs do Laravel
docker exec f2b-api-laravel10 tail -f storage/logs/laravel.log
```

## 🆕 Arquivos Importantes

- `Dockerfile` - Build otimizado
- `docker-compose.yml` - Serviços configurados
- `supervisord.conf` - Processos gerenciados
- `init.sh` - Script de inicialização
- `php.ini` - Configurações PHP
- `.dockerignore` - Build otimizado

## 🔄 Comandos Úteis

```bash
# Rebuild completo
docker-compose build --no-cache

# Restart específico
docker-compose restart app

# Executar artisan
docker exec f2b-api-laravel10 php artisan migrate

# Shell no container
docker exec -it f2b-api-laravel10 bash
```
