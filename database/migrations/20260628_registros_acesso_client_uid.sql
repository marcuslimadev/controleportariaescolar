ALTER TABLE scp_registros_acesso ADD COLUMN client_uid VARCHAR(80) NULL AFTER ip;
CREATE UNIQUE INDEX uniq_registros_acesso_client_uid ON scp_registros_acesso(client_uid);
