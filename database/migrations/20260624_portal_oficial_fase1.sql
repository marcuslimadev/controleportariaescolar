ALTER TABLE scp_usuarios MODIFY perfil ENUM('admin','secretaria','portaria','professor') NOT NULL;

CREATE TABLE IF NOT EXISTS scp_professores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  telefone VARCHAR(30) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(usuario_id) REFERENCES scp_usuarios(id) ON DELETE SET NULL,
  INDEX(usuario_id),
  INDEX(ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scp_professor_turma (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id INT UNSIGNED NOT NULL,
  turma_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(professor_id) REFERENCES scp_professores(id) ON DELETE CASCADE,
  FOREIGN KEY(turma_id) REFERENCES scp_turmas(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_professor_turma (professor_id, turma_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scp_posts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  autor_id INT UNSIGNED NOT NULL,
  tipo ENUM('comunicado','atividade','evento','programação','alerta','cardápio','lembrete') NOT NULL DEFAULT 'comunicado',
  titulo VARCHAR(190) NOT NULL,
  conteudo TEXT NOT NULL,
  imagem_url VARCHAR(255) NULL,
  publico ENUM('toda_escola','turma','aluno','equipe') NOT NULL DEFAULT 'toda_escola',
  turma_id INT UNSIGNED NULL,
  aluno_id INT UNSIGNED NULL,
  data_evento DATE NULL,
  hora_evento TIME NULL,
  local VARCHAR(190) NULL,
  importante TINYINT(1) NOT NULL DEFAULT 0,
  exige_ciencia TINYINT(1) NOT NULL DEFAULT 0,
  fixado TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('rascunho','publicado','arquivado') NOT NULL DEFAULT 'rascunho',
  publicado_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(autor_id) REFERENCES scp_usuarios(id),
  FOREIGN KEY(turma_id) REFERENCES scp_turmas(id) ON DELETE SET NULL,
  FOREIGN KEY(aluno_id) REFERENCES scp_alunos(id) ON DELETE SET NULL,
  INDEX(status, publicado_em),
  INDEX(tipo, data_evento),
  INDEX(publico, turma_id, aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scp_post_curtidas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(post_id) REFERENCES scp_posts(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  FOREIGN KEY(usuario_id) REFERENCES scp_usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_post_responsavel (post_id, responsavel_id),
  UNIQUE KEY uniq_post_usuario (post_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scp_post_ciencias (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  FOREIGN KEY(post_id) REFERENCES scp_posts(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  FOREIGN KEY(usuario_id) REFERENCES scp_usuarios(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_ciencia_responsavel (post_id, responsavel_id),
  UNIQUE KEY uniq_ciencia_usuario (post_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scp_avisos_falta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NOT NULL,
  turma_id INT UNSIGNED NULL,
  data_falta DATE NOT NULL,
  motivo VARCHAR(80) NOT NULL,
  observacao TEXT NULL,
  anexo_url VARCHAR(255) NULL,
  status ENUM('enviado','visualizado','abonado','rejeitado') NOT NULL DEFAULT 'enviado',
  visualizado_em DATETIME NULL,
  analisado_por INT UNSIGNED NULL,
  analisado_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(aluno_id) REFERENCES scp_alunos(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  FOREIGN KEY(turma_id) REFERENCES scp_turmas(id) ON DELETE SET NULL,
  FOREIGN KEY(analisado_por) REFERENCES scp_usuarios(id) ON DELETE SET NULL,
  INDEX(data_falta, status),
  INDEX(turma_id, data_falta),
  INDEX(responsavel_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
