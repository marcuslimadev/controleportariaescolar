ALTER TABLE scp_posts
  MODIFY publico ENUM('publico','toda_escola','turma','aluno','equipe') NOT NULL DEFAULT 'toda_escola';
