# Deploy no Coolify

Guia do zero até o primeiro login em produção.

---

## 1. Antes de tudo: gere a APP_KEY

O container **se recusa a subir sem ela** — sem `APP_KEY` as sessões e os dados
criptografados não funcionam, e é melhor falhar no boot do que em produção.

Rode na sua máquina:

```bash
php artisan key:generate --show
```

Copie o valor inteiro, incluindo o prefixo `base64:`. Guarde: se você trocar
essa chave depois, todo mundo é deslogado.

---

## 2. Suba o código para um repositório Git

O Coolify faz deploy a partir de um repositório. Crie um repositório privado
(GitHub, GitLab ou o Gitea do próprio Coolify) e envie o projeto:

```bash
git remote add origin git@github.com:SEU-USUARIO/contador-carregamento.git
git push -u origin main
```

> O `.gitignore` já exclui `.env`, `/vendor`, `/node_modules` e o banco SQLite
> local. Nenhum segredo vai junto.

---

## 3. Crie o recurso no Coolify

No painel: **+ New** → **Resource** → escolha o seu repositório Git.

### ⚠️ Troque o Build Pack — este é o passo que mais confunde

Ao adicionar um repositório, o Coolify assume **Nixpacks** por padrão. O
Nixpacks tenta adivinhar como construir o projeto e **ignora completamente** o
`Dockerfile` e o `docker-compose.yml`. O resultado é um container que sobe e
morre (status `Exited`).

Em **Configuration → General → Build Pack**, troque de `Nixpacks` para
**`Docker Compose`** e clique em **Save**.

Aparece então o campo **Docker Compose Location**. Deixe `/docker-compose.yml`.

Salve de novo. O Coolify lê o arquivo e passa a mostrar os dois serviços:
`app` e `mysql`.

> Se preferir usar um banco gerenciado pelo próprio Coolify (com backup pela
> interface), apague o serviço `mysql` do compose e aponte `DB_HOST` para o
> host que ele fornecer. O caminho documentado aqui é o do compose, porque foi
> o testado de ponta a ponta.

### Domínio

O domínio precisa apontar para o serviço **`app`**, na porta **8080** — nunca
para o `mysql`. O compose declara `expose: 8080`, então o Coolify já sugere a
porta certa.

---

## 4. Variáveis de ambiente

Cadastre em **Environment Variables**:

| Variável | Valor | Observação |
|---|---|---|
| `APP_KEY` | `base64:...` | O que você gerou no passo 1 |
| `APP_URL` | `https://carregamento.seudominio.com.br` | Com `https://`, sem barra no fim |
| `DB_PASSWORD` | senha forte | Senha do usuário da aplicação |
| `DB_ROOT_PASSWORD` | outra senha forte | Root do MySQL |
| `APP_NAME` | `Contador de Carregamento` | Opcional |
| `DB_DATABASE` | `carregamento` | Opcional, esse é o padrão |
| `DB_USERNAME` | `carregamento` | Opcional, esse é o padrão |

`APP_ENV=production`, `APP_DEBUG=false` e o locale `pt_BR` já vêm fixos no
compose — não precisa cadastrar.

### 🔴 Se o domínio for `http://` (sem HTTPS)

Cadastre também:

| Variável | Valor |
|---|---|
| `SESSION_SECURE_COOKIE` | `false` |

O padrão é `true`, que manda o navegador só enviar o cookie de sessão por
HTTPS. Num domínio `http://` — o `sslip.io` gerado pelo Coolify, por exemplo —
o navegador simplesmente descarta o cookie: o login parece funcionar mas volta
para a tela de login, ou dá erro 419.

Ao colocar um domínio real com HTTPS depois, **remova essa variável** para
voltar ao padrão seguro.

---

## 5. Domínio e HTTPS

Aponte o domínio para o servidor do Coolify e configure-o no recurso. O Coolify
emite o certificado Let's Encrypt sozinho.

A aplicação já confia no proxy (`trustProxies` em `bootstrap/app.php`), então as
URLs e os cookies seguros funcionam corretamente atrás do Traefik.

---

## 6. Deploy

Clique em **Deploy**. O que acontece sozinho, a cada deploy:

1. Build da imagem — instala dependências PHP e compila o Tailwind
2. Espera o MySQL responder (até 60 segundos)
3. Roda `php artisan migrate --force`
4. Gera cache de config, rotas e views
5. Sobe nginx + php-fpm

Acompanhe pelos logs. Quando aparecer `Pronto — subindo nginx e php-fpm`, está no ar.

---

## 7. Crie o primeiro usuário

**Este passo é obrigatório.** Não existe autocadastro: um banco novo não tem
nenhum usuário, e sem isso você não consegue entrar.

No terminal do container (aba **Terminal** ou **Execute Command** no Coolify):

```bash
php artisan usuario:gestor --code=admin --name="Seu Nome" --password="uma-senha-boa"
```

Agora entre em `https://seudominio/login` com o código `admin`.

Daí em diante, cadastre os outros pelo menu **Usuários**.

> O mesmo comando serve para **recuperar o acesso** se a senha do gestor se
> perder: rodar de novo com o mesmo código redefine a senha.

---

## Depois do deploy

### Trocar a senha de alguém
Menu **Usuários** → **Editar**. Senha em branco mantém a atual.

### Backup
O que importa está no volume `mysql-data`. Configure o backup agendado do
Coolify apontando para o serviço `mysql`. Para um dump manual:

```bash
mysqldump -u root -p"$DB_ROOT_PASSWORD" carregamento > backup.sql
```

### Ver logs
Ficam no stdout do container, visíveis no painel do Coolify. `LOG_LEVEL` está em
`warning` — para investigar algum problema, mude para `debug` temporariamente.

### Atualizar o sistema
`git push` na branch configurada. Se o webhook estiver ligado, o Coolify faz o
deploy sozinho; as migrations rodam automaticamente.

---

## Problemas comuns

**O container reinicia em loop e o log mostra `ERRO: APP_KEY não está definida`**
A variável não foi cadastrada, ou foi cadastrada sem o prefixo `base64:`.

**`banco de dados não respondeu depois de 30 tentativas`**
O MySQL não subiu. Veja os logs dele — quase sempre é `DB_ROOT_PASSWORD` vazia.

**Tela sem estilo, tudo desalinhado**
O `APP_URL` está errado ou com `http://` num domínio que serve HTTPS.

**O build roda vários minutos e morre sem mensagem de erro**
Falta de memória no servidor durante a compilação das extensões PHP. O
processo é morto pelo kernel e o log simplesmente para no meio, sem erro.
O Dockerfile já compila só o essencial e com `-j2` justamente por isso;
se voltar a acontecer, o servidor precisa de mais RAM ou de swap.

**Erro 419 ao enviar formulários**
Mesma causa: `APP_URL` diferente do domínio real quebra a validação do CSRF.

---

## O que foi verificado

Esta configuração foi testada de verdade, não só escrita: a imagem foi
construída, a stack subiu com MySQL 8.4, as 15 migrations rodaram, o gestor foi
criado pelo comando, o login por código funcionou, e o fluxo completo do
carregador (produto por peso → pesagem de 35 kg → orientação de retirar 4,31 m →
finalização → PDF de 878 KB) rodou de ponta a ponta dentro do container.
