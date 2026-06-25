<?php
declare(strict_types=1);

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
        return [
            ['Timeline', 'feed.php'],
            ['Meus filhos', 'responsavel/index.php'],
            ['Crachá digital', 'cracha.php'],
            ['Avisar falta', 'responsavel/avisar-falta.php'],
            ['Eventos', 'eventos.php'],
            ['Histórico', 'responsavel/index.php'],
        ];
    }
    if ($role === 'professor') {
        return [
            ['Timeline', 'feed.php'],
            ['Frequência', 'professor/frequencia.php'],
            ['Avisos de falta', 'professor/avisos-falta.php'],
            ['Eventos', 'eventos.php'],
        ];
    }
    if ($role === 'portaria') {
        return [
            ['Leitor QR Code', 'portaria/index.php'],
            ['Convites', 'portaria/convites.php'],
            ['Timeline', 'feed.php'],
        ];
    }
    if (in_array($role, ['admin', 'secretaria'], true)) {
        $items = [
            ['Timeline', 'feed.php'],
            ['Nova publicação', 'admin/post-form.php'],
            ['Eventos', 'eventos.php'],
            ['Avisos de falta', 'admin/avisos-falta.php'],
            ['Frequência', 'professor/frequencia.php'],
            ['Alunos', 'admin/index.php'],
            ['Responsáveis', 'admin/index.php'],
            ['Turmas', 'admin/index.php'],
        ];
        if ($role === 'admin') $items[] = ['Professores', 'admin/index.php'];
        $items[] = ['Portaria', 'portaria/index.php'];
        return $items;
    }
    return [];
}

function portal_nav_html(): string {
    $items = portal_nav_items();
    if (!$items) return '';
    $html = '<div class="app-nav-scroll"><div class="container"><div class="app-nav">';
    foreach ($items as [$label, $path]) {
        $html .= '<a href="' . e(url($path)) . '">' . e($label) . '</a>';
    }
    return $html . '</div></div></div>';
}

function portal_quick_actions(): array {
    $role = current_role();
    if ($role === 'responsavel') {
        return [
            ['Avisar falta', 'responsavel/avisar-falta.php', 'Registrar ausência'],
            ['Eventos', 'eventos.php', 'Agenda da escola'],
            ['Crachá', 'cracha.php', 'QR dos filhos'],
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
            ['Convites', 'portaria/convites.php', 'Cadastro familiar'],
            ['Eventos', 'eventos.php', 'Agenda'],
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
    if (in_array($role, ['admin', 'secretaria'], true)) return ['1=1', []];
    if ($role === 'responsavel') {
        return ["($alias.publico='toda_escola' OR ($alias.publico='turma' AND $alias.turma_id IN (SELECT a.turma_id FROM scp_aluno_responsavel ar JOIN scp_alunos a ON a.id=ar.aluno_id WHERE ar.responsavel_id=?)) OR ($alias.publico='aluno' AND $alias.aluno_id IN (SELECT aluno_id FROM scp_aluno_responsavel WHERE responsavel_id=?)))", [$_SESSION['responsavel_id'], $_SESSION['responsavel_id']]];
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
    $directory = __DIR__ . '/../public/uploads/' . trim($folder, '/');
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Não foi possível preparar a pasta de uploads.');
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('Não foi possível salvar o arquivo.');
    return url('uploads/' . trim($folder, '/') . '/' . $filename);
}

function format_br_datetime(?string $value): string {
    return $value ? date('d/m/Y H:i', strtotime($value)) : '-';
}

function portal_excerpt(string $text, int $limit = 360): string {
    $plain = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    if (function_exists('mb_strimwidth')) return mb_strimwidth($plain, 0, $limit, '...', 'UTF-8');
    return strlen($plain) > $limit ? substr($plain, 0, $limit - 3) . '...' : $plain;
}
