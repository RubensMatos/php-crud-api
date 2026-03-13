# PHP CRUD API

API CRUD em PHP 8.3 com arquitetura modular e smoke test local.

## Objetivo

Demonstrar capacidade de construcao de API profissional em PHP com:

- roteamento com parametros dinamicos
- camada de controller e repositorio
- validacao de payload
- persistencia com fallback automatico:
  - SQLite (quando `pdo_sqlite` estiver disponivel)
  - arquivo JSON local (quando SQLite nao estiver habilitado)
- tratamento de erros

## Endpoints

- `GET /health`
- `GET /api/tasks`
- `GET /api/tasks/{id}`
- `POST /api/tasks`
- `PUT /api/tasks/{id}`
- `DELETE /api/tasks/{id}`

## Exemplo de payload

```json
{
  "title": "Preparar demo tecnica",
  "description": "Apresentar API CRUD em entrevista",
  "status": "todo"
}
```

Valores permitidos para `status`: `todo`, `in_progress`, `done`.

## Como executar

```bash
cd projects/php-crud-api
php -S 127.0.0.1:8081 -t public public/index.php
```

## Smoke test automatizado

```bash
cd projects/php-crud-api
bash tests/smoke-test.sh
```

## Estrutura

- `public/index.php`: bootstrap e definicao de rotas
- `src/Http`: classes de Request/Response
- `src/Support`: roteador e conexao com banco
- `src/Repositories`: acesso a dados
- `src/Controllers`: regras de API
- `src/Validation`: validacoes de negocio
