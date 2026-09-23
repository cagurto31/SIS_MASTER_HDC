<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<?= $this->include('auth/_card_style') ?>

<?php
$emailAvailable = $emailAvailable ?? false;
$totpAvailable = $totpAvailable ?? false;
$showSetup = $showSetup ?? false;
$emailCodeSent = $emailCodeSent ?? false;
$preferredMethod = (string) session()->get('mfa_preferred_method');
$selectedMethod = $preferredMethod;

if ($selectedMethod === '') {
    if ($emailAvailable && ! $totpAvailable) {
        $selectedMethod = 'email';
    } elseif ($totpAvailable && ! $emailAvailable) {
        $selectedMethod = 'totp';
    }
}

$showTotpPanel = $selectedMethod === 'totp';
$showEmailPanel = $selectedMethod === 'email';
$showCodeSection = $selectedMethod === 'totp' || ($selectedMethod === 'email' && $emailCodeSent);
$showEmailForm = $emailAvailable && $selectedMethod === 'email';
?>

<div class="auth-card mfa-card" data-email-code-sent="<?= $emailCodeSent ? '1' : '0' ?>">
  <div class="auth-brand">
    <div class="auth-logo"><i class="bi bi-shield-lock-fill"></i></div>
    <h1 class="auth-title">Verificacion MFA</h1>
    <div class="auth-sub">Elige un metodo de verificacion para completar el inicio de sesion.</div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="auth-alert err"><i class="bi bi-exclamation-circle-fill"></i> <?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif ?>
  <?php if (session()->getFlashdata('success')): ?>
    <div class="auth-alert"><i class="bi bi-check-circle-fill"></i> <?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif ?>

  <form method="post" action="<?= site_url('mfa') ?>" id="mfaVerifyForm">
    <?= csrf_field() ?>
    <label class="auth-label">Metodo</label>
    <select name="method" id="mfaMethod" class="auth-input" style="color:#172b3a;background:#fff" required>
      <?php if ($emailAvailable && $totpAvailable): ?><option value="" <?= $selectedMethod === '' ? 'selected' : '' ?>>Selecciona un metodo</option><?php endif ?>
      <?php if ($emailAvailable): ?><option value="email" <?= $selectedMethod === 'email' ? 'selected' : '' ?>>Codigo al correo</option><?php endif ?>
      <?php if ($totpAvailable): ?><option value="totp" <?= $selectedMethod === 'totp' ? 'selected' : '' ?>>Google Authenticator</option><?php endif ?>
    </select>

    <?php if ($totpAvailable): ?>
      <div class="mfa-method-panel" data-method-panel="totp" <?= $showTotpPanel ? '' : 'hidden' ?>>
        <?php if ($showSetup): ?>
          <div class="auth-sub mfa-help">Escanea este codigo QR con Google Authenticator y luego ingresa el codigo generado.</div>
          <div class="mfa-qr-box">
            <div id="mfaQr" class="d-inline-block bg-white p-2 rounded" data-uri="<?= esc($setupUri ?? '', 'attr') ?>"></div>
            <div id="mfaQrError" class="text-secondary small d-none mt-2">No fue posible generar el codigo QR.</div>
          </div>
        <?php else: ?>
          <div class="auth-sub mfa-help">Ingresa el codigo de tu aplicacion Authenticator.</div>
        <?php endif ?>
      </div>
    <?php endif ?>

    <?php if ($emailAvailable): ?>
      <div class="mfa-method-panel" data-method-panel="email" <?= $showEmailPanel ? '' : 'hidden' ?>>
        <div class="auth-sub mfa-help">Presiona el boton para recibir un codigo temporal en tu correo registrado.</div>
      </div>
    <?php endif ?>

    <div data-code-section <?= $showCodeSection ? '' : 'hidden' ?>>
      <label class="auth-label mt-3">Codigo</label>
      <input name="code" class="auth-input" inputmode="numeric" pattern="\d{6}" maxlength="6" required placeholder="000000" <?= $showCodeSection ? '' : 'disabled' ?>>
      <button type="submit" class="auth-btn"><i class="bi bi-check-lg"></i> Verificar</button>
    </div>
  </form>

  <?php if ($emailAvailable): ?>
    <form method="post" action="<?= site_url('mfa/email') ?>" class="mfa-email-form" data-loading-form <?= $showEmailForm ? '' : 'hidden' ?>>
      <?= csrf_field() ?>
      <button class="auth-btn auth-btn-secondary" type="submit" data-loading-text="Enviando codigo..."><i class="bi bi-envelope"></i> Enviar codigo al correo</button>
    </form>
  <?php endif ?>

  <a href="<?= site_url('logout') ?>" class="auth-link"><i class="bi bi-arrow-left"></i> Cancelar inicio de sesion</a>
</div>

<?php if ($totpAvailable && $showSetup): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(() => {
  const target = document.getElementById('mfaQr');
  if (!target || typeof QRCode === 'undefined') {
    document.getElementById('mfaQrError')?.classList.remove('d-none');
    return;
  }
  new QRCode(target, { text: target.dataset.uri || '', width: 156, height: 156, correctLevel: QRCode.CorrectLevel.M });
})();
</script>
<?php endif ?>

<script>
(() => {
  const card = document.querySelector('.mfa-card');
  const method = document.getElementById('mfaMethod');
  const codeSection = document.querySelector('[data-code-section]');
  const codeInput = codeSection?.querySelector('input[name="code"]');
  const emailForm = document.querySelector('.mfa-email-form');
  const emailSent = card?.dataset.emailCodeSent === '1';

  function refreshMfaView() {
    const value = method?.value || '';
    document.querySelectorAll('[data-method-panel]').forEach(panel => {
      panel.hidden = panel.dataset.methodPanel !== value;
    });
    if (emailForm) emailForm.hidden = value !== 'email';
    const canEnterCode = value === 'totp' || (value === 'email' && emailSent);
    if (codeSection) codeSection.hidden = !canEnterCode;
    if (codeInput) {
      codeInput.disabled = !canEnterCode;
      if (canEnterCode) codeInput.focus();
    }
  }

  method?.addEventListener('change', refreshMfaView);
  refreshMfaView();

  document.querySelectorAll('[data-loading-form]').forEach(form => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ' + (button.dataset.loadingText || 'Enviando...');
    });
  });
})();
</script>

<style>
.mfa-card{max-width:440px;padding:34px 38px}.mfa-card .auth-brand{margin-bottom:18px}.mfa-card .auth-logo{width:58px;height:58px;border-radius:16px;margin-bottom:14px}.mfa-help{margin:12px 0}.mfa-method-panel[hidden],.mfa-email-form[hidden],[data-code-section][hidden]{display:none!important}.mfa-email-form{margin:14px 0 4px}.auth-btn-secondary{background:linear-gradient(135deg,#2563eb,#16a6df)}.mfa-qr-box{background:#f4f8fa;border:1px solid #dce7ec;border-radius:12px;margin-bottom:16px;padding:12px;text-align:center}.mfa-qr-box canvas,.mfa-qr-box img{display:block;width:156px!important;height:156px!important}@media(max-height:760px){.mfa-card{padding:28px 34px}.mfa-card .auth-sub{font-size:12px}.mfa-qr-box canvas,.mfa-qr-box img{width:140px!important;height:140px!important}}
</style>

<?= $this->endSection() ?>
