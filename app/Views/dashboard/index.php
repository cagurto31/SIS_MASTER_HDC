<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<?php
$nombrePersona = static function (array $row): string {
    $nombres = preg_split('/\s+/', trim((string) ($row['NOMBRE'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    return trim(implode(' ', array_filter([
        $nombres[0] ?? '',
        trim((string) ($row['APELLIDO_PATERNO'] ?? '')),
        trim((string) ($row['APELLIDO_MATERNO'] ?? '')),
    ]))) ?: '-';
};
?>

<style>
.banner{border-radius:18px;overflow:hidden;position:relative;margin-bottom:24px;background:linear-gradient(135deg,#1D6FE8 0%,#0EA5E9 100%);box-shadow:0 8px 32px rgba(29,111,232,.25);}
.banner-body{padding:26px 32px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;}
.banner-title{font-size:24px;font-weight:700;color:#fff;margin:0 0 6px;}
.banner-sub{font-size:14px;color:rgba(255,255,255,.85);}
.banner-pill{background:rgba(255,255,255,.22);padding:3px 12px;border-radius:99px;font-size:12px;font-weight:500;text-transform:capitalize;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;}
.stat{background:#fff;border-radius:16px;padding:20px 22px;border:1px solid #F1F5F9;box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 16px rgba(0,0,0,.04);width:100%;text-align:left;cursor:pointer;transition:.15s ease;border-color:#F1F5F9;font:inherit;color:inherit;}
.stat:hover{transform:translateY(-1px);box-shadow:0 8px 26px rgba(15,23,42,.1);}
.stat-row{display:flex;justify-content:space-between;align-items:flex-start;}
.stat-label{font-size:12px;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
.stat-value{font-size:34px;font-weight:700;font-family:'Space Grotesk',sans-serif;color:#0F172A;line-height:1;}
.stat-sub{font-size:12px;color:#94A3B8;margin-top:5px;}
.stat-ico{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.detail-table{font-size:13px;}
.detail-table th{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748B;background:#F8FAFC;}
.detail-table td,.detail-table th{vertical-align:middle;}
.modal-summary{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;}
.modal-chip{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:8px 12px;font-size:13px;color:#334155;}
.modal-filter{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:12px;margin-bottom:14px;}
.modal-filter .form-control,.modal-filter .form-select{height:38px;}
</style>

<div class="banner">
  <div class="banner-body">
    <div>
      <div class="banner-title">Bienvenido, <?= esc($usuario['nombre']) ?></div>
      <div class="banner-sub">
        <span>Master HDC administra usuarios, MFA y accesos a sistemas del Hospital de Cauquenes.</span>
      </div>
    </div>
  </div>
</div>

<div class="stats-grid">
  <button type="button" class="stat" data-bs-toggle="modal" data-bs-target="#detalleUsuarios">
    <div class="stat-row">
      <div>
        <div class="stat-label">Usuarios</div>
        <div class="stat-value"><?= (int) $total_usuarios ?></div>
        <div class="stat-sub"><?= (int) $usuarios_activos ?> activos / <?= (int) $usuarios_inactivos ?> inactivos</div>
      </div>
      <div class="stat-ico" style="background:var(--accent-light);color:var(--accent);"><i class="bi bi-people-fill"></i></div>
    </div>
  </button>

  <button type="button" class="stat" data-bs-toggle="modal" data-bs-target="#detalleSistemas">
    <div class="stat-row">
      <div>
        <div class="stat-label">Sistemas HDC</div>
        <div class="stat-value"><?= (int) $sistemas_activos ?></div>
        <div class="stat-sub">Sistemas disponibles para asignar</div>
      </div>
      <div class="stat-ico" style="background:var(--teal-light);color:var(--teal);"><i class="bi bi-hdd-network-fill"></i></div>
    </div>
  </button>

  <button type="button" class="stat" data-bs-toggle="modal" data-bs-target="#detalleAccesos">
    <div class="stat-row">
      <div>
        <div class="stat-label">Accesos activos</div>
        <div class="stat-value"><?= (int) $accesos_activos ?></div>
        <div class="stat-sub">Asignaciones usuario/sistema</div>
      </div>
      <div class="stat-ico" style="background:var(--green-light);color:var(--green);"><i class="bi bi-key-fill"></i></div>
    </div>
  </button>

  <button type="button" class="stat" data-bs-toggle="modal" data-bs-target="#detalleMfa">
    <div class="stat-row">
      <div>
        <div class="stat-label">MFA pendiente</div>
        <div class="stat-value"><?= (int) $mfa_pendientes ?></div>
        <div class="stat-sub">Usuarios que deben enrolar segundo factor</div>
      </div>
      <div class="stat-ico" style="background:var(--amber-light);color:var(--amber);"><i class="bi bi-phone-lock"></i></div>
    </div>
  </button>
</div>

<div class="modal fade" id="detalleUsuarios" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de usuarios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="modal-summary">
          <span class="modal-chip"><strong><?= (int) $total_usuarios ?></strong> total</span>
          <span class="modal-chip"><strong><?= (int) $usuarios_activos ?></strong> activos</span>
          <span class="modal-chip"><strong><?= (int) $usuarios_inactivos ?></strong> inactivos</span>
        </div>
        <form class="modal-filter" id="usuariosFilterForm">
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label mb-1" for="usuariosSearch">Nombre o RUT</label>
              <input type="search" class="form-control form-control-sm" id="usuariosSearch" placeholder="Buscar por nombre o RUT">
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1" for="usuariosMfa">MFA</label>
              <select class="form-select form-select-sm" id="usuariosMfa">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="pendiente">Pendiente</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label mb-1" for="usuariosEstado">Estado</label>
              <select class="form-select form-select-sm" id="usuariosEstado">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-mov btn-sm"><i class="bi bi-search"></i> Buscar</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="usuariosFilterClear">Limpiar</button>
            </div>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm detail-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>RUT</th>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>MFA</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios_detalle as $row): ?>
              <?php
                $rutUsuario = trim((string) ($row['RUT'] ?? '') . '-' . (string) ($row['DV'] ?? ''));
                $nombreUsuario = $nombrePersona($row);
                $mfaEstado = (int) ($row['MFA_TOTP_ENABLED'] ?? 0) === 1 ? 'activo' : 'pendiente';
                $estadoUsuario = (int) ($row['ACTIVO'] ?? 0) === 1 ? 'activo' : 'inactivo';
              ?>
              <tr class="usuarios-detail-row" data-nombre="<?= esc(mb_strtolower($nombreUsuario)) ?>" data-rut="<?= esc(mb_strtolower($rutUsuario)) ?>" data-mfa="<?= esc($mfaEstado) ?>" data-estado="<?= esc($estadoUsuario) ?>">
                <td><?= (int) $row['ID'] ?></td>
                <td><?= esc($rutUsuario) ?></td>
                <td><?= esc($nombreUsuario) ?></td>
                <td><code><?= esc($row['USER'] ?? '-') ?></code></td>
                <td><?= esc($row['CORREO'] ?? '-') ?></td>
                <td><?= (int) ($row['MFA_TOTP_ENABLED'] ?? 0) === 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>' ?></td>
                <td><?= (int) ($row['ACTIVO'] ?? 0) === 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
              </tr>
            <?php endforeach; ?>
              <tr id="usuariosFilterEmpty" class="d-none"><td colspan="7" class="text-center text-muted py-4">Sin resultados para la busqueda</td></tr>
            <?php if (empty($usuarios_detalle)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">Sin usuarios registrados</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detalleSistemas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de sistemas HDC</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="modal-summary">
          <span class="modal-chip"><strong><?= (int) $sistemas_activos ?></strong> activos</span>
          <span class="modal-chip"><strong><?= count($sistemas_detalle) ?></strong> registrados</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm detail-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Sistema</th>
                <th>Slug</th>
                <th>URL</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($sistemas_detalle as $row): ?>
              <tr>
                <td><?= (int) $row['ID'] ?></td>
                <td>
                  <strong><?= esc($row['NOMBRE'] ?? '-') ?></strong>
                  <?php if (! empty($row['DESCRIPCION'])): ?><div class="text-muted small"><?= esc($row['DESCRIPCION']) ?></div><?php endif; ?>
                </td>
                <td><code><?= esc($row['SLUG'] ?? '-') ?></code></td>
                <td><?= ! empty($row['URL']) ? '<a href="' . esc($row['URL']) . '" target="_blank" rel="noopener">Abrir</a>' : '<span class="text-muted">Sin URL</span>' ?></td>
                <td><?= (int) ($row['ACTIVO'] ?? 0) === 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($sistemas_detalle)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Sin sistemas registrados</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detalleAccesos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de accesos activos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="modal-summary">
          <span class="modal-chip"><strong><?= (int) $accesos_activos ?></strong> asignaciones activas</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm detail-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>RUT</th>
                <th>Sistema</th>
                <th>Slug</th>
                <th>Asignado</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($accesos_detalle as $row): ?>
              <tr>
                <td><?= (int) $row['ID'] ?></td>
                <td>
                  <strong><?= esc($nombrePersona($row)) ?></strong>
                  <div class="text-muted small"><code><?= esc($row['USER'] ?? '-') ?></code></div>
                </td>
                <td><?= esc($row['RUT'] ?? '') ?>-<?= esc($row['DV'] ?? '') ?></td>
                <td><?= esc($row['SISTEMA_NOMBRE'] ?? '-') ?></td>
                <td><code><?= esc($row['SLUG'] ?? '-') ?></code></td>
                <td><?= ! empty($row['CREATED_AT']) ? esc(date('d/m/Y', strtotime($row['CREATED_AT']))) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($accesos_detalle)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Sin accesos activos</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detalleMfa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de MFA pendiente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="modal-summary">
          <span class="modal-chip"><strong><?= (int) $mfa_pendientes ?></strong> usuarios pendientes</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm detail-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>RUT</th>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Estado MFA</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($mfa_pendientes_detalle as $row): ?>
              <tr>
                <td><?= (int) $row['ID'] ?></td>
                <td><?= esc($row['RUT'] ?? '') ?>-<?= esc($row['DV'] ?? '') ?></td>
                <td><?= esc($nombrePersona($row)) ?></td>
                <td><code><?= esc($row['USER'] ?? '-') ?></code></td>
                <td><?= esc($row['CORREO'] ?? '-') ?></td>
                <td><span class="badge bg-warning text-dark">Pendiente</span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($mfa_pendientes_detalle)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Sin usuarios pendientes de MFA</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="surface">
  <div class="surface-header">
    <h3>Gobierno de identidad HDC</h3>
  </div>
  <div class="surface-body">
    <p class="mb-0">Use el modulo Personal para altas, bajas, cambio de clave unica HDC, MFA y asignacion de accesos. Use Sistemas HDC para mantener el catalogo central de aplicaciones del hospital.</p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('usuariosFilterForm');
  if (!form) return;

  const search = document.getElementById('usuariosSearch');
  const mfa = document.getElementById('usuariosMfa');
  const estado = document.getElementById('usuariosEstado');
  const clear = document.getElementById('usuariosFilterClear');
  const rows = Array.from(document.querySelectorAll('.usuarios-detail-row'));
  const empty = document.getElementById('usuariosFilterEmpty');

  const normalize = (value) => (value || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

  const applyFilter = () => {
    const term = normalize(search.value).replace(/\./g, '');
    const selectedMfa = mfa.value;
    const selectedEstado = estado.value;
    let visible = 0;

    rows.forEach((row) => {
      const nombre = normalize(row.dataset.nombre);
      const rut = normalize(row.dataset.rut).replace(/\./g, '');
      const matchesText = term === '' || nombre.includes(term) || rut.includes(term);
      const matchesMfa = selectedMfa === '' || row.dataset.mfa === selectedMfa;
      const matchesEstado = selectedEstado === '' || row.dataset.estado === selectedEstado;
      const show = matchesText && matchesMfa && matchesEstado;

      row.classList.toggle('d-none', !show);
      if (show) visible += 1;
    });

    if (empty) empty.classList.toggle('d-none', visible > 0);
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    applyFilter();
  });

  clear.addEventListener('click', () => {
    search.value = '';
    mfa.value = '';
    estado.value = '';
    applyFilter();
    search.focus();
  });
});
</script>

<?= $this->endSection() ?>
