<?php
$uri = service('uri');
$seg1 = $uri->getSegment(1) ?: 'dashboard';
$brand = ['name' => 'Hospital de Cauquenes', 'menu_logo' => null, 'favicon' => null, 'primary_color' => '#1D6FE8'];
$sidebarNombre = session()->get('nombre');
try {
    $activeBrand = db_connect('master_usuarios')->table('security_institutions')->where(['selected' => 1, 'active' => 1])->get()->getRowArray();
    if ($activeBrand) {
        $brand = array_merge($brand, $activeBrand);
    }

    if (session()->get('usuario_id')) {
        $sessionUser = db_connect('master_usuarios')
            ->table('user')
            ->select('NOMBRE, APELLIDO_PATERNO, APELLIDO_MATERNO')
            ->where('ID', session()->get('usuario_id'))
            ->get()
            ->getRowArray();

        if ($sessionUser) {
            $nombres = preg_split('/\s+/', trim((string) ($sessionUser['NOMBRE'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
            $sidebarNombre = trim(implode(' ', array_filter([
                $nombres[0] ?? '',
                trim((string) ($sessionUser['APELLIDO_PATERNO'] ?? '')),
                trim((string) ($sessionUser['APELLIDO_MATERNO'] ?? '')),
            ]))) ?: $sidebarNombre;
        }
    }
} catch (\Throwable $e) {
}

$navItems = [
    ['seg' => 'dashboard', 'icon' => 'bi-grid-1x2-fill', 'label' => 'Inicio'],
    ['seg' => 'master-usuarios', 'icon' => 'bi-person-vcard-fill', 'label' => 'Personal'],
    ['seg' => 'sistemas', 'icon' => 'bi-hdd-network-fill', 'label' => 'Sistemas HDC'],
    ['seg' => 'configuracion', 'icon' => 'bi-sliders', 'label' => 'Configuracion'],
];

$breadcrumbMap = [
    'dashboard' => 'Inicio',
    'master-usuarios' => 'Personal',
    'sistemas' => 'Sistemas HDC',
    'configuracion' => 'Configuracion',
];
$seccionLabel = $breadcrumbMap[$seg1] ?? ucfirst($seg1);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($titulo ?? 'Master HDC') ?></title>
<?php if (! empty($brand['favicon'])): ?><link rel="icon" href="<?= site_url('branding/favicon') ?>?v=<?= rawurlencode($brand['favicon']) ?>"><?php endif ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{
    --sidebar-bg:#0B1628;--sidebar-border:rgba(255,255,255,.08);--sidebar-text:#94A3B8;--bg:#F0F4F8;
    --surface:#fff;--surface-hover:#F8FAFC;--border:#E2E8F0;--text:#0F172A;--text-muted:#64748B;
    --accent:#1D6FE8;--accent-light:#EFF6FF;--teal:#0891B2;--teal-light:#ECFEFF;--green:#059669;
    --green-light:#ECFDF5;--amber:#D97706;--amber-light:#FFFBEB;--red:#DC2626;--red-light:#FEF2F2;
    --mov-primary:var(--accent);--mov-bg:var(--bg);--mov-border:var(--border);--mov-text:var(--text);
}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--text);font-family:'DM Sans',-apple-system,'Segoe UI',Roboto,sans-serif}
.app-shell{display:flex;height:100vh;overflow:hidden}
.sidebar{width:240px;flex-shrink:0;background:var(--sidebar-bg);display:flex;flex-direction:column;border-right:1px solid var(--sidebar-border)}
.sidebar-header{padding:20px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--sidebar-border)}
.brand-mark{width:36px;height:36px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff}
.brand-title{font-size:14px;font-weight:700;color:#fff}.brand-sub{font-size:11px;color:rgba(255,255,255,.45)}
.sidebar-nav{flex:1;padding:12px 10px;display:flex;flex-direction:column;gap:4px;overflow-y:auto}
.nav-item-mov{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;color:rgba(255,255,255,.58);text-decoration:none;font-size:14px}
.nav-item-mov:hover{background:rgba(255,255,255,.06);color:#fff}.nav-item-mov.active{background:rgba(255,255,255,.11);color:#fff;font-weight:600}
.nav-item-mov i{font-size:18px;width:18px;text-align:center}
.sidebar-user{padding:14px 12px;border-top:1px solid var(--sidebar-border);display:flex;align-items:flex-start;gap:10px}
.user-avatar{width:34px;height:34px;border-radius:99px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}
.user-info{flex:1;min-width:0}.user-name{font-size:13px;font-weight:600;color:#fff;white-space:normal;overflow:visible;text-overflow:clip;line-height:1.25;overflow-wrap:anywhere}.user-role{font-size:11px;color:rgba(255,255,255,.45);text-transform:capitalize}
.logout{color:rgba(255,255,255,.55);padding:6px;border-radius:8px}.logout:hover{color:#fff;background:rgba(255,255,255,.08)}
.main-col{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}
.topbar{height:68px;background:#fff;border-bottom:1px solid #E8EDF4;display:flex;align-items:center;padding:0 28px;gap:16px}
.breadcrumb-mov{font-size:14px;color:#94A3B8;display:flex;align-items:center;gap:6px}.breadcrumb-mov .current{color:var(--text);font-weight:600}
.topbar-spacer{flex:1}.topbar-date{font-size:13px;color:var(--text-muted);background:var(--surface-hover);padding:7px 14px;border-radius:10px;border:1px solid var(--border)}
.content{flex:1;overflow-y:auto;padding:28px}.content>.container-fluid{padding:0;max-width:none}
.page-title{font-size:24px;font-weight:750;margin:0}.page-subtitle{color:var(--text-muted);font-size:13px;margin-top:4px}
.card-soft{background:#fff;border:1px solid #dce7ec;border-radius:14px;box-shadow:0 2px 8px #16384b0b}
.section-title{font-size:15px;font-weight:700}
.surface{background:var(--surface);border:1px solid #F1F5F9;border-radius:18px;box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 16px rgba(0,0,0,.04);overflow:hidden}
.surface-header{padding:20px 24px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.surface-header h3{margin:0;font-size:16px;font-weight:700}.surface-body{padding:20px 24px}
.btn-primary-mov,.btn-mov{background:var(--accent);border:none;color:#fff;font-weight:600;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:7px}
.btn-primary-mov{padding:9px 16px;font-size:13px}.btn-mov{padding:.45rem 1rem;font-size:.85rem}
.btn-primary-mov:hover,.btn-mov:hover{background:#1558C0;color:#fff}
.card-mov{background:var(--surface);border:1px solid #F1F5F9;border-radius:18px;box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 16px rgba(0,0,0,.04);overflow:hidden}
.card-mov .card-header{background:linear-gradient(135deg,var(--accent) 0%,#3b82f6 100%);border-bottom:none;color:#fff;font-weight:700;padding:1rem 1.25rem;display:flex;align-items:center;gap:.5rem}
.card-mov .card-body{padding:1.25rem}
.activo-badge{font-size:.75rem;padding:.3em .65em;border-radius:20px;font-weight:600;display:inline-block}.activo-1{background:#d1fae5;color:#065f46}.activo-0{background:#fee2e2;color:#7f1d1d}
.table-professional{width:100%;border-collapse:collapse;font-size:13px}.table-professional thead th{padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#94A3B8;letter-spacing:.8px;text-transform:uppercase;border-bottom:1px solid #F1F5F9;background:#F8FAFC}
.table-professional tbody td{padding:14px 16px;border-bottom:1px solid #F8FAFC;vertical-align:middle}.table-professional tbody tr:hover{background:#FAFBFC}
.alert{border-radius:10px;border:none}.form-control,.form-select{border-radius:10px;border:1px solid var(--border);font-size:14px;padding:9px 12px}.form-label{font-size:13px;font-weight:600}
@media(max-width:900px){.sidebar{width:72px}.brand-title,.brand-sub,.nav-item-mov span,.user-info,.logout{display:none}.content{padding:16px}.topbar{padding:0 16px}}
</style>
</head>
<body>

<?php if (session()->get('usuario_id')): ?>
<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="brand-mark"<?= ! empty($brand['menu_logo']) ? ' style="background:#fff;overflow:hidden;"' : '' ?>>
        <?php if (! empty($brand['menu_logo'])): ?>
          <img src="<?= site_url('branding/menu') ?>?v=<?= rawurlencode($brand['menu_logo']) ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
        <?php else: ?>
          <i class="bi bi-shield-lock-fill"></i>
        <?php endif; ?>
      </div>
      <div>
        <div class="brand-title">Master HDC</div>
        <div class="brand-sub"><?= esc($brand['name']) ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $it): ?>
        <a href="<?= site_url($it['seg']) ?>" class="nav-item-mov <?= $seg1 === explode('/', $it['seg'])[0] ? 'active' : '' ?>" title="<?= esc($it['label']) ?>">
          <i class="bi <?= $it['icon'] ?>"></i>
          <span><?= esc($it['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
      <div class="user-avatar"><?= esc(strtoupper(mb_substr($sidebarNombre ?? '?', 0, 1))) ?></div>
      <div class="user-info">
        <div class="user-name"><?= esc($sidebarNombre) ?></div>
      </div>
      <a href="<?= site_url('logout') ?>" class="logout" title="Salir"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </aside>

  <div class="main-col">
    <header class="topbar">
      <div class="breadcrumb-mov">
        <span>Panel de Control</span>
        <i class="bi bi-chevron-right" style="font-size:12px;color:#CBD5E1;"></i>
        <span class="current"><?= esc($seccionLabel) ?></span>
      </div>
      <div class="topbar-spacer"></div>
      <div class="topbar-date d-none d-sm-block"><i class="bi bi-calendar3 me-1"></i><?= (new DateTime())->format('d / m / Y') ?></div>
    </header>

    <main class="content">
      <div class="container-fluid">
        <?php if (session()->getFlashdata('success')): ?>
          <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?= $this->renderSection('contenido') ?>
      </div>
    </main>
  </div>
</div>
<?php else: ?>
<div class="container py-4">
  <?= $this->renderSection('contenido') ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
