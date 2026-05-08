CREATE DATABASE IF NOT EXISTS estagios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE estagios;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

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
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    nome_empresa VARCHAR(200) NOT NULL,
    descricao TEXT,
    cidade VARCHAR(100),
    estado CHAR(2),
    setor VARCHAR(100),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    area VARCHAR(100),
    cidade VARCHAR(100),
    estado CHAR(2),
    modalidade ENUM('presencial','remoto','hibrido') DEFAULT 'presencial',
    bolsa DECIMAL(10,2),
    carga_horaria INT,
    ativa TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Tech Solutions', 'tech@example.com', '$2y$10$placeholderhash', 'empresa');

INSERT INTO empresas (usuario_id, nome_empresa, cidade, estado, setor) VALUES
(1, 'Tech Solutions', 'São Paulo', 'SP', 'Tecnologia');

INSERT INTO vagas (empresa_id, titulo, descricao, area, cidade, estado, modalidade, bolsa, carga_horaria) VALUES
(1, 'Estágio em Desenvolvimento Web', 'Trabalhe com React e Node.js.', 'Tecnologia', 'São Paulo', 'SP', 'hibrido', 1500.00, 30),
(1, 'Estágio em Data Science', 'Análise de dados com Python e SQL.', 'Dados', 'São Paulo', 'SP', 'remoto', 1800.00, 20);
