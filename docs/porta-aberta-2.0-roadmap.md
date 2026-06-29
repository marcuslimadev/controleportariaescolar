# Porta Aberta Escolar 2.0

Roadmap técnico para evoluir o MVP atual sem interromper o uso em produção.

## Prioridade imediata

- Manter o fluxo atual funcionando: login único, portaria, crachá, portal público e comunicação.
- Fazer melhorias por camadas pequenas e publicáveis.
- Evitar reescrita completa antes de haver testes e endpoints cobertos.

## Segurança aplicada na base atual

- CSRF centralizado nos formulários sensíveis.
- Regeneração de sessão após login.
- Headers de segurança globais.
- Limite de tentativas de login por IP + identificador informado.
- Auditoria para ações críticas.
- Primeiro soft delete em publicações.
- Primeiro service com contrato/repository para exclusão de posts.
- Primeiro service com contrato/repository para registro de acesso da portaria.
- Lookup do QR da portaria migrado para service/repository.
- Permissões granulares iniciadas com mapa central por perfil.
- Endpoints JSON críticos retornam 403 em JSON quando sem permissão.
- Serviço central de senha com Argon2id quando disponível.
- Login faz rehash transparente de senhas antigas.
- Auditoria passou a registrar user-agent.
- `composer.json` criado com PSR-4 para `App\\`.
- Fluxo de convites da portaria iniciou migração para service/repository.
- Cadastro público da família via convite migrado para service/repository.
- Login único migrado para `AuthService` com repositories.
- Criação e edição de publicações migradas para `PostService`.
- Avisos de falta migrados para `AbsenceService` com repository.
- Frequência do professor migrada para `FrequencyService` com repository.
- Listagem de avisos do professor passou a reutilizar `AbsenceService`.
- Painel de cadastros da escola migrado para `SchoolAdminService`.
- Portal do responsável e histórico de movimentações migrados para `GuardianPortalService`.
- Relatório administrativo de acessos migrado para `ReportService`.
- Pendências da portaria passaram a usar `InviteService`.
- Portal público de emergência do QR migrado para `EmergencyBadgeService`.
- Curtidas e ciência de comunicados migradas para `PostInteractionService`.
- Timeline, eventos, portal público de login e gestão de publicações migrados para leituras pelo `PostService`.
- Crachá digital do responsável e QR de segurança migrados para `BadgeService`.
- Cadastro rápido da portaria migrado para `QuickRegistrationService`.
- Conferência de cadastro por convite passou a usar `InviteService`.
- Páginas públicas, de portaria e administrativas principais ficaram sem SQL direto.
- Instanciação de services centralizada em `ServiceFactory`.
- `DatabaseAuditLogger` passou a gravar direto no banco, sem depender da função global `audit()`.
- `InviteService` passou a exigir `PDO` tipado na transação de aprovação.
- Exemplos e defaults de deploy foram higienizados para não expor IP, usuário e caminho real de produção.
- CSP final sem `unsafe-inline`, com nonce por requisição para scripts inline.
- Soft delete adicionado em alunos, responsáveis e usuários, com filtros nas consultas principais.
- Smoke HTTP não destrutivo criado e acoplado ao deploy.
- CI bloqueia regressão de SQL direto em `public/`.
- Deploy roda lint, testes unitários e smoke HTTP.
- CI básico para lint PHP/JSON e teste unitário leve.
- Deploy preserva uploads em produção e publica o app no `public_html`.
- PWA usa ícone oficial atualizado, prompt de instalação/atualização e fallback offline.
- Portaria possui fila offline com IndexedDB e idempotência por `client_uid`.
- Usuários internos e responsáveis podem definir foto de perfil.
- Feed público e privado exibem avatar/nome do autor da publicação.
- Dashboard administrativo criado com indicadores de portaria e comunicação.
- Relatórios administrativos exportam CSV.
- Portal do responsável mostra resumo dos filhos, último status de entrada/saída e exportação CSV.
- Galeria pública e gestão administrativa de publicações possuem filtros.
- Agenda de eventos possui resumo mensal e navegação mês anterior/próximo.

## Progresso estimado

- Base aproveitável do 2.0 no MVP atual: 100%.
- Fase 1 — Portaria 2.0: 95%.
- Fase 2 — Comunicação: 92%.
- Fase 3 — Portal público: 92%.
- Fase 4 — Responsáveis: 94%.
- Fase 5 — Relatórios e PWA: 94%.
- Progresso geral estimado do roadmap atual: 94%.
- Restante estimado antes de abrir o roadmap de cursos: 6%.

## Próximas fases

### Fase 0 — Base técnica

- Concluída para o escopo viável do MVP atual.

### Fase 1 — Portaria 2.0

- Concluído: leitor QR com estado claro: pronto, procurando, encontrado, entrada, saída, erro e sucesso.
- Concluído: aceita token puro e URL pública.
- Concluído: prioriza câmera traseira no PWA.
- Concluído: fila offline para registros pendentes.
- Concluído: IndexedDB como banco local do PWA, com fallback e migração de `localStorage`.
- Pendente: tela administrativa para auditar/sinalizar registros sincronizados posteriormente.

### Fase 2 — Comunicação

- Concluído: publicações com anexos.
- Concluído: comentários moderados.
- Concluído: notificações internas.
- Concluído: exclusão/edição com permissão por perfil.
- Concluído: histórico de ciência para comunicados importantes.
- Concluído: indicadores de engajamento na lista administrativa.
- Concluído: filtros administrativos por busca, status, público e tipo.
- Pendente: exportação CSV do histórico de ciência/comentários, se necessário.

### Fase 3 — Portal público

- Concluído: conteúdos públicos e internos separados.
- Concluído: feed público sem dados sensíveis.
- Concluído: galeria pública com filtros por busca/tipo.
- Concluído: eventos com resumo mensal e navegação.
- Pendente: revisão visual final da home pública em celulares reais.

### Fase 4 — Responsáveis

- Concluído: crachá digital por responsável.
- Concluído: histórico permitido por aluno.
- Concluído: solicitações e autorizações de retirada.
- Concluído: resumo de filhos, status dentro/fora da escola e exportação CSV.
- Pendente: detalhe individual por aluno, com histórico dedicado.

### Fase 5 — Relatórios e PWA

- Concluído: relatórios exportáveis.
- Concluído: dashboard de acessos e comunicados.
- Concluído: melhorias offline do PWA.
- Concluído: smoke tests HTTP pós-deploy.
- Concluído: hardening final aplicado ao escopo atual.
- Pendente: checklist manual final por perfil em produção.

## Pendências finais antes do módulo de cursos

1. Criar detalhe individual do aluno para o responsável.
2. Adicionar exportação CSV de ciência/comentários, se for útil para operação.
3. Revisar visualmente home pública, agenda, galeria, portal do responsável e portaria em celular real.
4. Registrar um checklist de teste manual por perfil: admin, secretaria, professor, portaria e responsável.
5. Ajustar pequenos textos/traduções restantes que aparecerem no teste manual.

## Próximo roadmap planejado

Após fechar as pendências finais acima, abrir um roadmap separado para a plataforma de cursos gratuitos/interativos para alunos.
