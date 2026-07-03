CREATE DATABASE IF NOT EXISTS estagios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estagios;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno','empresa','coordenacao') NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    curso VARCHAR(150),
    universidade VARCHAR(200),
    semestre INT,
    cidade VARCHAR(100),
    estado CHAR(2),
    sobre TEXT,
    linkedin VARCHAR(255),
    github VARCHAR(255),
    foto VARCHAR(255),
    curriculo_path VARCHAR(255),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    nome_empresa VARCHAR(200) NOT NULL,
    cnpj VARCHAR(20),
    descricao TEXT,
    site VARCHAR(255),
    cidade VARCHAR(100),
    estado CHAR(2),
    setor VARCHAR(100),
    logo VARCHAR(255),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    beneficios TEXT,
    area VARCHAR(100),
    cidade VARCHAR(100),
    estado CHAR(2),
    modalidade ENUM('presencial','remoto','hibrido') DEFAULT 'presencial',
    bolsa DECIMAL(10,2),
    carga_horaria INT,
    ativa TINYINT(1) DEFAULT 1,
    restrita TINYINT(1) DEFAULT 0,
    motivo_restricao VARCHAR(255) DEFAULT NULL,
    destaque TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    vaga_id INT NOT NULL,
    status ENUM('pendente','visualizado','aprovado','recusado') DEFAULT 'pendente',
    carta TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_candidatura (aluno_id, vaga_id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oauth_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    provider ENUM('google','github') NOT NULL,
    provider_user_id VARCHAR(100) NOT NULL,
    email VARCHAR(200),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_provider_user (provider, provider_user_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vagas_favoritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    vaga_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorita (aluno_id, vaga_id),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidatura_id INT NOT NULL,
    remetente_tipo ENUM('aluno','empresa') NOT NULL,
    conteudo TEXT NOT NULL,
    lida TINYINT(1) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidatura_id) REFERENCES candidaturas(id) ON DELETE CASCADE,
    INDEX idx_candidatura (candidatura_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens enviadas pela página pública de Contato (lidas pela Coordenação)
CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('nova','lida','resolvida') NOT NULL DEFAULT 'nova',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respostas (chat) entre coordenação e usuario dentro de uma mensagem de contato
CREATE TABLE IF NOT EXISTS respostas_contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensagem_id INT NOT NULL,
    remetente_tipo ENUM('coordenacao','usuario') NOT NULL,
    conteudo TEXT NOT NULL,
    lida TINYINT(1) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensagem_id) REFERENCES mensagens_contato(id) ON DELETE CASCADE,
    INDEX idx_msg (mensagem_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Senha de todos os usuarios seed (empresas + alunos): 123456
-- Senha da coordenacao: coord123
-- Para promover qualquer usuario a admin depois:
--   UPDATE usuarios SET is_admin = 1 WHERE email = 'rh@techsolutions.com';
INSERT INTO usuarios (nome, email, senha, tipo, is_admin) VALUES
('Tech Solutions Ltda', 'rh@techsolutions.com', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'empresa', 0),
('InovaCorp', 'rh@inovacorp.com.br', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'empresa', 0),
('StartupBR', 'vagas@startupbr.io', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'empresa', 0),
('João Pedro Pertile', 'jp20042009@gmail.com', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'aluno', 0),
('Derick Visintiner Sonda', 'derick.v.sonda@gmail.com', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'aluno', 0),
('Luiza Demartini da Costa', 'demartinidacostaluiza@gmail.com', '$2y$10$hh09wHWPNNQn0Eg0q1k7q.2vshE7aLPCtDKOTMQb7CIaNgMpJQzO2', 'aluno', 0),
('Coordenação InternSHIP', 'coordenacao@internshipconnect.com.br', '$2y$10$97dAx2d12lNzl7PjyFJuguWN914Evwr6sHvF7GBkYHy6b.uijBn02', 'coordenacao', 1);

INSERT INTO empresas (usuario_id, nome_empresa, descricao, cidade, estado, setor) VALUES
(1, 'Tech Solutions', 'Empresa líder em soluções de software para a Serra Gaúcha.', 'Bento Gonçalves', 'RS', 'Tecnologia'),
(2, 'InovaCorp', 'Startup de inovação focada em IA e Machine Learning.', 'Caxias do Sul', 'RS', 'Inteligência Artificial'),
(3, 'StartupBR', 'Aceleradora de startups com foco em fintech e edtech.', 'Porto Alegre', 'RS', 'Fintech');

INSERT INTO alunos (usuario_id, curso, universidade, semestre, cidade, estado) VALUES
(4, 'Ciência da Computação', 'IFRS - Campus Bento Gonçalves', 6, 'Bento Gonçalves', 'RS'),
(5, 'Sistemas de Informação', 'IFRS - Campus Caxias do Sul', 4, 'Caxias do Sul', 'RS'),
(6, 'Análise e Desenvolvimento de Sistemas', 'IFRS - Campus Farroupilha', 5, 'Farroupilha', 'RS');

-- Duas vagas por empresa (foco em Bento Gonçalves e Carlos Barbosa)
INSERT INTO vagas (empresa_id, titulo, descricao, requisitos, beneficios, area, cidade, estado, modalidade, bolsa, carga_horaria, destaque) VALUES
-- Tech Solutions
(1, 'Estágio em Desenvolvimento Web',
 'Trabalhe no desenvolvimento de aplicações web modernas usando PHP, React e Node.js. Você vai participar do ciclo completo: análise de requisitos, implementação, testes e deploy. Nossa equipe atua com metodologias ágeis (Scrum) e você terá mentoria constante de devs sêniores.',
 'Cursando Ciência da Computação, Sistemas de Informação ou ADS. Conhecimento básico em HTML, CSS e JavaScript. Vontade de aprender back-end (PHP ou Node). Boa comunicação e trabalho em equipe.',
 'Bolsa R$ 1.500, Vale-refeição R$ 35/dia, Vale-transporte, Plano de saúde, Horário flexível',
 'Desenvolvimento Web', 'Bento Gonçalves', 'RS', 'hibrido', 1500.00, 30, 1),
(1, 'Estágio em Desenvolvimento Mobile',
 'Participe do desenvolvimento de aplicativos mobile híbridos com React Native. Você vai trabalhar em apps reais em produção, integrando com APIs REST, resolvendo bugs e implementando novas features. Terá contato direto com nossa equipe de UX e produto.',
 'Cursando Ciência da Computação, Sistemas de Informação ou ADS. Conhecimento em JavaScript. Já ter mexido com React ou React Native é diferencial. Disposição para aprender.',
 'Bolsa R$ 1.600, Vale-refeição R$ 35/dia, Vale-transporte, Plano de saúde, Dispositivo de testes fornecido',
 'Desenvolvimento Mobile', 'Bento Gonçalves', 'RS', 'hibrido', 1600.00, 30, 0),
-- InovaCorp
(2, 'Estágio em Data Science e IA',
 'Ajude a construir modelos de machine learning que impactam decisões reais. Você vai trabalhar com Python, Pandas, scikit-learn e frameworks de deep learning. Nossos projetos vão desde análise preditiva até processamento de linguagem natural.',
 'Cursando Engenharia, Ciência da Computação ou Estatística. Conhecimento em Python. Interesse em estatística e machine learning. Inglês para leitura técnica.',
 'Bolsa R$ 1.800, Vale-refeição R$ 40/dia, Vale-transporte, Plano de saúde, Auxílio home-office',
 'Data Science', 'Carlos Barbosa', 'RS', 'hibrido', 1800.00, 30, 1),
(2, 'Estágio em Engenharia de Dados',
 'Construa pipelines de dados robustos que alimentam nossos modelos de IA. Você vai trabalhar com Airflow, PostgreSQL, BigQuery e ferramentas de streaming (Kafka). É a oportunidade perfeita pra quem quer entrar no mundo de big data.',
 'Cursando Ciência da Computação, Engenharia ou áreas correlatas. SQL sólido. Python para scripts de ETL. Interesse em arquitetura de dados e cloud (AWS ou GCP).',
 'Bolsa R$ 1.700, Vale-refeição R$ 40/dia, Vale-transporte, Plano de saúde, Certificações pagas (AWS/GCP)',
 'Data Science', 'Carlos Barbosa', 'RS', 'hibrido', 1700.00, 30, 0),
-- StartupBR
(3, 'Estágio em Marketing Digital',
 'Aprenda na prática growth marketing, SEO, mídia paga e análise de métricas. Você vai criar e executar campanhas reais em Google Ads, Meta Ads e LinkedIn, além de analisar o funil de conversão e propor melhorias.',
 'Cursando Marketing, Publicidade, Administração ou correlatos. Criativo e analítico. Conhecimento básico em Google Ads e redes sociais. Boa escrita.',
 'Bolsa R$ 1.200, Vale-refeição, Vale-transporte, Cursos e certificações pagos',
 'Marketing Digital', 'Bento Gonçalves', 'RS', 'presencial', 1200.00, 25, 0),
(3, 'Estágio em Produto (Product Management)',
 'Aprenda a construir produtos digitais do zero. Você vai apoiar nossos PMs na definição do roadmap, priorização de features, pesquisa com usuários e análise de dados de uso. Contato direto com engenharia, design e stakeholders.',
 'Cursando Administração, Engenharia, Ciência da Computação ou correlatos. Pensamento analítico. Interesse em produtos digitais e startups. Boa comunicação escrita e oral.',
 'Bolsa R$ 1.400, Vale-refeição, Vale-transporte, Mentoria com PMs sêniores, Acesso a cursos de Product School',
 'Produto', 'Bento Gonçalves', 'RS', 'hibrido', 1400.00, 30, 1);
