<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<?php
$esEditar = $mu !== null;
$accesosPorSistema = [];
foreach ($accesos as $acceso) {
    $accesosPorSistema[(int) $acceso['SISTEMA_ID']] = $acceso;
}
?>

<div class="surface">
  <div class="surface-header">
    <h3><i class="bi bi-person-<?= $esEditar ? 'gear' : 'plus-fill' ?> me-2"></i><?= $esEditar ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
  </div>
  <div class="surface-body">
    <form method="post" action="<?= site_url($esEditar ? 'master-usuarios/guardar/' . $mu['ID'] : 'master-usuarios/guardar') ?>">
      <?= csrf_field() ?>

      <h5 class="mb-3">Identidad</h5>
      <div class="row g-3">
        <div class="col-sm-8 col-lg-5">
          <label class="form-label">RUT</label>
          <?php if ($esEditar): ?>
            <input type="text" class="form-control bg-light" value="<?= esc($mu['RUT']) ?>" disabled>
          <?php else: ?>
            <input type="text" name="RUT" class="form-control" maxlength="12" value="<?= esc(old('RUT', '')) ?>" required>
          <?php endif; ?>
        </div>
        <div class="col-sm-4 col-lg-2">
          <label class="form-label">DV</label>
          <?php if ($esEditar): ?>
            <input type="text" class="form-control bg-light" value="<?= esc($mu['DV']) ?>" disabled>
          <?php else: ?>
            <input type="text" name="DV" class="form-control" maxlength="1" value="<?= esc(old('DV', '')) ?>" required>
          <?php endif; ?>
        </div>
        <div class="col-md-5">
          <label class="form-label">Usuario</label>
          <input type="text" name="USER" class="form-control" maxlength="20" value="<?= esc(old('USER', $mu['USER'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nombre</label>
          <input type="text" name="NOMBRE" class="form-control" maxlength="60" value="<?= esc(old('NOMBRE', $mu['NOMBRE'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Apellido paterno</label>
          <input type="text" name="APELLIDO_PATERNO" class="form-control" maxlength="60" value="<?= esc(old('APELLIDO_PATERNO', $mu['APELLIDO_PATERNO'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Apellido materno</label>
          <input type="text" name="APELLIDO_MATERNO" class="form-control" maxlength="60" value="<?= esc(old('APELLIDO_MATERNO', $mu['APELLIDO_MATERNO'] ?? '')) ?>" required>
        </div>
      </div>

      <hr class="my-4">
      <h5 class="mb-3">Contacto y organizacion</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Correo</label>
          <input type="email" name="CORREO" class="form-control" maxlength="50" value="<?= esc(old('CORREO', $mu['CORREO'] ?? '')) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Telefono</label>
          <input type="number" name="TELEFONO" class="form-control" value="<?= esc(old('TELEFONO', $mu['TELEFONO'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" name="CARGO" class="form-control" maxlength="40" value="<?= esc(old('CARGO', $mu['CARGO'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Departamento</label>
          <input type="text" name="DEPARTAMENTO" class="form-control" maxlength="60" value="<?= esc(old('DEPARTAMENTO', $mu['DEPARTAMENTO'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Unidad</label>
          <input type="text" name="UNIDAD" class="form-control" maxlength="60" value="<?= esc(old('UNIDAD', $mu['UNIDAD'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Servicio / dependencia</label>
          <select name="SERVICIO_ID" class="form-select" id="servicioSelect">
            <option value="">Seleccionar servicio</option>
            <?php foreach (($servicios ?? []) as $servicio): ?>
              <option
                value="<?= (int) $servicio['cod_servicios'] ?>"
                data-codigo-centro="<?= esc($servicio['codigo_centro'] ?? '', 'attr') ?>"
                <?= (int) old('SERVICIO_ID', $mu['SERVICIO_ID'] ?? 0) === (int) $servicio['cod_servicios'] ? 'selected' : '' ?>
              >
                <?= esc($servicio['nom_servicio']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Centro de costo</label>
          <select name="CENTRO_COSTO_ID" class="form-select" id="centroCostoSelect">
            <option value="">Seleccionar centro de costo</option>
            <?php foreach (($centros ?? []) as $centro): ?>
              <option
                value="<?= (int) $centro['id'] ?>"
                data-codigo-centro="<?= esc($centro['codigo_centro'] ?? '', 'attr') ?>"
                <?= (int) old('CENTRO_COSTO_ID', $mu['CENTRO_COSTO_ID'] ?? 0) === (int) $centro['id'] ? 'selected' : '' ?>
              >
                <?= esc(($centro['codigo_centro'] ?? '') . ' - ' . $centro['nombre_centro']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Al seleccionar un servicio, el centro de costo se vincula automaticamente.</div>
        </div>
      </div>

      <hr class="my-4">
      <h5 class="mb-3">Seguridad</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Clave unica HDC <?= $esEditar ? '<span class="text-muted small">(dejar vacia para mantener)</span>' : '' ?></label>
          <input type="password" name="PASSWORD" class="form-control" minlength="8" maxlength="10" <?= $esEditar ? '' : 'required' ?> autocomplete="new-password">
          <div class="form-text">Entre 8 y 10 caracteres. Esta clave es la credencial central para los sistemas HDC.</div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="MFA_REQUIRED" value="1" id="mfa_required" <?= old('MFA_REQUIRED', $mu['MFA_REQUIRED'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="mfa_required">MFA obligatorio</label>
          </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ACTIVO" value="1" id="activo" <?= old('ACTIVO', $mu['ACTIVO'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Usuario activo</label>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <h5 class="mb-3">Acceso a sistemas</h5>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Sistema</th>
              <th style="width:90px;">Acceso</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sistemas as $sistema): ?>
            <?php
              $sid = (int) $sistema['ID'];
              $acceso = $accesosPorSistema[$sid] ?? null;
            ?>
            <tr>
              <td>
                <strong><?= esc($sistema['NOMBRE']) ?></strong>
                <div class="text-muted small"><code><?= esc($sistema['SLUG']) ?></code></div>
              </td>
              <td>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="ACCESOS[<?= $sid ?>][ACTIVO]" value="1" <?= $acceso && (int) $acceso['ACTIVO'] === 1 ? 'checked' : '' ?>>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($sistemas)): ?>
            <tr><td colspan="2" class="text-muted text-center py-4">Primero registre sistemas HDC.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <hr class="my-4">
      <button type="submit" class="btn btn-mov"><i class="bi bi-floppy-fill"></i> Guardar</button>
      <a href="<?= site_url('master-usuarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </form>
  </div>
</div>

<script>
(() => {
  const servicio = document.getElementById('servicioSelect');
  const centro = document.getElementById('centroCostoSelect');
  if (!servicio || !centro) return;

  function syncCentroCosto() {
    const codigoCentro = servicio.selectedOptions[0]?.dataset.codigoCentro || '';
    if (!codigoCentro) return;

    const option = Array.from(centro.options).find(item => item.dataset.codigoCentro === codigoCentro);
    if (option) {
      centro.value = option.value;
    }
  }

  servicio.addEventListener('change', syncCentroCosto);
  syncCentroCosto();
})();
</script>

<?= $this->endSection() ?>
