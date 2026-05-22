CREATE DATABASE IF NOT EXISTS estagios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estagios;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno','empresa') NOT NULL,
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
    destaque TINYINT(1) DEFAULT 0,
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

-- Senha de todos os usuarios seed: senha123
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Tech Solutions Ltda', 'rh@techsolutions.com', '$2y$10$fdi6dZAy.8whVjkaSPy36udFh43DjplufxfAqobwMmjcuaD95cmm.', 'empresa'),
('InovaCorp', 'rh@inovacorp.com.br', '$2y$10$mGniEL9zZ09CsujHtxAa9.B3n39cH.esZ3R5g9029AwxrgnLdDhWy', 'empresa'),
('StartupBR', 'vagas@startupbr.io', '$2y$10$X07bJkKSO23PuZGPa78sxuiBe1IKKypDNCfehzDWnb1IP64WETmfG', 'empresa'),
('João Silva', 'joao@email.com', '$2y$10$Eb9GBnNG3n.7keF6ZBiymODhF2UKshz7bn1ciJVSXYRwNu4PgVdNe', 'aluno');

INSERT INTO empresas (usuario_id, nome_empresa, descricao, cidade, estado, setor) VALUES
(1, 'Tech Solutions', 'Empresa líder em soluções de software para a Serra Gaúcha.', 'Bento Gonçalves', 'RS', 'Tecnologia'),
(2, 'InovaCorp', 'Startup de inovação focada em IA e Machine Learning.', 'Caxias do Sul', 'RS', 'Inteligência Artificial'),
(3, 'StartupBR', 'Aceleradora de startups com foco em fintech e edtech.', 'Porto Alegre', 'RS', 'Fintech');

INSERT INTO alunos (usuario_id, curso, universidade, semestre, cidade, estado) VALUES
(4, 'Ciência da Computação', 'IFRS', 6, 'Bento Gonçalves', 'RS');

INSERT INTO vagas (empresa_id, titulo, descricao, requisitos, beneficios, area, cidade, estado, modalidade, bolsa, carga_horaria, destaque) VALUES
(1, 'Estágio em Desenvolvimento Web', 'Você irá trabalhar no desenvolvimento de aplicações web modernas usando React e Node.js. Aprenderá boas práticas de desenvolvimento, metodologias ágeis e trabalhará em um time multidisciplinar.', 'Cursando TI, Sistemas ou áreas relacionadas. Conhecimento básico em HTML, CSS e JavaScript. Proativo e com vontade de aprender.', 'Bolsa R$ 1.500, Vale-refeição R$ 35/dia, Vale-transporte, Plano de saúde', 'Desenvolvimento Web', 'Bento Gonçalves', 'RS', 'hibrido', 1500.00, 30, 1),
(1, 'Estágio em Data Science', 'Trabalhe com análise de dados e criação de modelos preditivos usando Python e SQL. Você terá acesso a grandes volumes de dados reais e aprenderá com nosso time de cientistas de dados sêniores.', 'Cursando Estatística, Matemática, Ciência da Computação. Python básico. Interesse em dados e machine learning.', 'Bolsa R$ 1.800, Vale-refeição, Vale-transporte, Auxílio home-office', 'Dados', 'Bento Gonçalves', 'RS', 'remoto', 1800.00, 20, 1),
(2, 'Estágio em UX/UI Design', 'Crie experiências digitais incríveis! Você vai participar de todo o processo de design, desde a pesquisa com usuários até a entrega das interfaces para o time de desenvolvimento.', 'Cursando Design, Publicidade ou áreas correlatas. Conhecimento em Figma. Portfolio apresentando projetos pessoais.', 'Bolsa R$ 1.200, Vale-refeição, Vale-transporte, Licenças de software', 'Design', 'Caxias do Sul', 'RS', 'presencial', 1200.00, 30, 0),
(2, 'Estágio em Machine Learning', 'Ajude a construir modelos de IA de ponta! Trabalhe com nossos engenheiros de ML em projetos que impactam milhões de usuários.', 'Cursando Engenharia, Computação ou Matemática. Python e bibliotecas de ML (sklearn, pandas). Inglês intermediário.', 'Bolsa R$ 2.000, Vale-refeição R$ 40/dia, Vale-transporte, Stock options', 'Inteligência Artificial', 'Carlos Barbosa', 'RS', 'hibrido', 2000.00, 30, 1),
(3, 'Estágio em Marketing Digital', 'Aprenda na prática sobre estratégias de growth, SEO, redes sociais e performance. Você terá autonomia para criar e executar campanhas reais.', 'Cursando Marketing, Publicidade ou áreas relacionadas. Criativo e analítico. Conhecimento básico em Google Ads e Meta Ads.', 'Bolsa R$ 1.000, Vale-refeição, Vale-transporte', 'Marketing', 'Garibaldi', 'RS', 'hibrido', 1000.00, 20, 0),
(3, 'Estágio em Produto (Product Management)', 'Aprenda a construir produtos digitais do zero. Você vai trabalhar na definição do roadmap, priorização de features e comunicação com stakeholders.', 'Cursando Administração, Engenharia ou Ciência da Computação. Pensamento analítico. Interesse em produtos digitais.', 'Bolsa R$ 1.600, Vale-refeição, Vale-transporte, Mentoria com PMs sêniores', 'Produto', 'Porto Alegre', 'RS', 'remoto', 1600.00, 30, 0);
