# Contador de Carregamento

Ferramenta interna para o pátio da madeireira. Os produtos são vendidos em m²,
m³, metros, caixas, unidades ou peças — mas o carregamento é feito por pacotes
físicos ou pesado na balança. O carregador não faz conta: informa o que o pedido
pede e o sistema converte.

## Como funciona

**Carregador** — três passos, uma decisão por tela:

1. Escolhe o produto
2. Informa a quantidade do pedido (mais os campos extras que o gestor pediu)
3. Conta os pacotes ou pesa o material

Ao final, gera um comprovante em PDF e compartilha no WhatsApp.

Existem duas modalidades de cálculo, definidas por produto:

- **Por pacotes** — conta pacotes com `+` e `−`, acumulando m² em tempo real.
  Quando falta menos de um pacote, um pop-up avisa qual medida fecha o pedido —
  sem obrigar, porque a medida sugerida pode não existir no pátio.
- **Por peso** — informa quanto deu na balança e o sistema responde quantos
  metros/peças são, quanto retirar e qual peso deixar. Várias pesagens somam
  até fechar o pedido.

**Gestor** — cadastra produtos e tipos de pacote, define campos extras que o
carregador precisa preencher (com toggle de ativação) e administra os usuários.

## Stack

Laravel 12 · PHP 8.4 · Blade + Tailwind · SQLite (dev) / MySQL (prod) · DomPDF

## Rodando localmente

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
php artisan migrate

# Cria o primeiro acesso — não existe autocadastro
php artisan usuario:gestor --code=admin --name="Admin" --password=teste123

php artisan serve
```

Para popular dados de demonstração: `php artisan db:seed --class=DemoSeeder`
(cria os usuários `gestor` e `carregador`, senha `senha1234`).

## Testes

```bash
php artisan test
```

## Deploy

Veja **[DEPLOY.md](DEPLOY.md)** — guia passo a passo para o Coolify, com
Dockerfile e docker-compose prontos.

## Convenções

As regras de negócio, o esquema do banco e os padrões de código estão em
**[CLAUDE.md](CLAUDE.md)**.
