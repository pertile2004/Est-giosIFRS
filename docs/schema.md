# Schema do banco

Banco: `estagios` · Charset: `utf8mb4_unicode_ci` · Engine: `InnoDB`

## Diagrama de relacionamentos

```
usuarios (1) ──┬── (1) alunos
               ├── (1) empresas
               └── (N) password_resets
               └── (N) oauth_accounts

empresas (1) ── (N) vagas
vagas    (1) ── (N) candidaturas
alunos   (1) ── (N) candidaturas
alunos   (1) ── (N) vagas_favoritas
vagas    (1) ── (N) vagas_favoritas
```

## Tabelas

### `usuarios`
Identidade base, comum a alunos e empresas.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `nome` | VARCHAR(150) | Nome completo ou razão social |
| `email` | VARCHAR(200) UNIQUE | Login |
| `senha` | VARCHAR(255) | Hash bcrypt |
| `tipo` | ENUM('aluno','empresa') | Discrimina o perfil |
| `is_admin` | TINYINT(1) | 1 = administrador |
| `ativo` | TINYINT(1) DEFAULT 1 | 0 = bloqueado |
| `criado_em` | TIMESTAMP | |

### `alunos`
Dados específicos do estudante. 1-para-1 com `usuarios`.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `usuario_id` | INT FK | → `usuarios.id` ON DELETE CASCADE |
| `curso` | VARCHAR(150) | |
| `universidade` | VARCHAR(200) | |
| `semestre` | INT | |
| `cidade`, `estado` | VARCHAR/CHAR(2) | |
| `sobre` | TEXT | Bio |
| `linkedin`, `github` | VARCHAR(255) | URLs |
| `foto` | VARCHAR(255) | URL de avatar |
| `curriculo_path` | VARCHAR(255) | Caminho relativo (`uploads/curriculos/cv_...pdf`) |

### `empresas`
Dados específicos da empresa. 1-para-1 com `usuarios`.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `usuario_id` | INT FK | → `usuarios.id` ON DELETE CASCADE |
| `nome_empresa` | VARCHAR(200) | Razão social |
| `cnpj` | VARCHAR(20) | Apenas dígitos |
| `descricao` | TEXT | |
| `site` | VARCHAR(255) | |
| `cidade`, `estado` | VARCHAR/CHAR(2) | |
| `setor` | VARCHAR(100) | |
| `logo` | VARCHAR(255) | URL ou caminho |

### `vagas`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `empresa_id` | INT FK | → `empresas.id` ON DELETE CASCADE |
| `titulo` | VARCHAR(200) | |
| `descricao` | TEXT | |
| `requisitos`, `beneficios` | TEXT | Texto livre |
| `area` | VARCHAR(100) | Ex: `Desenvolvimento Web` |
| `cidade`, `estado` | VARCHAR/CHAR(2) | |
| `modalidade` | ENUM | `presencial`, `remoto`, `hibrido` |
| `bolsa` | DECIMAL(10,2) | R$/mês |
| `carga_horaria` | INT | h/semana |
| `ativa` | TINYINT(1) DEFAULT 1 | 0 = pausada |
| `destaque` | TINYINT(1) DEFAULT 0 | 1 = aparece em destaque |
| `views` | INT DEFAULT 0 | Contador de visualizações |
| `criado_em` | TIMESTAMP | |

### `candidaturas`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `aluno_id` | INT FK | → `alunos.id` ON DELETE CASCADE |
| `vaga_id` | INT FK | → `vagas.id` ON DELETE CASCADE |
| `status` | ENUM | `pendente`, `visualizado`, `aprovado`, `recusado` |
| `carta` | TEXT | Carta de apresentação |
| `criado_em` | TIMESTAMP | |

Índice único `(aluno_id, vaga_id)` — um aluno só pode se candidatar
uma vez por vaga.

### `password_resets`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `usuario_id` | INT FK | → `usuarios.id` ON DELETE CASCADE |
| `token` | VARCHAR(255) UNIQUE | 64 caracteres hex |
| `expires_at` | DATETIME | 1h após criação |
| `used` | TINYINT(1) | 1 = já utilizado |
| `criado_em` | TIMESTAMP | |

Index secundário `idx_token (token)` para lookup rápido.

### `oauth_accounts`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `usuario_id` | INT FK | → `usuarios.id` ON DELETE CASCADE |
| `provider` | ENUM | `google`, `github` |
| `provider_user_id` | VARCHAR(100) | ID do usuário no provider |
| `email` | VARCHAR(200) | E-mail informado pelo provider |
| `criado_em` | TIMESTAMP | |

Índice único `(provider, provider_user_id)` — uma conta externa só pode
estar vinculada a um usuário interno.

### `vagas_favoritas`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | INT AI PK | |
| `aluno_id` | INT FK | → `alunos.id` ON DELETE CASCADE |
| `vaga_id` | INT FK | → `vagas.id` ON DELETE CASCADE |
| `criado_em` | TIMESTAMP | |

Índice único `(aluno_id, vaga_id)` — sem favoritos duplicados.
