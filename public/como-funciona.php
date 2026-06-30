<?php
require __DIR__ . '/../includes/bootstrap.php';
layout_header(current_locale()==='en' ? 'How it works' : 'Como Funciona');
$nextLang = current_locale() === 'en' ? 'pt' : 'en';
?>
<section class="public-home">
  <header class="public-topbar">
    <a class="public-brand" href="<?=e(url('login.php'))?>">
      <img src="<?=e(app_logo_url())?>" alt="<?=e(app_name())?>">
      <span><strong><?=e(app_name())?></strong><small><?=e(app_tagline())?></small></span>
    </a>
    <nav class="public-top-actions" aria-label="Ações públicas">
      <a href="<?=e(url('login.php?login=1#login-card'))?>" class="btn btn-primary"><?=e(t('login'))?></a>
      <a href="<?=e(url('login.php'))?>" class="btn btn-outline-primary"><?=e(current_locale()==='en' ? 'Timeline' : 'Timeline')?></a>
      <a href="<?=e(lang_url($nextLang))?>" class="btn btn-outline-secondary lang-button">
        <span class="lang-flag" aria-hidden="true"><?=$nextLang === 'pt' ? '🇧🇷' : '🇺🇸'?></span>
        <span class="lang-code"><?=e(strtoupper($nextLang))?></span>
      </a>
    </nav>
  </header>

  <section class="public-memorial" aria-label="<?=e(current_locale()==='en' ? 'About the app' : 'Sobre o aplicativo')?>">
    <div class="public-memorial-hero">
      <span class="gate-eyebrow"><?=e(current_locale()==='en' ? 'SCHOOL, FAMILY AND GATEHOUSE' : 'ESCOLA, FAMÍLIA E PORTARIA')?></span>
      <h1><?=e(current_locale()==='en' ? 'A safer school routine, connected in one app.' : 'Uma rotina escolar mais segura, conectada em um só app.')?></h1>
      <p><?=e(current_locale()==='en'
        ? 'Porta Aberta Escolar organizes official communication, student pickup, attendance records and family access with clarity and traceability.'
        : 'O Porta Aberta Escolar organiza comunicação oficial, retirada de alunos, registros de acesso e acompanhamento da família com clareza e rastreabilidade.')?></p>
    </div>

    <div class="public-value-grid">
      <article>
        <strong>1</strong>
        <h2><?=e(current_locale()==='en' ? 'The problem it solves' : 'O problema que resolve')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'Reduces scattered messages, manual gatehouse notes and uncertainty about who can pick up each student.'
          : 'Reduz mensagens espalhadas, anotações manuais na portaria e dúvidas sobre quem pode retirar cada aluno.')?></p>
      </article>
      <article>
        <strong>2</strong>
        <h2><?=e(current_locale()==='en' ? 'Who uses it' : 'Quem usa')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'Admin, office staff, teachers, gatehouse team and guardians each see the right tools for their role.'
          : 'Direção/admin, secretaria, professores, portaria e responsáveis acessam ferramentas próprias para cada perfil.')?></p>
      </article>
      <article>
        <strong>3</strong>
        <h2><?=e(current_locale()==='en' ? 'Student safety' : 'Segurança do aluno')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'QR badges, pickup authorizations, access history and offline gatehouse queue help protect every movement.'
          : 'Crachás QR, autorizações de retirada, histórico de acesso e fila offline da portaria protegem cada movimentação.')?></p>
      </article>
      <article>
        <strong>4</strong>
        <h2><?=e(current_locale()==='en' ? 'What comes next' : 'O que vem a seguir')?></h2>
        <p><?=e(current_locale()==='en'
          ? 'The platform is prepared to receive free interactive courses for students, expanding learning beyond communication.'
          : 'A plataforma está preparada para receber cursos interativos gratuitos para alunos, ampliando o aprendizado além da comunicação.')?></p>
      </article>
    </div>

    <div class="public-memorial-footer">
      <span><?=e(current_locale()==='en' ? 'Value for the school' : 'Valor para a escola')?></span>
      <p><?=e(current_locale()==='en'
        ? 'More organization, safer operations, better family experience and reliable records for daily decisions.'
        : 'Mais organização, operação segura, melhor experiência para as famílias e registros confiáveis para decisões do dia a dia.')?></p>
    </div>
  </section>
</section>
<?php layout_footer();
