<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
  <div>
    <h1 class="page-title">Configuracion institucional</h1>
    <div class="page-subtitle">Empresa activa e identidad institucional de Master HDC.</div>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#institutionNew">
    <i class="bi bi-building-add me-1"></i>Nueva empresa
  </button>
</div>

<div class="row g-3">
  <?php foreach ($institutions as $row): ?>
    <div class="col-xl-6">
      <div class="card-soft p-3 h-100 <?= $row['selected'] ? 'border-info' : '' ?>">
        <div class="d-flex gap-3 align-items-start">
          <div style="width:72px;height:72px;border-radius:12px;background:#f4f8fa;display:grid;place-items:center;overflow:hidden">
            <?php if (! empty($row['menu_logo'])): ?>
              <img src="<?= site_url('branding/menu/' . $row['id']) ?>" style="max-width:100%;max-height:100%;object-fit:contain" alt="Logo">
            <?php else: ?>
              <i class="bi bi-building fs-2 text-secondary"></i>
            <?php endif ?>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <h2 class="section-title mb-0"><?= esc($row['name']) ?></h2>
              <?php if ($row['selected']): ?><span class="badge text-bg-info">Activa</span><?php endif ?>
              <?php if (! $row['active']): ?><span class="badge text-bg-secondary">Inactiva</span><?php endif ?>
            </div>
            <div class="text-secondary small mt-1"><?= esc($row['legal_name'] ?: '') ?></div>
            <div class="small mt-2"><?= esc($row['rut'] ?: 'Sin RUT') ?> · <?= esc($row['commune'] ?: 'Sin comuna') ?></div>
          </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <small class="text-secondary">Codigo: <?= esc($row['code']) ?></small>
          <div class="d-flex gap-2">
            <?php if (! $row['selected'] && $row['active']): ?>
              <form method="post" action="<?= site_url('configuracion/seleccionar/' . $row['id']) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-info">Usar esta empresa</button>
              </form>
            <?php endif ?>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#institution<?= $row['id'] ?>">
              <i class="bi bi-pencil"></i> Editar
            </button>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<?php
$renderForm = function (?array $row = null) {
    $editing = $row !== null;
    $id = $row['id'] ?? null;
?>
<div class="modal fade" id="institution<?= $editing ? $id : 'New' ?>">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" method="post" enctype="multipart/form-data" action="<?= site_url('configuracion/guardar' . ($editing ? '/' . $id : '')) ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= $editing ? 'Editar' : 'Nueva' ?> empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Codigo</label>
            <input name="code" maxlength="30" class="form-control" value="<?= esc(old('code', $row['code'] ?? '')) ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Nombre</label>
            <input name="name" maxlength="180" class="form-control" value="<?= esc(old('name', $row['name'] ?? '')) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Razon social</label>
            <input name="legal_name" maxlength="200" class="form-control" value="<?= esc(old('legal_name', $row['legal_name'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">RUT empresa</label>
            <input name="rut" maxlength="20" class="form-control" value="<?= esc(old('rut', $row['rut'] ?? '')) ?>">
          </div>
          <div class="col-md-5">
            <label class="form-label">Direccion</label>
            <input name="address" maxlength="220" class="form-control" value="<?= esc(old('address', $row['address'] ?? '')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Comuna</label>
            <input name="commune" maxlength="100" class="form-control" value="<?= esc(old('commune', $row['commune'] ?? '')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Region</label>
            <input name="region" maxlength="100" class="form-control" value="<?= esc(old('region', $row['region'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Correo</label>
            <input type="email" name="email" maxlength="160" class="form-control" value="<?= esc(old('email', $row['email'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Telefono</label>
            <input name="phone" maxlength="30" class="form-control" value="<?= esc(old('phone', $row['phone'] ?? '')) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sitio web</label>
            <input name="website" maxlength="200" class="form-control" value="<?= esc(old('website', $row['website'] ?? '')) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Color principal</label>
            <input type="color" name="primary_color" class="form-control form-control-color w-100" value="<?= esc(old('primary_color', $row['primary_color'] ?? '#1D6FE8')) ?>">
          </div>
          <div class="col-12">
            <hr>
            <h6>Recursos de marca</h6>
          </div>
          <?php foreach ([
              'menu_logo' => 'Logo menu principal',
              'login_logo' => 'Logo del login',
              'favicon' => 'Icono de la URL',
              'document_logo' => 'Logo de documentos',
          ] as $field => $label): ?>
            <div class="col-md-3">
              <label class="form-label"><?= $label ?></label>
              <?php if ($editing && ! empty($row[$field])): ?>
                <?php $type = str_replace(['menu_logo', 'login_logo', 'favicon', 'document_logo'], ['menu', 'login', 'favicon', 'document'], $field); ?>
                <div class="mb-2 bg-light rounded p-2 text-center" style="height:72px">
                  <img src="<?= site_url('branding/' . $type . '/' . $id) ?>" style="max-height:100%;max-width:100%;object-fit:contain" alt="<?= esc($label) ?>">
                </div>
              <?php endif ?>
              <input type="file" name="<?= $field ?>" class="form-control" accept=".png,.jpg,.jpeg,.webp,.ico">
              <div class="form-text">PNG, JPG, WEBP o ICO · max. 3 MB</div>
            </div>
          <?php endforeach ?>
          <div class="col-12">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="active" value="1" <?= old('active', $row['active'] ?? 1) ? 'checked' : '' ?>>
              Empresa activa
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar configuracion</button>
      </div>
    </form>
  </div>
</div>
<?php
};
$renderForm();
foreach ($institutions as $institution) {
    $renderForm($institution);
}
?>

<?= $this->endSection() ?>
