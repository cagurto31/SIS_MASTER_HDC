<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<?php
$_empNombre = 'Hospital de Cauquenes';
$_empIso = '';
try {
    $_brand = db_connect('master_usuarios')->table('security_institutions')->where(['selected' => 1, 'active' => 1])->get()->getRowArray();
    if ($_brand) {
        $_empNombre = $_brand['name'];
        if (! empty($_brand['login_logo'])) {
            $_empIso = site_url('branding/login');
        }
    }
} catch (\Throwable $_e) {
}
?>

<style>
body{background:#0B1628!important;min-height:100vh}.container{max-width:100%!important;padding:0!important;min-height:100vh;display:flex!important;align-items:center;justify-content:center;position:relative;overflow:hidden}
.login-bg{position:fixed;inset:0;background:linear-gradient(135deg,#0B1628 0%,#0f2347 40%,#0d1f3c 70%,#0B1628 100%);z-index:0}.login-bg::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 50% at 20% 40%,rgba(29,111,232,.18) 0%,transparent 70%),radial-gradient(ellipse 50% 40% at 80% 20%,rgba(14,165,233,.14) 0%,transparent 60%),radial-gradient(ellipse 40% 60% at 60% 80%,rgba(124,58,237,.10) 0%,transparent 70%);animation:bgShift 10s ease-in-out infinite alternate}@keyframes bgShift{0%{opacity:.6;transform:scale(1)}100%{opacity:1;transform:scale(1.05)}}
.login-orb{position:fixed;border-radius:50%;pointer-events:none;filter:blur(70px);animation:orbFloat 8s ease-in-out infinite}.orb-1{width:380px;height:380px;background:rgba(29,111,232,.15);top:-120px;right:80px}.orb-2{width:260px;height:260px;background:rgba(14,165,233,.12);bottom:80px;left:60px;animation-delay:2.5s}.orb-3{width:180px;height:180px;background:rgba(124,58,237,.10);top:45%;right:15%;animation-delay:5s}@keyframes orbFloat{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-24px) scale(1.04)}}
.login-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:48px 48px;pointer-events:none}
.login-card{position:relative;z-index:10;width:100%;max-width:420px;margin:24px;background:rgba(255,255,255,.06);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.12);border-radius:28px;padding:44px 40px 40px;box-shadow:0 32px 64px rgba(0,0,0,.4),0 0 0 1px rgba(255,255,255,.05);animation:cardAppear .55s cubic-bezier(.22,1,.36,1) both}@keyframes cardAppear{from{opacity:0;transform:translateY(28px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.login-brand{text-align:center;margin-bottom:34px}.login-logo-wrap{display:inline-flex;align-items:center;justify-content:center;width:68px;height:68px;border-radius:20px;background:linear-gradient(135deg,#1D6FE8 0%,#0EA5E9 100%);box-shadow:0 10px 28px rgba(29,111,232,.45),0 0 0 1px rgba(255,255,255,.1);font-size:30px;color:#fff;margin-bottom:18px}.login-title{font-size:26px;font-weight:800;color:#fff;margin:0 0 6px;letter-spacing:-.4px}.login-subtitle{font-size:13px;color:rgba(255,255,255,.5);margin:0;line-height:1.5}
.login-alert{background:rgba(220,38,38,.15);border:1px solid rgba(239,68,68,.3);border-radius:12px;color:#FCA5A5;padding:10px 14px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px}.login-alert.ok{background:rgba(16,185,129,.15);border-color:rgba(16,185,129,.3);color:#6ee7b7}
.login-form{display:flex;flex-direction:column;gap:14px}.login-field{position:relative}.login-field-ico{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.35);font-size:16px;pointer-events:none;z-index:2}.login-field:focus-within .login-field-ico{color:rgba(29,111,232,.9)}
.login-input{width:100%;background:rgba(255,255,255,.07);border:1.5px solid rgba(255,255,255,.1);border-radius:16px;padding:14px 16px 14px 48px;color:#fff;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,background .2s,box-shadow .2s}.login-input::placeholder{color:rgba(255,255,255,.3)}.login-input:focus{border-color:rgba(29,111,232,.7);background:rgba(255,255,255,.1);box-shadow:0 0 0 4px rgba(29,111,232,.18)}
.login-eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,.3);padding:4px;font-size:16px;line-height:1;z-index:2}.login-eye:hover{color:rgba(255,255,255,.7)}
.login-btn{margin-top:4px;width:100%;position:relative;overflow:hidden;background:linear-gradient(135deg,#1D6FE8 0%,#0EA5E9 100%);border:none;border-radius:16px;padding:14px 20px;color:#fff;font-size:15px;font-weight:700;font-family:'DM Sans',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 10px 28px rgba(29,111,232,.4);transition:transform .2s,box-shadow .2s,filter .2s}.login-btn:hover{transform:translateY(-2px);box-shadow:0 14px 36px rgba(29,111,232,.55);filter:brightness(1.08)}.btn-shimmer{position:absolute;top:0;left:-80%;width:60%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.22),transparent);transform:skewX(-15deg);animation:shimmerSlide 3.5s ease 1s infinite}@keyframes shimmerSlide{0%{left:-80%}100%{left:140%}}
.login-forgot{text-align:center;margin-top:18px}.login-forgot a{color:rgba(255,255,255,.6);font-size:13px;text-decoration:none}.login-forgot a:hover{color:rgba(255,255,255,.9)}
.login-footer-text{text-align:center;color:rgba(255,255,255,.24);font-size:12px;margin-top:26px;margin-bottom:0;letter-spacing:.3px}
</style>

<div class="login-bg"></div><div class="login-orb orb-1"></div><div class="login-orb orb-2"></div><div class="login-orb orb-3"></div><div class="login-grid"></div>

<div class="login-card">
  <div class="login-brand">
    <div class="login-logo-wrap"<?= $_empIso !== '' ? ' style="background:#fff;"' : '' ?>>
      <?php if ($_empIso !== ''): ?>
        <img src="<?= esc($_empIso) ?>" alt="<?= esc($_empNombre) ?>" style="width:100%;height:100%;object-fit:contain;border-radius:20px;padding:6px;box-sizing:border-box;">
      <?php else: ?>
        <i class="bi bi-shield-lock-fill"></i>
      <?php endif; ?>
    </div>
    <h1 class="login-title">Master HDC</h1>
    <p class="login-subtitle">Identidad y accesos · Hospital de Cauquenes</p>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="login-alert ok"><i class="bi bi-check-circle-fill"></i><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="login-alert"><i class="bi bi-exclamation-circle-fill"></i><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <form method="post" action="<?= site_url('login') ?>" class="login-form">
    <?= csrf_field() ?>
    <div class="login-field">
      <span class="login-field-ico"><i class="bi bi-person-fill"></i></span>
      <input type="text" name="usuario" class="login-input" placeholder="Usuario institucional" value="<?= esc(old('usuario')) ?>" maxlength="20" required autofocus autocomplete="username">
    </div>
    <div class="login-field">
      <span class="login-field-ico"><i class="bi bi-lock-fill"></i></span>
      <input type="password" name="password" id="loginPwd" class="login-input" placeholder="Clave unica HDC" minlength="8" maxlength="10" required autocomplete="current-password">
      <button type="button" class="login-eye" onclick="togglePwd()" title="Mostrar/ocultar"><i class="bi bi-eye" id="eyeIcon"></i></button>
    </div>
    <button type="submit" class="login-btn"><span>Iniciar sesion</span><i class="bi bi-arrow-right-circle-fill"></i><div class="btn-shimmer"></div></button>
  </form>

  <div class="login-forgot">
    <a href="<?= site_url('forgot-password') ?>">¿Olvidaste tu contraseña?</a>
  </div>

  <p class="login-footer-text">Hospital San Juan de Dios de Cauquenes</p>
</div>

<script>
function togglePwd(){const inp=document.getElementById('loginPwd');const ico=document.getElementById('eyeIcon');if(inp.type==='password'){inp.type='text';ico.className='bi bi-eye-slash'}else{inp.type='password';ico.className='bi bi-eye'}}
</script>

<?= $this->endSection() ?>
