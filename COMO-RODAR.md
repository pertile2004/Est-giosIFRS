# Como rodar o projeto (guia de apresentação)

Guia rápido para rodar o InternSHIP Connect em qualquer PC (ex.: o da escola) sem erros.

## Pré-requisitos
- **XAMPP** instalado, com **Apache** e **MySQL** (PHP **8.0 ou superior**).

## Passo a passo

1. **Iniciar o XAMPP**
   - Abrir o *XAMPP Control Panel* → **Start** no **Apache** e no **MySQL**.

2. **Colocar o código na pasta certa**
   - O código TEM que ficar em **`C:\xampp\htdocs\teste`**.
   - ⚠️ A pasta **precisa se chamar `teste`** — o site usa links absolutos `/teste/...`. Se o nome for outro, os links quebram.
   - Opção A (git): dentro de `C:\xampp\htdocs`, rodar:
     ```
     git clone https://github.com/pertile2004/Est-giosIFRS teste
     ```
   - Opção B (sem git): baixar o ZIP no GitHub (botão verde **Code → Download ZIP**), extrair e renomear a pasta para **`teste`** dentro de `C:\xampp\htdocs`.

3. **Criar o banco de dados**
   - Abrir **http://localhost/phpmyadmin**
   - Aba **Importar** → **Escolher arquivo** → selecionar `C:\xampp\htdocs\teste\setup.sql` → **Executar**.
   - Isso cria o banco `estagios` com todas as tabelas e as contas de demonstração.

4. **Abrir o site**
   - **http://localhost/teste/**

## Contas para a apresentação (senha entre parênteses)

| Papel        | E-mail                                      | Senha     |
|--------------|---------------------------------------------|-----------|
| Coordenação  | coordenacao@internshipconnect.com.br        | coord123  |
| Empresa      | rh@techsolutions.com                        | senha123  |
| Aluno        | joao@email.com                              | senha123  |

## Para NÃO dar erro
- **Use login por e-mail/senha.** Os botões **Google/GitHub** precisam de configuração extra (`config/oauth.local.php`, que não vai pro GitHub) e **não funcionam** na apresentação.
- **Instalação nova:** basta o `setup.sql` — ele já traz tudo (coordenação, mensagens, restrição de vagas).
- **Se o banco `estagios` já existir de uma versão antiga**, rode UMA vez para atualizar:
  ```
  C:\xampp\php\php.exe migracao-coordenacao.php
  ```
- Confira a versão do PHP: precisa ser **8.0+** (o projeto usa recursos novos da linguagem).

## Roteiro sugerido da demo
1. **Aluno** — buscar vaga, usar os filtros (faixa de bolsa), se candidatar, conversar pelo chat.
2. **Empresa** — publicar vaga (carga horária livre), ver candidaturas, **aprovar** um candidato (ele vai para a seção *Aprovados*).
3. **Coordenação** — receber a mensagem enviada na página **Contato**, **restringir** uma vaga que viole as políticas.
