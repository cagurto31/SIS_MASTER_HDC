<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>
<?= $this->include('auth/_card_style') ?>

<div class="auth-card">
  <div class="auth-brand">
    <div class="auth-logo"><i class="bi bi-key-fill"></i></div>
    <h1 class="auth-title">Recuperar contraseña</h1>
    <p class="auth-sub">Ingresa tu correo registrado y te enviaremos un enlace para crear una nueva contraseña.</p>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="auth-alert err"><i class="bi bi-exclamation-circle-fill"></i> <?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('forgot-password') ?>">
    <?= csrf_field() ?>
    <label class="auth-label">Correo electrónico</label>
    <input type="email" name="correo" class="auth-input" placeholder="nombre@dominio.cl" required autofocus value="<?= esc(old('correo')) ?>" autocomplete="email">
    <button type="submit" class="auth-btn"><i class="bi bi-send-fill"></i> Enviar enlace</button>
  </form>

  <a href="<?= site_url('login') ?>" class="auth-link"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
</div>

<?= $this->endSection() ?>
