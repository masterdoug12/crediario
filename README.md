# Fiados – Controle de Crediário

Aplicação completa para gerenciamento de crediários de clientes, composta por uma API Laravel 12 com autenticação via Sanctum e um frontend SPA em Vue 3 + Vite. A infraestrutura é orquestrada por Docker, com PostgreSQL, PHP-FPM, Nginx e um contêiner opcional para automação de build do frontend.

## Stack

- **Backend:** Laravel 12 (PHP 8.2), Laravel Sanctum, PostgreSQL
- **Frontend:** Vue 3, Vite, Bootstrap 5, Axios
- **Infra:** Docker Compose (php-fpm, nginx, postgres, node)

## Estrutura de pastas

```
Fiados/
├── backend/      # Projeto Laravel (API, migrations, seeders, etc.)
├── frontend/     # Projeto Vue 3 + Vite (SPA)
├── docker/       # Dockerfiles e configuração Nginx/PHP
└── docker-compose.yml
```

## Pré-requisitos

- Docker e Docker Compose instalados

## Subindo o ambiente

1. **Copie as variáveis padrão (se necessário):**
   ```bash
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
   ```

2. **Construa os contêineres:**
   ```bash
   docker compose build
   ```

3. **Suba a stack:**
   ```bash
   docker compose up -d
   ```

4. **Instale as dependências do Laravel e gere a key (apenas na primeira vez):**
   ```bash
   docker compose run --rm app composer install
   docker compose run --rm app php artisan key:generate
   ```

5. **Execute as migrations e seeds (gera dados de exemplo e usuário admin):**
   ```bash
   docker compose run --rm app php artisan migrate --seed
   ```

6. **Crie o link de storage para servir arquivos públicos (opcional, mas recomendado):**
   ```bash
   docker compose run --rm app php artisan storage:link
   ```

O contêiner `node` instala as dependências do frontend e mantém um watch (`vite build --watch`) gerando a SPA para `backend/public/app`. O Nginx serve essa build estática diretamente em `http://localhost`, enquanto todas as rotas `http://localhost/api/...` são encaminhadas para o Laravel.

> Para forçar uma nova build manualmente: `docker compose exec node npm run build`

## Credenciais de acesso

- **E-mail:** `admin@crediario.local`
- **Senha:** `senha123`

Esses dados são criados automaticamente pelo seeder `AdminUserSeeder`.

## Endpoints principais

| Método | Rota                                 | Descrição                               |
|--------|--------------------------------------|-----------------------------------------|
| POST   | `/api/login`                         | Autenticação (Sanctum, retorna token)   |
| POST   | `/api/logout`                        | Revoga o token atual                    |
| GET    | `/api/clientes`                      | Lista clientes + filtro por nome/telefone|
| POST   | `/api/clientes`                      | Cadastra cliente                        |
| GET    | `/api/clientes/{cliente}`            | Detalhes de um cliente                  |
| PUT    | `/api/clientes/{cliente}`            | Atualiza cliente                        |
| DELETE | `/api/clientes/{cliente}`            | Remove cliente                          |
| GET    | `/api/clientes/{cliente}/movimentos` | Histórico combinado de débitos/pagamentos|
| POST   | `/api/clientes/{cliente}/debito`     | Registra um débito                      |
| POST   | `/api/clientes/{cliente}/pagamento`  | Registra um pagamento                   |

Todas as rotas, exceto `POST /api/login`, exigem token Bearer (`Authorization: Bearer {token}`).

## Frontend

- Login via endpoint `/api/login`
- Listagem de clientes com busca, saldo atualizado e ações básicas
- Detalhamento com histórico, cards de débitos/pagamentos e formulários modais
- Axios configurado com interceptador para renovar o header `Authorization`

## Próximos passos sugeridos

- Configurar HTTPS/SSL caso necessário em produção
- Adicionar testes automatizados (PHPUnit/Pest no backend e Vitest/Cypress no frontend)
- Habilitar pipelines de CI/CD executando `php artisan test` e `npm run build`

---

**Inicialização rápida:**
```bash
docker compose up -d
# primeira vez:
docker compose run --rm app composer install
docker compose run --rm app php artisan migrate --seed
```
A aplicação estará disponível em **http://localhost**.
