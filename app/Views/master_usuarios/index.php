<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

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
    <div class="table-responsive">
      <table class="table-professional">
        <thead>
          <tr>
            <th>ID</th>
            <th>RUT</th>
            <th>Nombre completo</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Sistemas</th>
            <th>MFA</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <?php $activo = (int) ($u['ACTIVO'] ?? 1); ?>
          <tr class="<?= $activo ? '' : 'text-muted' ?>">
            <td class="text-muted"><?= (int) $u['ID'] ?></td>
            <td><strong><?= esc($u['RUT']) ?>-<?= esc($u['DV']) ?></strong></td>
            <td><?= esc(trim($u['NOMBRE'] . ' ' . $u['APELLIDO_PATERNO'] . ' ' . $u['APELLIDO_MATERNO'])) ?></td>
            <td><code><?= esc($u['USER'] ?? '-') ?></code></td>
            <td><?= esc($u['CORREO'] ?? '-') ?></td>
            <td><?= $u['SISTEMA'] ? esc($u['SISTEMA']) : '<span class="text-muted">Sin accesos</span>' ?></td>
            <td>
              <?php if ((int) ($u['MFA_TOTP_ENABLED'] ?? 0) === 1): ?>
                <span class="badge bg-success">Activo</span>
              <?php elseif ((int) ($u['MFA_REQUIRED'] ?? 1) === 1): ?>
                <span class="badge bg-warning text-dark">Pendiente</span>
              <?php else: ?>
                <span class="badge bg-secondary">No requerido</span>
              <?php endif; ?>
            </td>
            <td><span class="activo-badge activo-<?= $activo ?>"><?= $activo ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="text-end" style="white-space:nowrap;">
              <a href="<?= site_url('master-usuarios/editar/' . $u['ID']) ?>" class="btn btn-sm btn-mov" title="Editar"><i class="bi bi-pencil-fill"></i></a>
              <form method="post" action="<?= site_url('master-usuarios/reset-mfa/' . $u['ID']) ?>" class="d-inline" onsubmit="return confirm('Reiniciar MFA de este usuario?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-warning" title="Reiniciar MFA"><i class="bi bi-phone-flip"></i></button>
              </form>
              <a href="<?= site_url('master-usuarios/toggle/' . $u['ID']) ?>" class="btn btn-sm <?= $activo ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $activo ? 'Desactivar' : 'Activar' ?>" onclick="return confirm('Cambiar estado de este usuario?')">
                <i class="bi bi-<?= $activo ? 'toggle-on' : 'toggle-off' ?>"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">Sin registros</td></tr>
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
