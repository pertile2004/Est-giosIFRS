# InternSHIP Conect

Plataforma de estágios da região do IFRS (Serra Gaúcha) conectando estudantes
universitários a empresas locais.

[![PHP Lint](https://github.com/pertile2004/Est-giosIFRS/actions/workflows/ci.yml/badge.svg)](https://github.com/pertile2004/Est-giosIFRS/actions/workflows/ci.yml)

## Sumário

- [O que o sistema faz](#o-que-o-sistema-faz)
- [Stack](#stack)
- [Setup local](#setup-local)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Documentação detalhada](#documentação-detalhada)
- [Testes](#testes)
- [Contribuindo](#contribuindo)

## O que o sistema faz

### Para alunos
- Cadastro com curso, universidade e semestre
- Busca de vagas com filtros (área, modalidade, cidade IFRS, faixa de bolsa)
- Candidatura com carta de apresentação opcional
- Upload de currículo em PDF
- Foto de perfil (opcional)
- Vagas salvas em favoritos
- Chat com a empresa após a candidatura
- Painel com KPIs e histórico de candidaturas
- Login social Google/GitHub (opcional, requer configuração)
- Recuperação de senha por e-mail

### Para empresas
- Publicação de vagas com requisitos, benefícios e modalidade
- Edição de vagas já publicadas
- Logo da empresa (opcional)
- Verificação por CNPJ via BrasilAPI (Receita Federal)
- Gestão inline de candidaturas (pendente/visualizado/aprovado/recusado)
- Chat com cada candidato
- Visualização do perfil público de cada candidato
- Analytics de visualizações por vaga
- Exportação de candidaturas em CSV
- Pausar/reativar vagas

### Para administradores
- Painel com visão geral da plataforma
- Moderação de empresas (ativar/desativar/excluir)
- Moderação de vagas (pausar/ativar/excluir)

### Recursos transversais
- Tema claro/escuro com persistência (localStorage)
- Banner de cookies (LGPD)
- Páginas legais (Termos de uso, Política de privacidade)
- PWA instalável com suporte offline básico
- Responsivo para desktop e mobile

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8+ (procedural com helpers em `includes/auth.php`) |
| Banco | MySQL 5.7+ / MariaDB (via PDO) |
| Frontend | HTML5, CSS3 (custom design system), JavaScript vanilla |
| Tipografia | Inter (Google Fonts) |
| API externa | BrasilAPI (consulta de CNPJ) |
| Hospedagem dev | XAMPP (Apache + MySQL) |

Sem dependências de package manager. Tudo roda direto no XAMPP.

## Setup local

1. **Clone o repositório**
   ```bash
   git clone https://github.com/pertile2004/Est-giosIFRS.git teste
   cd teste
   ```

2. **Coloque dentro de `c:/xampp/htdocs/teste/`** (ou ajuste os caminhos
   `/teste/...` no código para a sua pasta).

3. **Crie o banco**
   - Abra `http://localhost/phpmyadmin`
   - Aba **SQL** → cole o conteúdo de [`setup.sql`](setup.sql) → Executar
   - O banco `estagios` será criado com seeds (6 vagas e 4 usuários de exemplo)

4. **Acesse**
   - `http://localhost/teste/` para a landing
   - Senha padrão dos seeds: **`senha123`**
     - Empresa: `rh@techsolutions.com`
     - Aluno: `joao@email.com`

5. **(Opcional) Configure OAuth** copiando `config/oauth.php` para
   `config/oauth.local.php` e preenchendo as credenciais reais do Google
   e GitHub.

6. **(Opcional) Promova um administrador**:
   ```sql
   UPDATE usuarios SET is_admin = 1 WHERE email = 'seu@email.com';
   ```

## Estrutura de pastas

```
teste/
├── admin/                  # Painel administrativo (moderação)
│   ├── index.php
│   ├── empresas.php
│   └── vagas.php
├── assets/
│   ├── css/style.css       # Design system completo
│   ├── js/main.js          # Interações (toggle tema, slider, LGPD)
│   └── img/                # Logo SVG
├── auth/                   # Handlers OAuth
│   ├── google.php
│   └── github.php
├── config/
│   ├── database.php        # Conexão PDO
│   ├── oauth.php           # Config OAuth (placeholders)
│   └── oauth.local.php     # (ignorado pelo git)
├── dashboard/
│   ├── aluno.php           # Painel do estudante
│   └── empresa.php         # Painel da empresa
├── docs/                   # Documentação adicional
│   ├── api.md
│   └── schema.md
├── empresa/
│   ├── publicar.php        # Form de publicação de vaga
│   ├── update.php          # Endpoint update perfil
│   └── exportar.php        # Export CSV de candidaturas
├── includes/
│   ├── auth.php            # Funções de autenticação e helpers
│   ├── header.php          # Header + navbar (incluído em todas)
│   └── footer.php          # Footer + script LGPD
├── perfil/
│   └── aluno.php           # Perfil público do aluno (empresa visualiza)
├── tests/                  # Testes smoke
│   └── run.php
├── uploads/                # Arquivos enviados pelos usuarios
│   ├── .htaccess           # Bloqueia execucao de PHP
│   ├── curriculos/         # PDFs de CV dos alunos
│   ├── fotos/              # Fotos de perfil dos alunos
│   └── logos/              # Logos das empresas
├── .github/workflows/      # CI (lint PHP)
├── index.php               # Landing
├── login.php
├── register.php
├── logout.php
├── forgot-password.php
├── reset-password.php
├── vagas.php               # Listagem com filtros
├── vaga.php                # Detalhe + candidatura
├── chat.php                # Chat aluno/empresa por candidatura
├── privacidade.php
├── termos.php
├── manifest.json           # PWA manifest
├── service-worker.js       # PWA cache
└── setup.sql               # Schema completo + seeds
```

## Documentação detalhada

- **[Rotas e endpoints](docs/api.md)** — todas as URLs com método HTTP, parâmetros e permissões
- **[Schema do banco](docs/schema.md)** — tabelas, campos, relacionamentos e índices

## Testes

Smoke tests sem dependências externas:

```bash
php tests/run.php
```

Roda checagens básicas:
- Conexão com o banco
- Hash de senha (`password_verify`)
- Funções de auth (`isLoggedIn`, `isAluno`, `isEmpresa`)
- Helper de CNPJ (`formatarCNPJ`)
- Validação de CNPJ via BrasilAPI (requer internet)

## Contribuindo

1. Crie uma branch a partir de `main` no padrão `feat/<funcionalidade>` ou
   `fix/<o-que-corrige>`.
2. Faça commits descritivos em português, no imperativo.
3. Rode os testes locais (`php tests/run.php`) antes de abrir o PR.
4. O CI valida o lint de todos os `.php` automaticamente em cada PR.

## Licença

Projeto acadêmico desenvolvido no IFRS. Uso livre para fins educacionais.
