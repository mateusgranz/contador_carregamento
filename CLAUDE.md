# CLAUDE.md — Constituição do Projeto
## MVP: Contador de Carregamento — Madeireira

---

## 1. Contexto do Projeto

Este é um projeto Laravel isolado e independente, desenvolvido separadamente do site institucional da madeireira.
Futuramente será integrado ao site institucional (também em Laravel), mas por ora deve ser tratado como um projeto autônomo e completo.

**Ao integrar no futuro:** controllers, models, migrations e views serão movidos para o projeto principal sem conflito, pois ambos usam o mesmo stack.

O problema central: produtos são vendidos em m² ou m³, mas o carregamento é feito por pacotes físicos.
O carregador precisa saber quantos pacotes está colocando no caminhão e o total acumulado em tempo real.

**Não existe integração com sistema de vendas.** O carregador trabalha de forma independente.

---

## 2. Stack Obrigatório

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 12 + PHP 8.4 |
| Frontend | Blade + Tailwind CSS |
| Banco (dev) | SQLite |
| Banco (prod) | MySQL |
| Autenticação | Laravel Breeze |
| PDF | DomPDF (barryvdh/laravel-dompdf) |

**Nunca sugerir ou usar tecnologias fora deste stack.**
Não usar Vue, React, Livewire, Alpine.js ou qualquer JS além do Tailwind e vanilla JS mínimo.

---

## 3. Perfis de Usuário

Dois perfis controlados pelo campo `role` na tabela `users`:

- **gestor** — acessa cadastro de produtos e pacotes
- **carregador** — acessa apenas a tela de carregamento

O redirecionamento pós-login deve ser feito com base no `role`.
Middleware de autorização deve bloquear acesso cruzado entre perfis.

---

## 4. Banco de Dados — Estrutura Completa

### Tabela: `users`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| code | string unique | **Código de acesso** — é o que a pessoa digita para entrar |
| name | string | |
| email | string unique nullable | Opcional — ferramenta interna não usa e-mail |
| password | string | hash bcrypt |
| role | enum('gestor','carregador') | |
| timestamps | | criado pelo Laravel |

**Acesso:** o login é por `code` + senha, nunca por e-mail. Não existe autocadastro público nem recuperação de senha por e-mail — quem cria usuários e redefine senhas é o gestor, em `/usuarios`. O login oferece "manter conectado" (remember token), marcado por padrão.

### Tabela: `products`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| name | string | Nome do produto |
| unit | enum('m2','m3','m','br','cx','un','pc') | Unidade de venda — campo único, veja abaixo |
| description | text nullable | Observações opcionais |
| calc_mode | enum('pacote','peso') | Modalidade de cálculo — default: pacote |
| kg_per_unit | decimal(10,4) nullable | Só no modo peso — kg de cada unidade |
| timestamps | | |

**Unidades de venda:** `m2` (m²), `m3` (m³), `m` (M, metro linear), `br` (BR, barra), `cx` (CX, caixa), `un` (UN, unidade), `pc` (PC, peça). São **inteiras** (sem fração): br, cx, un, pc.

**Modalidades de cálculo:**
- `pacote` — o carregador conta pacotes; o sistema acumula m² a partir de `package_types`. Como a conta gera área, só aceita `unit` m² ou m³.
- `peso` — o carregador pesa na balança; o sistema converte kg na unidade do produto usando `kg_per_unit`. Aceita qualquer unidade. Produtos neste modo **não têm** `package_types`, e salvar nesta modalidade apaga os que existirem.

### Tabela: `package_types`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| product_id | bigInt FK | references products(id) |
| length_cm | decimal(8,2) | Comprimento das peças em cm |
| width_mm | decimal(8,2) | Largura das peças em mm |
| thickness_mm | decimal(8,2) | Espessura das peças em mm |
| pieces_count | integer | Quantidade de peças no pacote |
| sqm_per_package | decimal(8,4) | Calculado automaticamente no Model |
| timestamps | | |

**Regra de negócio crítica:** `sqm_per_package` deve ser calculado automaticamente pelo Model usando:
`(width_mm / 1000) * (length_cm / 100) * pieces_count`
Nunca confiar no valor enviado pelo formulário para este campo.

### Tabela: `loadings`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| user_id | bigInt FK | references users(id) — carregador |
| product_id | bigInt FK | references products(id) |
| target_sqm | decimal(8,4) nullable | Metragem do pedido — só no modo pacote |
| loaded_sqm | decimal(8,4) nullable | Total acumulado — só no modo pacote |
| target_qty | decimal(10,4) nullable | Quantidade pedida — só no modo peso |
| loaded_qty | decimal(10,4) nullable | Total acumulado — só no modo peso |
| status | enum('em_andamento','finalizado') | default: em_andamento |
| finished_at | timestamp nullable | |
| timestamps | | |

### Tabela: `loading_fields`
Campos extras que o gestor pede ao carregador (ex.: "Código do pedido").

| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| label | string | Nome do campo, definido pelo gestor |
| type | enum('texto','numero','data') | Tipo do campo |
| required | boolean | Se o carregador é obrigado a preencher |
| active | boolean | Toggle — só aparece para o carregador quando true |
| position | unsignedInt | Ordem de exibição |
| timestamps | | |

### Tabela: `loading_field_values`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| loading_id | bigInt FK | references loadings(id) |
| loading_field_id | bigInt FK | references loading_fields(id) |
| value | string nullable | Guardado sempre como texto |
| timestamps | | |

**Regra:** `required` e `active` são independentes. Um campo obrigatório e inativo não aparece nem bloqueia nada.

### Tabela: `loading_weighings`
Pesagens do modo peso. Cada bobina/lote pesado vira um registro.

| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| loading_id | bigInt FK | references loadings(id) |
| weight_kg | decimal(10,4) | Peso registrado na balança |
| quantity | decimal(10,4) | Quantidade entregue na unidade do produto |
| timestamps | | |

**Regra:** `quantity` pode ser menor que o peso indicaria, porque o carregador corta o excedente da bobina antes de registrar.

### Tabela: `loading_items`
| Campo | Tipo | Observação |
|---|---|---|
| id | bigInt PK | |
| loading_id | bigInt FK | references loadings(id) |
| package_type_id | bigInt FK | references package_types(id) |
| quantity | integer | Quantidade de pacotes deste tipo |
| subtotal_sqm | decimal(8,4) | sqm_per_package × quantity |
| timestamps | | |

---

## 5. Rotas e Telas

```
/login                          → Todos (Laravel Breeze)
/dashboard                      → Todos (redireciona por role)

/produtos                       → Gestor — lista de produtos
/produtos/criar                 → Gestor — formulário novo produto + pacotes
/produtos/{id}/editar           → Gestor — edição de produto e pacotes
/campos                         → Gestor — campos extras do carregamento (nome, tipo, obrigatório, toggle)
/usuarios                       → Gestor — cadastro de usuários (código, nome, senha, perfil)

/carregamento                     → Carregador — passo 1: escolhe o produto
/carregamento/produto/{id}        → Carregador — passo 2: informa a metragem do pedido
/carregamento/{id}                → Carregador — passo 3: contador (modo pacote) ou calculadora de peso (modo peso)
/carregamento/{id}?peso=X         → Carregador — cálculo da pesagem, sem gravar nada
/carregamento/{id}/finalizar      → Carregador — resumo + PDF + WhatsApp
```

---

## 6. Regras de Negócio

1. O fluxo do carregador tem 3 passos, um por tela: escolher o produto → informar a metragem do pedido em m² (mais os campos extras ativos) → contar os pacotes. O carregador nunca calcula pacotes; a conversão é sempre do sistema.
2. O total de m² carregados é calculado e exibido em tempo real conforme os pacotes são adicionados.
3. Quando restar menos de 1 pacote equivalente para completar o pedido, o sistema abre um **pop-up** avisando que falta apenas mais um pacote e indicando a medida mais próxima da metragem restante. A sugestão **nunca é obrigatória**: o pop-up sempre oferece uma saída ("Não tenho essa medida") e o carregador pode adicionar qualquer outro pacote no lugar, porque a medida sugerida pode não existir no pátio.
4. O carregador pode adicionar e remover pacotes livremente a qualquer momento.
5. O `loaded_sqm` deve ser sempre recalculado a partir dos `loading_items`, e o `loaded_qty` a partir das `loading_weighings` — nunca somados incrementalmente, para evitar inconsistências.
7. **Modo peso:** o passo 3 é uma calculadora, não um contador. A tela mostra de saída quanto o pedido dá na balança (`quantidade × kg_per_unit`). O carregador digita o peso lido e o sistema responde quanto retirar (ou quanto ainda falta) e qual peso deixar na balança. O cálculo é um GET e **não grava nada**: só é registrado quando o carregador escolhe entre "já retirei" (registra a quantidade ajustada) ou "registrar tudo".
8. **Modo peso:** unidades discretas (barra, peça) são arredondadas para baixo — pedaço incompleto não conta. Metro admite fração.
6. Ao finalizar, o status muda para `finalizado` e `finished_at` é preenchido.

---

## 7. UX — Regras da Tela de Carregamento

A tela de carregamento é usada por pessoas no pátio, com mãos sujas, sol na tela e pressa.
Seguir obrigatoriamente:

- Botões de + e − com tamanho mínimo de 48px de altura
- Fonte mínima de 18px em todos os elementos da tela de carregamento
- Exibir o m² de cada tipo de pacote embaixo do botão, sempre visível
- Exibir o total de m² carregados em destaque no topo da tela
- Uma pergunta por tela: o operador é leigo em smartphone e não deve decidir duas coisas ao mesmo tempo
- O aviso de fechamento é um pop-up com dois botões grandes: adicionar o pacote sugerido ou dispensar a sugestão
- Fundo branco, alto contraste — sem cores escuras de fundo na tela de carregamento
- Nenhuma ação deve exigir mais de 2 toques para ser executada

---

## 8. PDF e Envio WhatsApp

- Usar `barryvdh/laravel-dompdf` para geração do PDF
- O PDF deve conter: nome do produto, data e hora, nome do carregador, listagem de pacotes por tipo com quantidade e subtotal em m², total geral de m² carregados
- O envio via WhatsApp deve usar link nativo: `https://wa.me/?text=...` com o PDF anexado via share nativo do navegador (Web Share API)
- Não usar API paga do WhatsApp

---

## 9. Convenções de Código

- Classes em **PascalCase** (ex: `PackageType`, `LoadingController`)
- Métodos em **camelCase** (ex: `calculateSqm`, `finalizeLoading`)
- Tabelas no banco em **snake_case plural** (ex: `package_types`, `loading_items`)
- Variáveis e campos em **snake_case** (ex: `sqm_per_package`, `pieces_count`)
- Sempre usar **Form Requests** para validação — nunca validar direto no Controller
- Sempre usar **Resource Controllers** seguindo o padrão REST do Laravel
- Comentários em **português**

---

## 10. Padrões Invioláveis

- Nunca expor rotas do Gestor para o Carregador e vice-versa
- Nunca confiar no `sqm_per_package` vindo do formulário — sempre recalcular no Model
- Nunca recalcular `loaded_sqm` de forma incremental — sempre somar os `loading_items`
- Sempre usar migrations para qualquer alteração no banco — nunca editar o banco direto
- Sempre que criar ou editar um Model, garantir os relacionamentos `hasMany` / `belongsTo`
- Nunca deixar lógica de negócio no Blade — apenas exibição

---

## 11. Fora do Escopo (Não Implementar)

- Pacotes quebrados ou incompletos
- Filtro de carregamento por comprimento mínimo
- Histórico de carregamentos com relatórios gerenciais
- Múltiplos produtos no mesmo carregamento
- Notificações push
- Integração com sistema de vendas
