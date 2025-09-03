APP_NAME="Bob Flow"
APP_ENV=local
APP_KEY=base64:CHAVE_SERÁ_GERADA_AUTOMATICAMENTE
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bob_flow
DB_USERNAME=guilherme
DB_PASSWORD=vesuvius

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

JWT_SECRET=
JWT_TTL=60# Sistema de Gerenciamento de Usuários com Flow Builder

Este projeto é uma **API de Gerenciamento de Usuários** desenvolvida com **Laravel**, incluindo autenticação via JWT (usando **Laravel Passport**), gerenciamento de permissões e funções de usuários (administrador e usuário comum), além de **integração com o Flow Builder** para facilitar a criação de fluxos dinâmicos e visualização de dados. A API também trata de dados em **XML** e **JSON**, oferecendo flexibilidade no formato de entrada e saída para diversas integrações.

## Funcionalidades

- **Registro de Usuários:** Permite o cadastro de novos usuários com validação de dados.
- **Login e Logout com JWT:** Autenticação e sessões seguras para os usuários utilizando tokens JWT.
- **Gerenciamento de Perfis de Usuário:** Permite editar e visualizar informações de perfil.
- **Atribuição de Funções e Permissões:** Defina funções personalizadas para cada usuário (administrador, moderador, usuário comum) com permissões específicas.
- **Controle de Acesso Baseado em Funções:** Implementação de ACL (Controle de Acesso Baseado em Funções) para limitar o acesso a recursos dependendo da função.
- **Integração com Flow Builder:** Permite a criação e gerenciamento de fluxos dinâmicos de interação com os dados dos usuários.
- **Tratamento de Datasets em XML e JSON:** Suporte completo para importação, exportação e processamento de dados nos formatos XML e JSON.

## Tecnologias Utilizadas

- [Laravel](https://laravel.com/) - Framework PHP moderno e eficiente......
- [Laravel Passport](https://laravel.com/docs/8.x/passport) - Autenticação com JWT.
- [MySQL](https://www.mysql.com/) - Banco de dados relacional para armazenar dados dos usuários e permissões.
- **Flow Builder** - Plataforma de criação de fluxos dinâmicos para automação e personalização de processos.
- **XML/JSON** - Tratamento de dados estruturados para integração com sistemas externos.

## Requisitos

- **PHP >= 7.3**
- **Composer**
- **MySQL**
- **Laravel Passport**
- **Flow Builder (integrado como serviço de automação)**

## Instalação

1. **Clone o repositório:**
    ```sh
    git clone https://github.com/seu-usuario/seu-repositorio.git
    ```

2. **Navegue até o diretório do projeto:**
    ```sh
    cd seu-repositorio
    ```

3. **Instale as dependências do Composer:**
    ```sh
    composer install
    ```

4. **Crie um arquivo `.env` a partir do exemplo e configure suas credenciais de banco de dados:**
    ```sh
    cp .env.example .env
    ```

5. **Certifique-se de que as permissões estão corretas para os diretórios storage e bootstrap/cache:**
    ```sh
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    ```

6. **Gere a chave da aplicação:**
    ```sh
    php artisan key:generate
    ```

7. **Configure o banco de dados no arquivo `.env`:**
    ```plaintext
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nome_do_banco
    DB_USERNAME=seu_usuario
    DB_PASSWORD=sua_senha
    ```

8. **Execute as migrações e seeders:**
    ```sh
    php artisan migrate --seed
    ```

9. **Instale o Laravel Passport:**
    ```sh
    php artisan passport:install
    ```

## Integração com Flow Builder

O **Flow Builder** pode ser utilizado para criar fluxos personalizados e dinâmicos que interagem diretamente com os dados dos usuários. Após a integração, você pode:

- **Criar fluxos de trabalho dinâmicos** para processar dados conforme a interação do usuário.
- **Gerenciar eventos e ações automatizadas** (ex.: envio de e-mails, atualizações de perfil) através de um painel visual.

Para integrar, basta configurar a API do Flow Builder e definir os parâmetros para os fluxos em seu sistema.

## Tratamento de Datasets XML/JSON

Este projeto oferece suporte para manipulação de **dados estruturados em XML e JSON**, essenciais para integração com outros sistemas. É possível importar, exportar e converter dados entre os dois formatos de forma simples.

### Exemplos de Endpoints para Manipulação de Dados

#### 1. **Importação de Dados (XML/JSON)**

**Endpoint:** `POST /api/import`

Corpo da Requisição (JSON ou XML):
```json
{
    "file": "<arquivo_json_ou_xml>"
}
