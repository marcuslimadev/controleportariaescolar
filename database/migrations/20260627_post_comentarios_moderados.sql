CREATE TABLE IF NOT EXISTS scp_post_comentarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  responsavel_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  comentario TEXT NOT NULL,
  status ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  moderado_por INT UNSIGNED NULL,
  moderado_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(post_id) REFERENCES scp_posts(id) ON DELETE CASCADE,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id) ON DELETE CASCADE,
  FOREIGN KEY(usuario_id) REFERENCES scp_usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY(moderado_por) REFERENCES scp_usuarios(id) ON DELETE SET NULL,
  INDEX(post_id, status, created_at),
  INDEX(status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

