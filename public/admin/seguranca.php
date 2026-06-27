<?php
require __DIR__.'/../../includes/bootstrap.php';
require_permission('badge.issue');
$id=(int)($_GET['id']??0);
$badgeService = \App\Support\ServiceFactory::badges();
try {
    $a=$badgeService->securityBadge($id);
} catch (Throwable $error) {
    http_response_code(404);
    exit($error->getMessage());
}
$emergencyUrl=url('c/'.$a['qr_token']);
layout_header('QR de segurança de '.$a['nome']);
?>
<section class="badge-page">
  <div class="text-center mb-3">
    <span class="gate-eyebrow">QR DE SEGURANÇA</span>
    <h1 class="h2 fw-bold mt-1"><?=e($a['nome'])?></h1>
    <p class="text-muted">Para colar na mochila ou crachá físico da criança. Se alguém encontrar a criança desacompanhada e escanear este QR, cai direto numa página de alerta para a escola — sem mostrar nenhum dado pessoal.</p>
  </div>
  <div class="badge-preview-wrap text-center">
    <div id="security-qrcode" style="display:flex;justify-content:center;padding:12px"></div>
  </div>
  <div class="badge-actions">
    <button class="btn btn-outline-primary btn-lg w-100" type="button" onclick="window.print()">Imprimir</button>
    <a class="btn btn-link" href="<?=e(url('admin/index.php'))?>">Voltar ao painel</a>
  </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.querySelector('#security-qrcode'),{text:<?=json_encode($emergencyUrl)?>,width:260,height:260,correctLevel:QRCode.CorrectLevel.H});</script>
<?php layout_footer();
