<?= $this->extend('layouts/main') ?>
<?= $this->section('contenido') ?>

<div class="surface mb-3">
  <div class="surface-header">
    <div>
      <h3 class="mb-1"><i class="bi bi-hdd-network me-2"></i>Sistemas HDC</h3>
      <div class="text-muted small">Catalogo central de sistemas que usan la credencial unica HDC.</div>
    </div>
    <div class="ms-auto">
      <a href="<?= site_url('sistemas/crear') ?>" class="btn-primary-mov">
        <i class="bi bi-plus-lg"></i> Nuevo sistema
      </a>
    </div>
  </div>
  <div class="surface-body p-0">
    <div class="table-responsive">
      <table class="table-professional">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Slug</th>
            <th>URL</th>
            <th>MFA</th>
            <th>Metodos</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($sistemas as $s): ?>
          <tr>
            <td>
              <strong><?= esc($s['NOMBRE']) ?></strong>
              <?php if (! empty($s['DESCRIPCION'])): ?>
                <div class="text-muted small"><?= esc($s['DESCRIPCION']) ?></div>
              <?php endif; ?>
            </td>
            <td><code><?= esc($s['SLUG']) ?></code></td>
            <td><?= $s['URL'] ? '<a href="' . esc($s['URL']) . '" target="_blank" rel="noopener">Abrir</a>' : '<span class="text-muted">-</span>' ?></td>
            <td>
              <span class="badge text-bg-<?= ! empty($s['MFA_ENABLED']) ? 'success' : 'secondary' ?>">
                <?= ! empty($s['MFA_ENABLED']) ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td>
              <?php if (! empty($s['MFA_ENABLED'])): ?>
                <?php if (! empty($s['EMAIL_ENABLED'])): ?><span class="badge text-bg-light border"><i class="bi bi-envelope-fill me-1"></i>Correo</span><?php endif; ?>
                <?php if (! empty($s['AUTHENTICATOR_ENABLED'])): ?><span class="badge text-bg-light border"><i class="bi bi-phone-fill me-1"></i>Authenticator</span><?php endif; ?>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="activo-badge activo-<?= (int) $s['ACTIVO'] ?>"><?= $s['ACTIVO'] ? 'Activo' : 'Inactivo' ?></span>
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mfaSistema<?= (int) $s['ID'] ?>" title="Configurar MFA">
                <i class="bi bi-shield-lock-fill"></i>
              </button>
              <a href="<?= site_url('sistemas/editar/' . $s['ID']) ?>" class="btn btn-sm btn-mov"><i class="bi bi-pencil-fill"></i></a>
              <a href="<?= site_url('sistemas/toggle/' . $s['ID']) ?>" class="btn btn-sm <?= $s['ACTIVO'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                <i class="bi bi-<?= $s['ACTIVO'] ? 'toggle-on' : 'toggle-off' ?>"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($sistemas)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Sin sistemas registrados</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php foreach ($sistemas as $s): ?>
  <div class="modal fade" id="mfaSistema<?= (int) $s['ID'] ?>" tabindex="-1" aria-labelledby="mfaSistemaTitulo<?= (int) $s['ID'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="<?= site_url('sistemas/mfa/' . $s['ID']) ?>">
          <?= csrf_field() ?>
          <div class="modal-header">
            <div>
              <h5 class="modal-title" id="mfaSistemaTitulo<?= (int) $s['ID'] ?>">MFA de <?= esc($s['NOMBRE']) ?></h5>
              <div class="text-muted small mt-1"><code><?= esc($s['SLUG']) ?></code></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="form-check form-switch mb-4">
              <input class="form-check-input mfa-master-toggle" type="checkbox" role="switch" name="MFA_ENABLED" value="1" id="mfaEnabled<?= (int) $s['ID'] ?>" <?= ! empty($s['MFA_ENABLED']) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="mfaEnabled<?= (int) $s['ID'] ?>">Exigir autenticacion multifactor</label>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Metodos permitidos</label>
              <div class="d-grid gap-2">
                <label class="form-check border rounded p-3">
                  <input class="form-check-input mfa-method" type="checkbox" name="EMAIL_ENABLED" value="1" <?= ! isset($s['EMAIL_ENABLED']) || ! empty($s['EMAIL_ENABLED']) ? 'checked' : '' ?>>
                  <span class="ms-1"><i class="bi bi-envelope-fill me-2 text-primary"></i>Codigo enviado al correo</span>
                </label>
                <label class="form-check border rounded p-3">
                  <input class="form-check-input mfa-method" type="checkbox" name="AUTHENTICATOR_ENABLED" value="1" <?= ! isset($s['AUTHENTICATOR_ENABLED']) || ! empty($s['AUTHENTICATOR_ENABLED']) ? 'checked' : '' ?>>
                  <span class="ms-1"><i class="bi bi-phone-fill me-2 text-primary"></i>Aplicacion Authenticator</span>
                </label>
              </div>
            </div>

            <div>
              <label class="form-label" for="issuer<?= (int) $s['ID'] ?>">Nombre mostrado en Authenticator</label>
              <input class="form-control" id="issuer<?= (int) $s['ID'] ?>" name="ISSUER" maxlength="120" value="<?= esc($s['ISSUER'] ?: $s['NOMBRE']) ?>">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-shield-check me-1"></i>Guardar MFA</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
