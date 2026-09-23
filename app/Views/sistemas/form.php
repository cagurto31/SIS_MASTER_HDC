<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<?php $esEditar = $sistema !== null; ?>

<div class="surface">
  <div class="surface-header">
    <h3><i class="bi bi-hdd-network me-2"></i><?= $esEditar ? 'Editar sistema' : 'Nuevo sistema' ?></h3>
  </div>
  <div class="surface-body">
    <form method="post" action="<?= site_url($esEditar ? 'sistemas/guardar/' . $sistema['ID'] : 'sistemas/guardar') ?>">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="NOMBRE" class="form-control" maxlength="80" required value="<?= esc(old('NOMBRE', $sistema['NOMBRE'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Slug tecnico</label>
          <input type="text" name="SLUG" class="form-control" maxlength="60" required value="<?= esc(old('SLUG', $sistema['SLUG'] ?? '')) ?>" placeholder="ej: master_hdc">
        </div>
        <div class="col-12">
          <label class="form-label">URL</label>
          <input type="url" name="URL" class="form-control" maxlength="255" value="<?= esc(old('URL', $sistema['URL'] ?? '')) ?>" placeholder="https://...">
        </div>
        <div class="col-12">
          <label class="form-label">Descripcion</label>
          <textarea name="DESCRIPCION" class="form-control" rows="3" maxlength="255"><?= esc(old('DESCRIPCION', $sistema['DESCRIPCION'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="ACTIVO" value="1" id="activo" <?= old('ACTIVO', $sistema['ACTIVO'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Sistema activo</label>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <button type="submit" class="btn btn-mov"><i class="bi bi-floppy-fill"></i> Guardar</button>
      <a href="<?= site_url('sistemas') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
