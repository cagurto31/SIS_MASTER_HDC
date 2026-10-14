<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<style>
.personal-table-wrap{overflow-x:auto;max-width:100%}
.personal-table{table-layout:fixed;min-width:1320px}
.personal-table .col-id{width:4%}.personal-table .col-rut{width:9%}
.personal-table .col-nombre{width:16%}.personal-table .col-usuario{width:9%}
.personal-table .col-correo{width:17%}.personal-table .col-sistemas{width:10%}
.personal-table .col-perfil{width:12%}.personal-table .col-mfa{width:6%}
.personal-table .col-estado{width:6%}.personal-table .col-acciones{width:11%}
.personal-table thead th{padding:12px;letter-spacing:.5px;white-space:nowrap}
.personal-table tbody td{padding:12px;line-height:1.5;overflow-wrap:anywhere}
.personal-table .personal-rut,.personal-table .personal-status{white-space:nowrap}
.personal-table .personal-name{overflow-wrap:normal;word-break:normal}
.personal-table .personal-systems{display:flex;flex-wrap:wrap;gap:5px}
.personal-table .personal-system{display:inline-block;max-width:100%;padding:3px 7px;border-radius:6px;background:#eff6ff;color:#1e40af;font-size:11px;overflow-wrap:anywhere}
.personal-table .personal-actions{position:sticky;right:0;background:#fff;box-shadow:-1px 0 0 #e8edf4}
.personal-table thead .personal-actions{z-index:2;background:#f8fafc;text-align:center}
.personal-table tbody tr:hover .personal-actions{background:#fafbfc}
.personal-action-buttons{display:flex;justify-content:center;align-items:center;gap:5px;flex-wrap:nowrap}
.personal-action-buttons form{margin:0;display:flex}
.personal-action-buttons .btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;padding:0;flex-shrink:0;border-radius:8px}
.personal-table-wrap:focus-visible{outline:2px solid var(--accent);outline-offset:-2px}
</style>

<div class="surface mb-3">
  <div class="surface-header">
    <div>
      <h3 class="mb-1"><i class="bi bi-people-fill me-2"></i>Personal HDC</h3>
      <div class="text-muted small">Altas, bajas, clave unica HDC, MFA y accesos a sistemas.</div>
    </div>
    <div class="ms-auto">
      <a href="<?= site_url('master-usuarios/crear') ?>" class="btn-primary-mov">
        <i class="bi bi-person-plus-fill"></i> Nuevo usuario
      </a>
    </div>
  </div>
  <div class="surface-body">
    <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
      <input type="search" name="q" value="<?= esc($q) ?>" class="form-control form-control-sm" style="max-width:300px;" placeholder="Buscar por nombre, RUT, correo...">
      <select name="sistema" class="form-select form-select-sm" style="max-width:240px;">
        <option value="">Todos los sistemas</option>
        <?php foreach ($sistemas as $sistema): ?>
          <option value="<?= esc($sistema['SLUG']) ?>" <?= ($sistemaFiltro ?? '') === $sistema['SLUG'] ? 'selected' : '' ?>>
            <?= esc($sistema['NOMBRE']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <button class="btn btn-mov btn-sm" type="submit"><i class="bi bi-search"></i> Buscar</button>
      <?php if ($q || ($sistemaFiltro ?? '') !== ''): ?>
        <a href="<?= site_url('master-usuarios') ?>" class="btn btn-sm btn-outline-secondary">Limpiar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="surface">
  <div class="surface-header">
    <h3>Listado de usuarios</h3>
    <span class="badge bg-primary ms-auto"><?= count($usuarios) ?> en esta pagina</span>
  </div>
  <div class="surface-body p-0">
    <div class="table-responsive personal-table-wrap" role="region" aria-label="Listado de usuarios; desplazamiento horizontal disponible" tabindex="0">
      <table class="table-professional personal-table">
        <colgroup>
          <col class="col-id"><col class="col-rut"><col class="col-nombre">
          <col class="col-usuario"><col class="col-correo"><col class="col-sistemas">
          <col class="col-perfil"><col class="col-mfa"><col class="col-estado"><col class="col-acciones">
        </colgroup>
        <thead>
          <tr>
            <th>ID</th>
            <th>RUT</th>
            <th>Nombre completo</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Sistemas</th>
            <th>Perfil / rol<br><span class="fw-normal">Master HDC</span></th>
            <th>MFA</th>
            <th>Estado</th>
            <th class="personal-actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <?php $activo = (int) ($u['ACTIVO'] ?? 1); ?>
          <tr class="<?= $activo ? '' : 'text-muted' ?>">
            <td class="text-muted"><?= (int) $u['ID'] ?></td>
            <td class="personal-rut"><strong><?= esc($u['RUT']) ?>-<?= esc($u['DV']) ?></strong></td>
            <td class="personal-name"><?= esc(trim($u['NOMBRE'] . ' ' . $u['APELLIDO_PATERNO'] . ' ' . $u['APELLIDO_MATERNO'])) ?></td>
            <td><code><?= esc($u['USER'] ?? '-') ?></code></td>
            <td><?= esc($u['CORREO'] ?? '-') ?></td>
            <td>
              <div class="personal-systems">
                <?php foreach (array_filter(array_map('trim', explode(',', (string) ($u['SISTEMA'] ?? '')))) as $sistemaUsuario): ?>
                  <span class="personal-system"><?= esc($sistemaUsuario) ?></span>
                <?php endforeach; ?>
                <?php if (empty($u['SISTEMA'])): ?><span class="text-muted">Sin accesos</span><?php endif; ?>
              </div>
            </td>
            <td>
              <?php $perfil = $perfiles[(int) ($u['MASTER_PERFIL_ID'] ?? 0)] ?? null; ?>
              <?php if ($perfil): ?>
                <strong><?= esc($perfil['name']) ?></strong>
                <div class="text-muted small"><?= esc($perfil['role_name'] ?? 'Sin rol') ?><?= ! $perfil['assignable'] ? ' (inactivo)' : '' ?></div>
              <?php else: ?>
                <span class="text-muted"><?= empty($u['MASTER_PERFIL_ID']) ? 'Sin perfil' : 'Perfil no disponible' ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int) ($u['MFA_TOTP_ENABLED'] ?? 0) === 1): ?>
                <span class="badge bg-success">Activo</span>
              <?php elseif ((int) ($u['MFA_REQUIRED'] ?? 1) === 1): ?>
                <span class="badge bg-warning text-dark">Pendiente</span>
              <?php else: ?>
                <span class="badge bg-secondary">No requerido</span>
              <?php endif; ?>
            </td>
            <td class="personal-status"><span class="activo-badge activo-<?= $activo ?>"><?= $activo ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="personal-actions">
              <div class="personal-action-buttons">
              <a href="<?= site_url('master-usuarios/editar/' . $u['ID']) ?>" class="btn btn-sm btn-mov" title="Editar" aria-label="Editar usuario"><i class="bi bi-pencil-fill"></i></a>
              <form method="post" action="<?= site_url('master-usuarios/reset-mfa/' . $u['ID']) ?>" class="d-inline" onsubmit="return confirm('Reiniciar MFA de este usuario?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-warning" title="Reiniciar MFA" aria-label="Reiniciar MFA"><i class="bi bi-phone-flip"></i></button>
              </form>
              <a href="<?= site_url('master-usuarios/toggle/' . $u['ID']) ?>" class="btn btn-sm <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" aria-label="<?= $activo ? 'Desactivar usuario' : 'Activar usuario' ?>" onclick="return confirm('Cambiar estado de este usuario?')">
                <i class="bi bi-<?= $activo ? 'toggle-on' : 'toggle-off' ?>"></i>
              </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">Sin registros</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pager): ?>
    <div class="d-flex justify-content-center py-3">
      <?= $pager->links('default', 'bootstrap_full') ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>
