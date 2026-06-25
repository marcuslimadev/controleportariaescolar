ALTER TABLE scp_responsaveis ADD COLUMN qr_token CHAR(64) NULL UNIQUE AFTER foto;

ALTER TABLE scp_registros_acesso ADD COLUMN responsavel_id INT UNSIGNED NULL AFTER aluno_id;

CREATE TABLE IF NOT EXISTS scp_crachas_responsavel_emitidos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  responsavel_id INT UNSIGNED NOT NULL,
  emitido_por INT UNSIGNED NOT NULL,
  emitido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  token_no_momento CHAR(64) NOT NULL,
  FOREIGN KEY(responsavel_id) REFERENCES scp_responsaveis(id),
  FOREIGN KEY(emitido_por) REFERENCES scp_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
