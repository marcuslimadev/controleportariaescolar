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
- Smoke HTTP não destrutivo criado e acoplado ao deploy.
- CI bloqueia regressão de SQL direto em `public/`.
- Deploy roda lint, testes unitários e smoke HTTP.
- CI básico para lint PHP/JSON e teste unitário leve.

## Progresso estimado

- Base aproveitável do 2.0 no MVP atual: 100%.
- Restante estimado: 0%.

## Próximas fases

### Fase 0 — Base técnica

- Concluída para o escopo viável do MVP atual.

### Fase 1 — Portaria 2.0

- Melhorar o leitor QR com estado claro: procurando, encontrado, entrada, saída, erro.
- Aceitar token puro e URL pública.
- Preservar câmera traseira como padrão no PWA.
- Preparar modo offline para registros pendentes.

### Fase 2 — Comunicação

- Evoluir publicações com anexos, comentários moderados e notificações.
- Manter exclusão/edição com permissão por perfil.
- Criar histórico de ciência para comunicados importantes.

### Fase 3 — Portal público

- Separar conteúdos públicos de conteúdos internos.
- Manter publicações públicas sem dados sensíveis.
- Melhorar eventos e galeria.

### Fase 4 — Responsáveis

- Crachá digital por responsável.
- Histórico permitido por aluno.
- Solicitações e autorizações de retirada.

### Fase 5 — Relatórios e PWA

- Relatórios exportáveis.
- Dashboard de acessos e comunicados.
- Melhorias offline do PWA.
- Smoke tests HTTP pós-deploy e hardening final concluídos.
