CREATE TABLE IF NOT EXISTS scp_autorizacoes_retirada (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NOT NULL,
  nome_autorizado VARCHAR(150) NOT NULL,
  documento VARCHAR(40) NULL,
  telefone VARCHAR(30) NULL,
  valido_ate DATE NOT NULL,
  observacao VARCHAR(255) NULL,
  status ENUM('ativa','usada','cancelada') NOT NULL DEFAULT 'ativa',
  usado_por INT UNSIGNED NULL,
  usado_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(aluno_id) REFERENCES scp_alunos(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  FOREIGN KEY(usado_por) REFERENCES scp_usuarios(id) ON DELETE SET NULL,
  INDEX(status, valido_ate),
  INDEX(responsavel_id, created_at),
  INDEX(aluno_id, valido_ate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

