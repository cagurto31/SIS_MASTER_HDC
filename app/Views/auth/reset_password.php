<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<?= $this->include('auth/_card_style') ?>

<div class="auth-card">
  <div class="auth-brand">
    <div class="auth-logo"><i class="bi bi-shield-lock-fill"></i></div>
    <h1 class="auth-title">Nueva contraseña</h1>
  </div>

  <?php if ($invalido): ?>
    <div class="auth-alert err"><i class="bi bi-exclamation-circle-fill"></i> El enlace es inválido o expiró. Solicita uno nuevo.</div>
    <a href="<?= site_url('forgot-password') ?>" class="auth-btn" style="text-decoration:none;"><i class="bi bi-arrow-repeat"></i> Solicitar nuevo enlace</a>
  <?php else: ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="auth-alert err"><i class="bi bi-exclamation-circle-fill"></i> <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= site_url('reset-password/' . $token) ?>">
      <?= csrf_field() ?>
      <label class="auth-label">Nueva contraseña</label>
      <input type="password" name="password" class="auth-input" minlength="8" maxlength="10" required autofocus placeholder="Entre 8 y 10 caracteres" autocomplete="new-password">
      <label class="auth-label" style="margin-top:14px;">Confirmar contraseña</label>
      <input type="password" name="password_confirm" class="auth-input" minlength="8" maxlength="10" required placeholder="Repite la contraseña" autocomplete="new-password">
      <button type="submit" class="auth-btn"><i class="bi bi-check-lg"></i> Guardar contraseña</button>
    </form>
  <?php endif; ?>

  <a href="<?= site_url('login') ?>" class="auth-link"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
</div>

<?= $this->endSection() ?>
