CREATE TABLE IF NOT EXISTS scp_login_tentativas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chave CHAR(64) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  login_hash CHAR(64) NULL,
  tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_ate DATETIME NULL,
  ultima_tentativa DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_login_tentativas_chave (chave),
  INDEX idx_login_tentativas_bloqueio (bloqueado_ate),
  INDEX idx_login_tentativas_ip (ip, ultima_tentativa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
