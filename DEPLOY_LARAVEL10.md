# 🚀 Deploy Laravel 10 - F2B API

## Principais Mudanças para Laravel 10

### ✅ Atualizações Realizadas

1. **PHP 8.2**: Atualizado de PHP 8.1 para 8.2 (recomendado para Laravel 10)
2. **Multi-stage Docker Build**: Otimização de tamanho da imagem
3. **Nginx + PHP-FPM**: Substituição do `artisan serve` por nginx para produção
4. **Cache Redis**: Adicionado para melhor performance
5. **OPCache**: Configurações otimizadas para Laravel 10
6. **Supervisor**: Configuração melhorada para nginx + php-fpm

### 🔧 Configurações de Performance Laravel 10

- **OPCache habilitado** com configurações otimizadas
- **Cache de configurações** (`config:cache`)
- **Cache de rotas** (`route:cache`)
- **Cache de views** (`view:cache`)
- **Cache de eventos** (`event:cache`)
- **Redis** para cache, sessões e filas

## 🐳 Como Fazer o Deploy

### 1. Build da Nova Imagem

```bash
# Build da imagem otimizada para Laravel 10
docker-compose build --no-cache

# Ou build apenas do app
docker build -t f2b-api-laravel10 .
```

### 2. Parar Containers Antigos

```bash
# Parar containers em execução
docker-compose down

# Remover imagens antigas (opcional)
docker image prune -f
```

### 3. Iniciar Nova Stack

```bash
# Subir com a nova configuração
docker-compose up -d

# Verificar logs
docker-compose logs -f app
```

### 4. Verificar Health Check

```bash
# Verificar se a aplicação está rodando
curl http://localhost:8000/api/health

# Verificar status dos containers
docker-compose ps
```

## 📊 Melhorias de Performance

### Antes (Laravel 8)
- PHP 8.1
- Artisan serve (desenvolvimento)
- Cache em arquivos
- Sem otimizações OPCache

### Depois (Laravel 10)
- PHP 8.2
- Nginx + PHP-FPM (produção)
- Cache Redis
- OPCache otimizado
- Build multi-stage (imagem menor)

## 🔍 Monitoramento

### Health Check
```bash
# API Health Check
curl -f http://localhost:8000/api/health

# Redis Check
docker exec f2b-redis redis-cli ping
```

### Logs
```bash
# Logs da aplicação
docker-compose logs -f app

# Logs do Redis
docker-compose logs -f redis

# Logs específicos do nginx
docker exec f2b-api-laravel10 tail -f /var/log/nginx/error.log
```

## 🛠️ Configurações Opcionais

### Filas (Queue Workers)
Se sua aplicação usa filas, descomente no `supervisord.conf`:

```ini
[program:laravel-queue]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/html
user=www-data
autostart=true
autorestart=true
```

### Agendamento (Scheduler)
Para tarefas agendadas, descomente no `supervisord.conf`:

```ini
[program:laravel-scheduler]
command=/bin/bash -c "while true; do php /var/www/html/artisan schedule:run --verbose --no-interaction & sleep 60; done"
```

## 🔒 Segurança

1. **Variáveis de ambiente**: Nunca commitadas no Git
2. **Chaves Passport**: Mantidas em volumes persistentes
3. **Logs**: Direcionados para stderr/stdout (Docker logs)
4. **Limites de recursos**: Configurados no docker-compose.yml

## 📈 Otimizações Adicionais

### Para Alto Tráfego
```bash
# Aumentar limites de memoria no docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '2.0'
```

### Para Desenvolvimento
```bash
# Build target development (se necessário)
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
```

## 🆘 Troubleshooting

### Container não inicia
```bash
# Verificar logs detalhados
docker-compose logs app

# Entrar no container para debug
docker exec -it f2b-api-laravel10 sh
```

### Performance baixa
```bash
# Verificar uso de recursos
docker stats

# Verificar cache Redis
docker exec f2b-redis redis-cli info memory
```

### Problemas de permissão
```bash
# Recriar volumes se necessário
docker-compose down -v
docker-compose up -d
```

## ✅ Checklist de Deploy

- [ ] Build da nova imagem realizado
- [ ] Containers antigos parados
- [ ] Variáveis de ambiente configuradas
- [ ] Redis funcionando
- [ ] Health check passando
- [ ] Logs sem erros
- [ ] Performance monitorada
- [ ] Backup dos dados importantes realizado

---

🎉 **Deploy realizado com sucesso!** Sua aplicação Laravel agora está rodando na versão 10 com otimizações de performance e segurança.
