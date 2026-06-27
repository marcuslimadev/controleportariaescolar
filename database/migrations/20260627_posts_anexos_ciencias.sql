ALTER TABLE scp_posts
  ADD COLUMN anexo_url VARCHAR(255) NULL AFTER imagem_url,
  ADD COLUMN anexo_nome VARCHAR(190) NULL AFTER anexo_url;

