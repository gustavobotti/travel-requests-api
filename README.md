# Corporate Travel API

Uma API RESTful feita em Laravel para gerenciar solicitações de viagens corporativas.

## Tecnologias

- **Framework:** Laravel 12.x
- **PHP:** 8.2+
- **Database:** MySQL 8.4
- **Authentication:** Laravel Sanctum
- **Development Environment:** Laravel Sail (Docker)
- **Mail Testing:** Mailpit
- **Testing:** PHPUnit

## Pré-requisitos

- Docker
- Git

## Instalação e Configuração

### 1. Clone o repositório

```bash
git clone git@github.com:gustavobotti/travel-requests-api.git
cd travel-requests-api
```

### 2. Instale as dependências

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 composer install --ignore-platform-reqs
```

### 3. Configuração do ambiente

```bash
cp .env.example .env
```

### 4. Configure o alias do Sail (opcional mas recomendado)

```bash
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'

ou simplesmente use `vendor\bin\sail` ao invés de `sail` em todos os comandos.
```

### 5. Inicie a aplicação

```bash
sail up -d
```

### 6. Gere a chave da aplicação

```bash
sail artisan key:generate
```

### 7. Execute as migrations e popule o banco de dados

```bash
sail artisan migrate:fresh --seed
```

### 8. Inicie o queue worker (necessário para notificações por email)

```bash
sail artisan queue:work
```

Mantenha isso rodando em uma janela de terminal separada para processar as notificações por email.

### 9. Execute os testes (opcional)

```bash
sail test
```

## Acessando a Aplicação

Uma vez que os containers estejam rodando, você pode acessar:

- **API:** http://localhost
- **Mailpit (Teste de Email):** http://localhost:8026
- **MySQL Database:** localhost:3307
  - Database: `laravel`
  - Username: `sail`
  - Password: `password`

## Testando Notificações por Email Reais

Para testar o sistema de notificações por email com chamadas reais à API:

1. Certifique-se de que o queue worker está rodando (veja passo 8 acima)
2. Execute o script de teste:

```bash
bash test-notification.sh
```

Este script irá:
- Registrar/fazer login de dois usuários de teste (solicitante e aprovador)
- Criar 2 solicitações de viagem
- Alterar seus status (aprovar, cancelar, e depois cancelar a aprovada)
- Disparar notificações por email para o mailpit

Confira os emails em: **http://localhost:8026**

## Parando a Aplicação

```bash
sail down
```

Para parar e remover os volumes:

```bash
sail down -v
```

## Comandos Adicionais

### Executar comandos artisan
```bash
sail artisan [command]
```

### Executar comandos composer
```bash
sail composer [command]
```

### Acessar o shell do container
```bash
sail shell
```

### Visualizar logs
```bash
sail logs -f
```

### Repopular o banco de dados
```bash
sail artisan migrate:fresh --seed
```

---
