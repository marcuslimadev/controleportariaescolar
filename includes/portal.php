<?php
declare(strict_types=1);

function app_setting(string $key, ?string $fallback = null): string {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $query = db()->query('SELECT chave, valor FROM scp_configuracoes');
            foreach ($query->fetchAll() as $row) {
                $settings[(string)$row['chave']] = (string)$row['valor'];
            }
        } catch (Throwable) {}
    }
    $value = trim((string)($settings[$key] ?? ''));
    return $value !== '' ? $value : (string)$fallback;
}

function app_setting_url(string $key, string $fallback = ''): string {
    $value = app_setting($key, '');
    if ($value === '') return $fallback;
    if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
    return url(ltrim($value, '/'));
}

function app_name(): string {
    return app_setting('nome_escola', t('app_name'));
}

function app_tagline(): string {
    return app_setting('texto_institucional', t('app_tagline'));
}

function app_logo_url(): string {
    return app_setting_url('logo_url', asset_url('assets/porta-aberta-logo.jpg'));
}

function app_cover_url(): string {
    return app_setting_url('capa_url', '');
}

function app_primary_color(): string {
    $color = app_setting('cor_principal', '#1356A2');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : '#1356A2';
}

function app_theme(): string {
    static $theme = null;
    if ($theme !== null) return $theme;
    $theme = 'classico';
    try {
        $value = app_setting('tema', 'classico');
        if (in_array($value, ['classico','azul_branco','preto_branco'], true)) {
            $theme = $value;
        }
    } catch (Throwable) {}
    return $theme;
}

function app_theme_class(): string {
    return match (app_theme()) {
        'azul_branco' => 'theme-blue-white',
        'preto_branco' => 'theme-black-white',
        default => 'theme-classic',
    };
}

function current_locale(): string {
    $lang = (string)($_SESSION['lang'] ?? config()['locale'] ?? 'pt');
    return $lang === 'en' ? 'en' : 'pt';
}

function t(string $key): string {
    static $messages = [
        'pt' => [
            'app_tagline' => 'O canal oficial entre escola, família e portaria.',
            'app_name' => 'Porta Aberta Escolar',
            'logout' => 'Sair',
            'language' => 'Idioma',
            'portuguese' => 'Português',
            'english' => 'English',
            'login' => 'Entrar',
            'events' => 'Eventos',
            'public_portal' => 'Portal público',
            'important' => 'Importante',
            'no_public_posts' => 'Nenhuma publicação pública no momento.',
            'welcome' => 'BEM-VINDO',
            'access_account' => 'Acesse sua conta',
            'login_identifier' => 'Usuário, CPF ou telefone',
            'login_placeholder' => 'Digite seu acesso',
            'password' => 'Senha',
            'password_placeholder' => 'Digite sua senha',
            'show_password' => 'Mostrar',
            'hide_password' => 'Ocultar',
            'invalid_login' => 'Usuário ou senha inválidos.',
            'too_many_attempts' => 'Muitas tentativas. Aguarde alguns minutos e tente novamente.',
            'official_portal' => 'PORTAL OFICIAL',
            'timeline' => 'Timeline',
            'new_post' => 'Nova publicação',
            'manage' => 'Gerenciar',
            'quick_actions' => 'Ações rápidas',
            'no_posts' => 'Nenhuma publicação disponível no momento.',
            'pinned' => 'Fixado',
            'science_confirmed' => 'Ciência confirmada em',
            'acknowledge' => 'Li e estou ciente',
            'agenda' => 'AGENDA',
            'events_program' => 'Eventos e programação',
            'events_hint' => 'Próximos eventos e programação oficial da escola.',
            'month' => 'Mês',
            'class' => 'Turma',
            'all' => 'Todas',
            'filter' => 'Filtrar',
            'upcoming' => 'Próximo',
            'past' => 'Passado',
            'no_events' => 'Nenhum evento neste mês.',
        ],
        'en' => [
            'app_tagline' => 'The official channel between school, family, and gatehouse.',
            'app_name' => 'Open School Door',
            'logout' => 'Sign out',
            'language' => 'Language',
            'portuguese' => 'Português',
            'english' => 'English',
            'login' => 'Sign in',
            'events' => 'Events',
            'public_portal' => 'Public portal',
            'important' => 'Important',
            'no_public_posts' => 'No public posts right now.',
            'welcome' => 'WELCOME',
            'access_account' => 'Access your account',
            'login_identifier' => 'User, CPF, or phone',
            'login_placeholder' => 'Enter your access',
            'password' => 'Password',
            'password_placeholder' => 'Enter your password',
            'show_password' => 'Show',
            'hide_password' => 'Hide',
            'invalid_login' => 'Invalid user or password.',
            'too_many_attempts' => 'Too many attempts. Wait a few minutes and try again.',
            'official_portal' => 'OFFICIAL PORTAL',
            'timeline' => 'Timeline',
            'new_post' => 'New post',
            'manage' => 'Manage',
            'quick_actions' => 'Quick actions',
            'no_posts' => 'No posts available right now.',
            'pinned' => 'Pinned',
            'science_confirmed' => 'Acknowledged on',
            'acknowledge' => 'I have read and acknowledge',
            'agenda' => 'AGENDA',
            'events_program' => 'Events and schedule',
            'events_hint' => 'Upcoming events and the official school schedule.',
            'month' => 'Month',
            'class' => 'Class',
            'all' => 'All',
            'filter' => 'Filter',
            'upcoming' => 'Upcoming',
            'past' => 'Past',
            'no_events' => 'No events this month.',
        ],
    ];
    $locale = current_locale();
    return $messages[$locale][$key] ?? $messages['pt'][$key] ?? $key;
}

function lang_url(string $lang): string {
    $query = $_GET;
    $query['lang'] = $lang === 'en' ? 'en' : 'pt';
    $path = ltrim((string)($_SERVER['SCRIPT_NAME'] ?? 'login.php'), '/');
    $prefix = trim(parse_url((string)(config()['base_url'] ?? ''), PHP_URL_PATH) ?: '', '/');
    if ($prefix !== '' && str_starts_with($path, $prefix . '/')) {
        $path = substr($path, strlen($prefix) + 1);
    }
    return url($path . '?' . http_build_query($query));
}

function current_role(): string {
    return !empty($_SESSION['responsavel_id']) ? 'responsavel' : (string)($_SESSION['role'] ?? '');
}

function portal_home(): string {
    $role = current_role();
    if ($role === 'portaria') return 'portaria/index.php';
    if ($role === '') return 'login.php';
    return 'feed.php';
}

function is_staff_role(array $roles): bool {
    return !empty($_SESSION['user_id']) && in_array((string)($_SESSION['role'] ?? ''), $roles, true);
}

function can_manage_posts(): bool {
    return is_staff_role(['admin', 'secretaria']);
}

function portal_nav_items(): array {
    $role = current_role();
    if ($role === 'responsavel') {
        return with_logout_item([
            [t('timeline'), 'feed.php'],
            [current_locale()==='en' ? 'My profile' : 'Meu perfil', 'perfil.php'],
            [current_locale()==='en' ? 'Notifications' : 'Notificações', 'notificacoes.php'],
            [current_locale()==='en' ? 'My children' : 'Meus filhos', 'responsavel/index.php'],
            [current_locale()==='en' ? 'Digital badge' : 'Crachá digital', 'cracha.php'],
            [current_locale()==='en' ? 'Pickup auth.' : 'Autorizações', 'responsavel/autorizacoes.php'],
            [current_locale()==='en' ? 'Report absence' : 'Avisar falta', 'responsavel/avisar-falta.php'],
            [t('events'), 'eventos.php'],
            [current_locale()==='en' ? 'History' : 'Histórico', 'responsavel/index.php'],
        ]);
    }
    if ($role === 'professor') {
        return with_logout_item([
            [t('timeline'), 'feed.php'],
            [current_locale()==='en' ? 'My profile' : 'Meu perfil', 'perfil.php'],
            [current_locale()==='en' ? 'Notifications' : 'Notificações', 'notificacoes.php'],
            [current_locale()==='en' ? 'Attendance' : 'Frequência', 'professor/frequencia.php'],
            [current_locale()==='en' ? 'Absence notices' : 'Avisos de falta', 'professor/avisos-falta.php'],
            [t('events'), 'eventos.php'],
        ]);
    }
    if ($role === 'portaria') {
        return with_logout_item([
            [current_locale()==='en' ? 'QR Reader' : 'Leitor QR Code', 'portaria/index.php'],
            [current_locale()==='en' ? 'My profile' : 'Meu perfil', 'perfil.php'],
            [current_locale()==='en' ? 'Pickup auth.' : 'Autorizações', 'portaria/autorizacoes.php'],
            [current_locale()==='en' ? 'Notifications' : 'Notificações', 'notificacoes.php'],
            [current_locale()==='en' ? 'Invites' : 'Convites', 'portaria/convites.php'],
            [t('timeline'), 'feed.php'],
        ]);
    }
    if (in_array($role, ['admin', 'secretaria'], true)) {
        $items = [
            [t('timeline'), 'feed.php'],
            [current_locale()==='en' ? 'My profile' : 'Meu perfil', 'perfil.php'],
            [current_locale()==='en' ? 'Notifications' : 'Notificações', 'notificacoes.php'],
            [t('new_post'), 'admin/post-form.php'],
            [current_locale()==='en' ? 'Comments' : 'Comentários', 'admin/comentarios.php'],
            [current_locale()==='en' ? 'Reports' : 'Relatórios', 'admin/relatorios.php'],
            [t('events'), 'eventos.php'],
            [current_locale()==='en' ? 'Absence notices' : 'Avisos de falta', 'admin/avisos-falta.php'],
            [current_locale()==='en' ? 'Attendance' : 'Frequência', 'professor/frequencia.php'],
            [current_locale()==='en' ? 'Students' : 'Alunos', 'admin/index.php?tab=alunos'],
            [current_locale()==='en' ? 'Guardians' : 'Responsáveis', 'admin/index.php?tab=responsaveis'],
            [current_locale()==='en' ? 'Classes' : 'Turmas', 'admin/index.php?tab=turmas'],
            [current_locale()==='en' ? 'Links' : 'Vínculos', 'admin/index.php?tab=vinculos'],
        ];
        if ($role === 'admin') $items[] = [current_locale()==='en' ? 'Teachers' : 'Professores', 'admin/index.php?tab=professores'];
        if ($role === 'admin') $items[] = [current_locale()==='en' ? 'Users' : 'Usuários', 'admin/index.php?tab=usuarios'];
        if ($role === 'admin') $items[] = [current_locale()==='en' ? 'Settings' : 'Configurações', 'admin/configuracoes.php'];
        $items[] = [current_locale()==='en' ? 'Gatehouse' : 'Portaria', 'portaria/index.php'];
        $items[] = [current_locale()==='en' ? 'Pickup auth.' : 'Autorizações', 'portaria/autorizacoes.php'];
        return with_logout_item($items);
    }
    return [];
}

function with_logout_item(array $items): array {
    $items[] = [t('logout'), 'logout.php'];
    return $items;
}

function portal_nav_html(): string {
    $items = portal_nav_items();
    if (!$items) return '';
    $current = trim((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $prefix = trim(parse_url((string)(config()['base_url'] ?? ''), PHP_URL_PATH) ?: '', '/');
    if ($prefix !== '' && str_starts_with($current, $prefix . '/')) {
        $current = substr($current, strlen($prefix) + 1);
    }
    $query = (string)($_SERVER['QUERY_STRING'] ?? '');
    $currentQuery = [];
    parse_str($query, $currentQuery);
    $html = '<div class="app-menu-overlay" aria-hidden="true"></div><div id="app-menu" class="app-nav-scroll" aria-label="Menu principal" aria-hidden="true"><div class="app-menu-head"><div><strong>Menu</strong><span>' . e(app_name()) . '</span></div><button class="app-menu-close" type="button" aria-label="Fechar menu">×</button></div><div class="container"><nav class="app-nav">';
    foreach ($items as $index => [$label, $path]) {
        $path = ltrim($path, '/');
        $target = parse_url($path);
        $pathOnly = (string)($target['path'] ?? $path);
        $targetQuery = [];
        parse_str((string)($target['query'] ?? ''), $targetQuery);
        $active = $current === $pathOnly;
        foreach ($targetQuery as $key => $value) {
            $active = $active && (($currentQuery[$key] ?? null) === $value);
        }
        $html .= '<a class="' . ($active ? 'is-active' : '') . '" href="' . e(url($path)) . '"><span class="app-nav-icon">' . e(portal_nav_icon($path, $index)) . '</span><span class="app-nav-label">' . e(portal_nav_short_label($label)) . '</span></a>';
    }
    return $html . '</nav></div></div>';
}

function portal_nav_icon(string $path, int $index): string {
    if (str_contains($path, 'perfil')) return '◉';
    if (str_contains($path, 'autorizacoes')) return '✓';
    if (str_contains($path, 'portaria')) return '▣';
    if (str_contains($path, 'convites')) return '✚';
    if (str_contains($path, 'cracha')) return '▤';
    if (str_contains($path, 'avisar-falta') || str_contains($path, 'avisos-falta')) return '!';
    if (str_contains($path, 'frequencia')) return '✓';
    if (str_contains($path, 'eventos')) return '◷';
    if (str_contains($path, 'post-form')) return '+';
    if (str_contains($path, 'relatorios')) return '▤';
    if (str_contains($path, 'configuracoes')) return '⚙';
    if (str_contains($path, 'logout')) return '↪';
    if (str_contains($path, 'admin/index')) return '☰';
    if (str_contains($path, 'responsavel/index')) return '👥';
    if (str_contains($path, 'feed')) return '⌂';
    return ['⌂','▣','+','✓','☰'][$index % 5];
}

function portal_nav_short_label(string $label): string {
    return match ($label) {
        'Leitor QR Code' => 'QR',
        'Crachá digital' => 'Crachá',
        'Avisar falta' => 'Falta',
        'Avisos de falta' => 'Faltas',
        'Nova publicação' => 'Novo',
        'Responsáveis' => 'Resp.',
        'Meus filhos' => 'Filhos',
        'Meu perfil' => 'Perfil',
        'Relatórios' => 'Relat.',
        default => $label,
    };
}

function portal_quick_actions(): array {
    $role = current_role();
    if ($role === 'responsavel') {
        return [
            ['Autorizações', 'responsavel/autorizacoes.php', 'Retirada temporária'],
            ['Avisar falta', 'responsavel/avisar-falta.php', 'Registrar ausência'],
            ['Eventos', 'eventos.php', 'Agenda da escola'],
        ];
    }
    if ($role === 'professor') {
        return [
            ['Frequência', 'professor/frequencia.php', 'Turma por data'],
            ['Avisos de falta', 'professor/avisos-falta.php', 'Alunos da turma'],
            ['Eventos', 'eventos.php', 'Agenda'],
        ];
    }
    if ($role === 'portaria') {
        return [
            ['Leitor QR Code', 'portaria/index.php', 'Entrada e saída'],
            ['Autorizações', 'portaria/autorizacoes.php', 'Retirada temporária'],
            ['Convites', 'portaria/convites.php', 'Cadastro familiar'],
        ];
    }
    if (in_array($role, ['admin', 'secretaria'], true)) {
        return [
            ['Nova publicação', 'admin/post-form.php', 'Comunicar a escola'],
            ['Avisos de falta', 'admin/avisos-falta.php', 'Analisar responsáveis'],
            ['Frequência', 'professor/frequencia.php', 'Turmas e presença'],
        ];
    }
    return [];
}

function post_visible_sql(string $alias = 'p'): array {
    $role = current_role();
    if (in_array($role, ['admin', 'secretaria'], true)) return ["$alias.publico<>'publico'", []];
    if ($role === 'responsavel') {
        return ["($alias.publico='toda_escola' OR ($alias.publico='turma' AND $alias.turma_id IN (SELECT a.turma_id FROM scp_aluno_responsavel ar JOIN scp_alunos a ON a.id=ar.aluno_id WHERE ar.responsavel_id=? AND a.deleted_at IS NULL)) OR ($alias.publico='aluno' AND $alias.aluno_id IN (SELECT ar.aluno_id FROM scp_aluno_responsavel ar JOIN scp_alunos a ON a.id=ar.aluno_id WHERE ar.responsavel_id=? AND a.deleted_at IS NULL)))", [$_SESSION['responsavel_id'], $_SESSION['responsavel_id']]];
    }
    if ($role === 'professor') {
        return ["($alias.publico IN ('toda_escola','equipe') OR ($alias.publico='turma' AND $alias.turma_id IN (SELECT turma_id FROM scp_professor_turma pt JOIN scp_professores pr ON pr.id=pt.professor_id WHERE pr.usuario_id=? AND pr.ativo=1)))", [$_SESSION['user_id']]];
    }
    if ($role === 'portaria') return ["$alias.publico IN ('toda_escola','equipe')", []];
    return ['0=1', []];
}

function get_professor_id_for_user(): int {
    if (($_SESSION['role'] ?? '') !== 'professor') return 0;
    $q = db()->prepare('SELECT id FROM scp_professores WHERE usuario_id=? AND ativo=1 LIMIT 1');
    $q->execute([$_SESSION['user_id']]);
    return (int)$q->fetchColumn();
}

function require_portal_access(): void {
    if (!empty($_SESSION['responsavel_id']) || !empty($_SESSION['user_id'])) return;
    redirect('login.php');
}

function save_portal_upload(array $file, string $folder, string $type = 'document'): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível receber o arquivo.');
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('O arquivo deve ter no máximo 8 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if ($type === 'document') $extensions['application/pdf'] = 'pdf';
    if (!isset($extensions[$mime])) throw new RuntimeException($type === 'image' ? 'Use JPG, PNG ou WebP.' : 'Use JPG, PNG, WebP ou PDF.');
    $directory = public_uploads_dir($folder);
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Não foi possível preparar a pasta de uploads.');
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('Não foi possível salvar o arquivo.');
    return url('uploads/' . trim($folder, '/') . '/' . $filename);
}

function media_url(?string $url, mixed $version = ''): string {
    $url = trim((string)$url);
    if ($url === '') return '';
    $versionValue = $version !== '' ? (string)$version : substr(hash('sha256', $url), 0, 10);
    $separator = str_contains($url, '?') ? '&' : '?';
    return $url . $separator . 'v=' . rawurlencode($versionValue);
}

function format_br_datetime(?string $value): string {
    return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
}

function portal_excerpt(string $text, int $limit = 360): string {
    $plain = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    if (function_exists('mb_strimwidth')) return mb_strimwidth($plain, 0, $limit, '...', 'UTF-8');
    return strlen($plain) > $limit ? substr($plain, 0, $limit - 3) . '...' : $plain;
}
