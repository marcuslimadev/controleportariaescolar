CREATE DATABASE IF NOT EXISTS controle_portaria_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_portaria_escolar;

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('admin', 'portaria') NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE turmas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE professoras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alunos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE NULL,
    data_nascimento DATE NULL,
    turma_id INT NULL,
    professora_id INT NULL,
    foto LONGBLOB,
    token_qrcode VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL,
    FOREIGN KEY (professora_id) REFERENCES professoras(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE responsaveis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE NULL,
    telefone VARCHAR(20),
    email VARCHAR(150),
    foto LONGBLOB,
    senha VARCHAR(255) NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aluno_responsavel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    responsavel_id INT NOT NULL,
    parentesco VARCHAR(50),
    autorizado_consulta BOOLEAN DEFAULT TRUE,
    autorizado_retirada BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel_id) REFERENCES responsaveis(id) ON DELETE CASCADE,
    UNIQUE KEY unique_aluno_responsavel (aluno_id, responsavel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registros_acesso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    tipo_movimento ENUM('entrada', 'saida') NOT NULL,
    data_hora DATETIME NOT NULL,
    usuario_portaria_id INT,
    observacao TEXT,
    ip_origem VARCHAR(45),
    dispositivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_portaria_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE crachas_emitidos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    numero_cracha VARCHAR(50) UNIQUE NOT NULL,
    data_emissao DATE NOT NULL,
    arquivo_url VARCHAR(500),
    status ENUM('ativo', 'cancelado', 'expirado') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alertas_cracha (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    token_qrcode VARCHAR(255) NOT NULL,
    nome_informante VARCHAR(150),
    telefone_informante VARCHAR(30),
    mensagem TEXT,
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    ip_origem VARCHAR(45),
    dispositivo VARCHAR(255),
    status ENUM('novo', 'visualizado', 'resolvido') DEFAULT 'novo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visualizado_em DATETIME,
    resolvido_em DATETIME,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_auditoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NULL,
    acao VARCHAR(255) NOT NULL,
    tabela_afetada VARCHAR(100),
    id_registro INT,
    dados_anteriores JSON,
    dados_novos JSON,
    ip_origem VARCHAR(45),
    user_agent TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
