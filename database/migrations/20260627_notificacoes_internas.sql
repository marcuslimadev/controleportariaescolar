CREATE TABLE IF NOT EXISTS scp_notificacoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  responsavel_id INT UNSIGNED NULL,
  titulo VARCHAR(190) NOT NULL,
  mensagem VARCHAR(255) NOT NULL,
  link VARCHAR(190) NOT NULL DEFAULT 'feed.php',
  origem_tipo VARCHAR(60) NULL,
  origem_id BIGINT UNSIGNED NULL,
  lida_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(usuario_id) REFERENCES scp_usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_notificacao_usuario_origem (usuario_id, origem_tipo, origem_id),
  UNIQUE KEY uniq_notificacao_responsavel_origem (responsavel_id, origem_tipo, origem_id),
  INDEX(usuario_id, lida_em, created_at),
  INDEX(responsavel_id, lida_em, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

