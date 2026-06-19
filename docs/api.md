# Rotas e endpoints

Todos os endpoints são páginas PHP (não API REST). Convencionamos GET para
listagens/exibições e POST para mutações (cadastros, candidaturas, etc.).

## Públicas (sem autenticação)

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teste/` | Landing com hero, stats e features |
| GET | `/teste/vagas.php` | Listagem de vagas com filtros |
| GET | `/teste/vaga.php?id=N` | Detalhe da vaga + form de candidatura |
| GET | `/teste/login.php` | Tela de login |
| POST | `/teste/login.php` | Autentica e cria sessão |
| GET | `/teste/register.php` | Tela de cadastro |
| POST | `/teste/register.php` | Cria conta de aluno ou empresa |
| GET | `/teste/logout.php` | Destrói sessão |
| GET | `/teste/forgot-password.php` | Form de solicitação de reset |
| POST | `/teste/forgot-password.php` | Gera token de reset |
| GET | `/teste/reset-password.php?token=...` | Form de nova senha |
| POST | `/teste/reset-password.php` | Salva nova senha |
| GET | `/teste/auth/google.php` | Início do fluxo OAuth Google |
| GET | `/teste/auth/google.php?callback=1` | Callback OAuth Google |
| GET | `/teste/auth/github.php` | Início do fluxo OAuth GitHub |
| GET | `/teste/auth/github.php?callback=1` | Callback OAuth GitHub |
| GET | `/teste/privacidade.php` | Política de Privacidade |
| GET | `/teste/termos.php` | Termos de Uso |

### Parâmetros de `/teste/vagas.php`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `q` | string | Busca em título, descrição, empresa e área |
| `area` | string | Filtro por área (ex: `Tecnologia`) |
| `modalidade` | enum | `presencial`, `remoto`, `hibrido` |
| `cidade` | string | Cidade do IFRS (ex: `Bento Gonçalves`) |
| `bolsa_min` | int | Bolsa mínima (0 a 3000) |
| `bolsa_max` | int | Bolsa máxima (0 a 3000) |
| `page` | int | Paginação (12 por página) |

## Aluno autenticado

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teste/dashboard/aluno.php` | Painel: KPIs, candidaturas, favoritos, perfil |
| POST | `/teste/dashboard/aluno.php` | Atualiza perfil + upload de CV ou foto |
| POST | `/teste/vaga.php?id=N` | Candidata-se (`candidatar`) ou salva favorito (`toggle_favorito`) |
| GET | `/teste/perfil/aluno.php?id=N` | Visualiza próprio perfil |

### Uploads do aluno (`POST /teste/dashboard/aluno.php`)

`multipart/form-data` com os campos opcionais `curriculo` e `foto`.

Currículo:
- Extensão `.pdf`
- MIME `application/pdf`
- Tamanho máximo 3 MB

Foto de perfil:
- Extensão `.jpg`, `.jpeg`, `.png` ou `.webp`
- MIME `image/*`
- Tamanho máximo 2 MB

Os dois substituem o arquivo anterior (apagam do disco) e podem ser
removidos via `remover_curriculo` ou `remover_foto`.

## Empresa autenticada

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teste/dashboard/empresa.php` | Painel: KPIs, vagas, candidatos, perfil |
| POST | `/teste/dashboard/empresa.php` | `update_status`, `toggle_vaga` ou `consultar_cnpj` |
| GET | `/teste/empresa/publicar.php` | Form de nova vaga |
| POST | `/teste/empresa/publicar.php` | Cria vaga |
| GET | `/teste/empresa/publicar.php?id=N` | Form de edição de vaga já publicada |
| POST | `/teste/empresa/publicar.php?id=N` | Atualiza vaga existente |
| POST | `/teste/empresa/update.php` | Atualiza perfil da empresa + upload de logo |
| GET | `/teste/empresa/exportar.php` | Baixa CSV de todas as candidaturas da empresa |
| GET | `/teste/empresa/exportar.php?vaga_id=N` | CSV das candidaturas de uma vaga específica |
| GET | `/teste/perfil/aluno.php?id=N` | Visualiza perfil de aluno que se candidatou |

A edição via `?id=N` só é permitida para vagas da própria empresa logada
(caso contrário redireciona para o painel).

### Resposta de `consultar_cnpj`

Quando POST contém `consultar_cnpj=1` e `cnpj`, o servidor consulta a
BrasilAPI e, se sucesso, atualiza `nome_empresa`, `cidade`, `estado` e
`cnpj` no banco.

### Upload de logo (`POST /teste/empresa/update.php`)

`multipart/form-data` com o campo opcional `logo`. Mesmas validações da
foto do aluno: `.jpg`/`.jpeg`/`.png`/`.webp`, MIME `image/*`, máx 2 MB.
Aceita também `remover_logo=1` para apagar o arquivo do disco e zerar
o campo no banco.

## Chat aluno/empresa

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teste/chat.php?candidatura_id=N` | Abre a conversa da candidatura |
| POST | `/teste/chat.php?candidatura_id=N` | Envia nova mensagem (campo `enviar` + `conteudo`) |

Só o aluno dono da candidatura ou a empresa dona da vaga acessam (HTTP
403 caso contrário). Ao abrir, marca como `lida = 1` todas as mensagens
recebidas do outro lado. Conteúdo tem limite de 2000 caracteres.

## Admin (requer `is_admin = 1`)

| Método | Rota | Descrição |
|---|---|---|
| GET | `/teste/admin/` | Visão geral |
| GET | `/teste/admin/empresas.php` | Lista de empresas |
| POST | `/teste/admin/empresas.php` | Ações: `ativar`, `desativar`, `excluir` |
| GET | `/teste/admin/vagas.php` | Lista de vagas (com filtro `?filtro=ativas\|pausadas\|todas`) |
| POST | `/teste/admin/vagas.php` | Ações: `ativar`, `pausar`, `excluir` |

## Códigos de resposta convencionais

- **200** — sucesso (HTML renderizado ou CSV)
- **302** — redirect (após POST bem-sucedido, ou requireLogin)
- **403** — acesso negado (admin ou perfil restrito)
- **404** — recurso não encontrado (vaga não existe, etc.)
- **500** — erro de banco ou interno
