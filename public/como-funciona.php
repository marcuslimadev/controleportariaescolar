<?php
require __DIR__ . '/../includes/bootstrap.php';
layout_header(current_locale()==='en' ? 'How it works' : 'Como Funciona');
$nextLang = current_locale() === 'en' ? 'pt' : 'en';
$whatsappUrl = 'https://wa.me/5592992287144?text=Ol%C3%A1%20gostaria%20de%20saber%20mais%20sobre%20o%20Escola%20Aberta';
$logoUrl = app_logo_url();
$appIconUrl = asset_url('assets/porta-aberta-app-v2-512.png');
?>
<section class="public-home public-how-page">
  <header class="public-topbar">
    <a class="public-brand" href="<?=e(url('login.php'))?>">
      <img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>">
      <span><strong><?=e(app_name())?></strong><small><?=e(app_tagline())?></small></span>
    </a>
    <nav class="public-top-actions" aria-label="Ações públicas">
      <a href="<?=e(url('login.php?login=1#login-card'))?>" class="btn btn-primary"><?=e(t('login'))?></a>
      <a href="<?=e(url('login.php'))?>" class="btn btn-outline-primary"><?=e(current_locale()==='en' ? 'Timeline' : 'Timeline')?></a>
      <a href="<?=e($whatsappUrl)?>" class="btn public-whatsapp-link" target="_blank" rel="noopener">WhatsApp</a>
      <a href="<?=e(lang_url($nextLang))?>" class="btn btn-outline-secondary lang-button">
        <span class="lang-flag" aria-hidden="true"><?=$nextLang === 'pt' ? '🇧🇷' : '🇺🇸'?></span>
        <span class="lang-code"><?=e(strtoupper($nextLang))?></span>
      </a>
    </nav>
  </header>

  <section class="public-memorial public-memorial-pro" aria-label="<?=e(current_locale()==='en' ? 'About the app' : 'Sobre o aplicativo')?>">
    <div class="public-memorial-hero public-memorial-hero-pro">
      <div>
        <span class="gate-eyebrow"><?=e(current_locale()==='en' ? 'OFFICIAL SCHOOL PORTAL' : 'PORTAL OFICIAL DA ESCOLA')?></span>
        <h1><?=e(current_locale()==='en' ? 'Communication, gatehouse and student safety in one beautiful app.' : 'Comunicação, portaria e segurança do aluno em um app bonito e simples.')?></h1>
        <p><?=e(current_locale()==='en'
          ? 'Porta Aberta Escolar replaces scattered messages and manual gatehouse notes with a clear, auditable and family-friendly digital flow.'
          : 'O Porta Aberta Escolar substitui mensagens espalhadas e controles manuais por um fluxo digital claro, rastreável e fácil para as famílias.')?></p>
        <div class="public-hero-actions">
          <a href="<?=e($whatsappUrl)?>" class="btn public-whatsapp-cta" target="_blank" rel="noopener"><?=e(current_locale()==='en' ? 'Talk on WhatsApp' : 'Falar no WhatsApp')?></a>
          <a href="<?=e(url('login.php'))?>" class="btn btn-outline-primary"><?=e(current_locale()==='en' ? 'View timeline' : 'Ver timeline')?></a>
        </div>
      </div>
      <div class="public-app-showcase" aria-hidden="true">
        <div class="public-phone-mock">
          <div class="public-phone-status"></div>
          <img class="public-phone-logo" src="<?=e($logoUrl)?>" alt="">
          <div class="public-phone-card">
            <span><?=e(current_locale()==='en' ? 'Official notice' : 'Comunicado oficial')?></span>
            <strong><?=e(current_locale()==='en' ? 'Pickup authorized' : 'Retirada autorizada')?></strong>
            <small><?=e(current_locale()==='en' ? 'Guardian confirmed · QR badge' : 'Responsável confirmado · Crachá QR')?></small>
          </div>
          <div class="public-phone-card soft">
            <span><?=e(current_locale()==='en' ? 'Class update' : 'Atualização da turma')?></span>
            <strong><?=e(current_locale()==='en' ? 'Visible to the right families' : 'Visível para as famílias certas')?></strong>
          </div>
        </div>
        <img class="public-floating-icon" src="<?=e($appIconUrl)?>" alt="">
      </div>
    </div>

    <div class="public-proof-strip">
      <span><?=e(current_locale()==='en' ? 'Public feed' : 'Feed público')?></span>
      <span><?=e(current_locale()==='en' ? 'Private notices by class' : 'Avisos privados por turma')?></span>
      <span><?=e(current_locale()==='en' ? 'QR gatehouse badge' : 'Crachá QR na portaria')?></span>
      <span><?=e(current_locale()==='en' ? 'Offline queue' : 'Fila offline')?></span>
    </div>

    <div class="public-image-story">
      <article>
        <img src="<?=e($logoUrl)?>" alt="<?=e(app_name())?>">
        <div>
          <span><?=e(current_locale()==='en' ? 'For families' : 'Para as famílias')?></span>
          <h2><?=e(current_locale()==='en' ? 'A school feed that feels familiar.' : 'Um feed escolar familiar e direto.')?></h2>
          <p><?=e(current_locale()==='en'
            ? 'Parents follow official posts, events, gallery and private notices without hunting through chat groups.'
            : 'Responsáveis acompanham comunicados, eventos, galeria e avisos privados sem depender de grupos bagunçados.')?></p>
        </div>
      </article>
      <article>
        <img src="<?=e($appIconUrl)?>" alt="<?=e(current_locale()==='en' ? 'App icon' : 'Ícone do aplicativo')?>">
        <div>
          <span><?=e(current_locale()==='en' ? 'For the school' : 'Para a escola')?></span>
          <h2><?=e(current_locale()==='en' ? 'Less improvisation. More control.' : 'Menos improviso. Mais controle.')?></h2>
          <p><?=e(current_locale()==='en'
            ? 'Management, teachers and gatehouse staff work with profiles, records and permissions made for their daily routine.'
            : 'Direção, professores e portaria trabalham com perfis, registros e permissões pensados para a rotina real.')?></p>
        </div>
      </article>
    </div>

    <div class="public-value-grid">
      <article>
        <strong>01</strong>
        <h2><?=e(current_locale()==='en' ? 'The problem it solves' : 'O problema que resolve')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'Reduces scattered messages, manual gatehouse notes and uncertainty about who can pick up each student.'
          : 'Reduz mensagens espalhadas, anotações manuais na portaria e dúvidas sobre quem pode retirar cada aluno.')?></p>
      </article>
      <article>
        <strong>02</strong>
        <h2><?=e(current_locale()==='en' ? 'Who uses it' : 'Quem usa')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'Admin, office staff, teachers, gatehouse team and guardians each see the right tools for their role.'
          : 'Direção/admin, secretaria, professores, portaria e responsáveis acessam ferramentas próprias para cada perfil.')?></p>
      </article>
      <article>
        <strong>03</strong>
        <h2><?=e(current_locale()==='en' ? 'Student safety' : 'Segurança do aluno')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'QR badges, pickup authorizations, access history and offline gatehouse queue help protect every movement.'
          : 'Crachás QR, autorizações de retirada, histórico de acesso e fila offline da portaria protegem cada movimentação.')?></p>
      </article>
      <article>
        <strong>04</strong>
        <h2><?=e(current_locale()==='en' ? 'What comes next' : 'O que vem a seguir')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'The platform is prepared to receive free interactive courses for students, expanding learning beyond communication.'
          : 'A plataforma está preparada para receber cursos interativos gratuitos para alunos, ampliando o aprendizado além da comunicação.')?></p>
      </article>
    </div>

    <div class="public-flow">
      <h2><?=e(current_locale()==='en' ? 'How the daily flow works' : 'Como funciona no dia a dia')?></h2>
      <div>
        <article><strong>1</strong><span><?=e(current_locale()==='en' ? 'The school publishes' : 'A escola publica')?></span></article>
        <article><strong>2</strong><span><?=e(current_locale()==='en' ? 'The family receives' : 'A família acompanha')?></span></article>
        <article><strong>3</strong><span><?=e(current_locale()==='en' ? 'Gatehouse validates' : 'A portaria valida')?></span></article>
        <article><strong>4</strong><span><?=e(current_locale()==='en' ? 'Everything is recorded' : 'Tudo fica registrado')?></span></article>
      </div>
    </div>

    <div class="public-memorial-footer">
      <span><?=e(current_locale()==='en' ? 'Value for the school' : 'Valor para a escola')?></span>
      <p><?=e(current_locale()==='en'
        ? 'More organization, safer operations, better family experience and reliable records for daily decisions.'
        : 'Mais organização, operação segura, melhor experiência para as famílias e registros confiáveis para decisões do dia a dia.')?></p>
      <a href="<?=e($whatsappUrl)?>" class="btn public-whatsapp-cta" target="_blank" rel="noopener"><?=e(current_locale()==='en' ? 'Talk on WhatsApp' : 'Falar no WhatsApp')?></a>
    </div>
  </section>
</section>
<?php layout_footer();
