CREATE TABLE IF NOT EXISTS scp_alertas_cracha (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  aluno_id INT UNSIGNED NOT NULL,
  qr_token CHAR(64) NOT NULL,
  nome_informante VARCHAR(150) NULL,
  telefone_informante VARCHAR(30) NULL,
  mensagem VARCHAR(500) NULL,
  latitude VARCHAR(50) NULL,
  longitude VARCHAR(50) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  status ENUM('novo','visualizado','resolvido') NOT NULL DEFAULT 'novo',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  visualizado_em DATETIME NULL,
  resolvido_em DATETIME NULL,
  FOREIGN KEY(aluno_id) REFERENCES scp_alunos(id) ON DELETE CASCADE,
  INDEX(status,created_at),
  INDEX(aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
