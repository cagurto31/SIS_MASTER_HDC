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

<div class="surface mt-4" id="perfiles-roles">
  <div class="surface-header">
    <div>
      <h3 class="mb-1"><i class="bi bi-person-badge-fill me-2"></i>Perfiles y roles</h3>
      <div class="text-muted small">Catalogo centralizado de roles, perfiles y permisos por modulo al estilo de Movilizacion.</div>
    </div>
    <div class="d-flex gap-2 ms-auto">
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleNew">
        <i class="bi bi-people-fill me-1"></i>Nuevo rol
      </button>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileNew">
        <i class="bi bi-shield-plus me-1"></i>Nuevo perfil
      </button>
    </div>
  </div>
  <div class="surface-body">
    <div class="row g-3 mb-4">
      <?php foreach (($roles ?? []) as $role): ?>
        <?php $roleActive = (int) ($role['active'] ?? 0) === 1; ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
          <div class="border rounded-3 p-3 h-100 <?= $roleActive ? '' : 'opacity-75' ?>">
            <div class="d-flex align-items-start gap-3">
              <div style="width:44px;height:44px;border-radius:10px;display:grid;place-items:center;background:<?= esc($role['color'] ?? '#1D6FE8') ?>20;color:<?= esc($role['color'] ?? '#1D6FE8') ?>">
                <i class="bi <?= esc($role['icon'] ?? 'bi-person-fill') ?> fs-4"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold"><?= esc($role['name']) ?></div>
                <code class="small"><?= esc($role['code']) ?></code>
              </div>
              <span class="activo-badge activo-<?= $roleActive ? 1 : 0 ?>"><?= $roleActive ? 'Activo' : 'Inactivo' ?></span>
            </div>
            <div class="text-muted small mt-3" style="min-height:38px"><?= esc($role['description'] ?: 'Sin descripcion') ?></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <span class="badge border" style="color:<?= esc($role['color'] ?? '#1D6FE8') ?>">Color <?= esc($role['color'] ?? '#1D6FE8') ?></span>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-mov" data-bs-toggle="modal" data-bs-target="#role<?= (int) $role['id'] ?>" title="Editar rol">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <a href="<?= site_url('configuracion/roles/toggle/' . $role['id']) ?>" class="btn btn-sm <?= $roleActive ? 'btn-outline-danger' : 'btn-outline-success' ?>" onclick="return confirm('Cambiar estado de este rol?')" title="<?= $roleActive ? 'Desactivar' : 'Activar' ?>">
                  <i class="bi bi-<?= $roleActive ? 'toggle-on' : 'toggle-off' ?>"></i>
                </a>
                <form method="post" action="<?= site_url('configuracion/roles/eliminar/' . $role['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar este rol? Los perfiles asociados quedaran sin rol.')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar rol">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>

    <div class="table-responsive">
      <table class="table-professional">
        <thead>
          <tr>
            <th>Perfil</th>
            <th>Rol sistema</th>
            <th>Permisos</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (($profiles ?? []) as $profile): ?>
          <?php
            $profilePermissions = (array) ($profile['permissions'] ?? []);
            $active = (int) ($profile['active'] ?? 0) === 1;
          ?>
          <tr class="<?= $active ? '' : 'text-muted' ?>">
            <td>
              <strong><?= esc($profile['name']) ?></strong>
              <div class="text-muted small"><?= esc($profile['description'] ?: 'Sin descripcion') ?></div>
            </td>
            <td>
              <?php if (! empty($profile['role_code'])): ?>
                <span class="badge border" style="color:<?= esc($profile['role_color'] ?? '#1D6FE8') ?>">
                  <i class="bi <?= esc($profile['role_icon'] ?? 'bi-person-fill') ?> me-1"></i><?= esc($profile['role_name'] ?? $profile['role_code']) ?>
                </span>
                <div><code class="small"><?= esc($profile['role_code']) ?></code></div>
              <?php else: ?>
                <span class="text-muted small">Sin rol asociado</span>
              <?php endif ?>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php foreach (array_slice($profilePermissions, 0, 8) as $permission): ?>
                  <span class="badge text-bg-light border"><?= esc($flatPermissions[$permission]['label'] ?? $permission) ?></span>
                <?php endforeach ?>
                <?php if (count($profilePermissions) > 8): ?>
                  <span class="badge text-bg-secondary">+<?= count($profilePermissions) - 8 ?></span>
                <?php endif ?>
              </div>
            </td>
            <td><span class="activo-badge activo-<?= $active ? 1 : 0 ?>"><?= $active ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="text-end" style="white-space:nowrap;">
              <button class="btn btn-sm btn-mov" data-bs-toggle="modal" data-bs-target="#profile<?= (int) $profile['id'] ?>" title="Editar">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <a href="<?= site_url('configuracion/perfiles/toggle/' . $profile['id']) ?>" class="btn btn-sm <?= $active ? 'btn-outline-danger' : 'btn-outline-success' ?>" onclick="return confirm('Cambiar estado de este perfil?')" title="<?= $active ? 'Desactivar' : 'Activar' ?>">
                <i class="bi bi-<?= $active ? 'toggle-on' : 'toggle-off' ?>"></i>
              </a>
              <form method="post" action="<?= site_url('configuracion/perfiles/eliminar/' . $profile['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar este perfil y todos sus permisos?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar perfil">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
        <?php if (empty($profiles)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Sin perfiles configurados</td></tr>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
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

$renderRoleForm = function (?array $role = null) {
    $editing = $role !== null;
    $id = $role['id'] ?? null;
?>
<div class="modal fade" id="role<?= $editing ? $id : 'New' ?>">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= site_url('configuracion/roles/guardar' . ($editing ? '/' . $id : '')) ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= $editing ? 'Editar rol' : 'Nuevo rol' ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Clave</label>
            <input name="code" maxlength="50" class="form-control" value="<?= esc(old('code', $role['code'] ?? '')) ?>" <?= $editing ? 'readonly' : 'required' ?>>
            <div class="form-text">Ej: administrador, operador, solicitante</div>
          </div>
          <div class="col-md-7">
            <label class="form-label">Nombre</label>
            <input name="name" maxlength="100" class="form-control" value="<?= esc(old('name', $role['name'] ?? '')) ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Descripcion</label>
            <textarea name="description" class="form-control" rows="3" maxlength="255"><?= esc(old('description', $role['description'] ?? '')) ?></textarea>
          </div>
          <div class="col-md-8">
            <label class="form-label">Icono Bootstrap</label>
            <input name="icon" maxlength="60" class="form-control" value="<?= esc(old('icon', $role['icon'] ?? 'bi-person-fill')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Color</label>
            <input type="color" name="color" class="form-control form-control-color w-100" value="<?= esc(old('color', $role['color'] ?? '#1D6FE8')) ?>">
          </div>
          <div class="col-12">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="active" value="1" <?= old('active', $role['active'] ?? 1) ? 'checked' : '' ?>>
              Rol activo
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar rol</button>
      </div>
    </form>
  </div>
</div>
<?php
};
$renderRoleForm();
foreach (($roles ?? []) as $role) {
    $renderRoleForm($role);
}

$renderProfileForm = function (?array $profile = null) use ($roles, $permissionsCatalog) {
    $editing = $profile !== null;
    $id = $profile['id'] ?? null;
    $selectedPermissions = old('permissions', $profile ? (array) ($profile['permissions'] ?? []) : ['dashboard']);
    if (! is_array($selectedPermissions)) {
        $selectedPermissions = [$selectedPermissions];
    }
?>
<div class="modal fade" id="profile<?= $editing ? $id : 'New' ?>">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= site_url('configuracion/perfiles/guardar' . ($editing ? '/' . $id : '')) ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><?= $editing ? 'Editar perfil' : 'Nuevo perfil' ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Nombre</label>
            <input name="name" maxlength="100" class="form-control" value="<?= esc(old('name', $profile['name'] ?? '')) ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Rol sistema</label>
            <select name="role_code" class="form-select">
              <option value="">Sin rol asociado</option>
              <?php foreach (($roles ?? []) as $role): ?>
                <?php $selectedRole = old('role_code', $profile['role_code'] ?? '') === $role['code']; ?>
                <option value="<?= esc($role['code']) ?>" <?= $selectedRole ? 'selected' : '' ?>><?= esc($role['name']) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Descripcion</label>
            <textarea name="description" class="form-control" rows="3" maxlength="255"><?= esc(old('description', $profile['description'] ?? '')) ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Permisos</label>
            <div class="row g-3">
              <?php foreach ($permissionsCatalog as $group => $items): ?>
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-2"><?= esc($group) ?></div>
                    <div class="d-grid gap-2">
                      <?php foreach ($items as $permission => $meta): ?>
                        <label class="form-check">
                          <input type="checkbox" class="form-check-input" name="permissions[]" value="<?= esc($permission) ?>" <?= in_array($permission, $selectedPermissions, true) ? 'checked' : '' ?>>
                          <i class="bi <?= esc($meta['icon'] ?? 'bi-dot') ?> me-1"></i><?= esc($meta['label'] ?? $permission) ?>
                        </label>
                      <?php endforeach ?>
                    </div>
                  </div>
                </div>
              <?php endforeach ?>
            </div>
          </div>
          <div class="col-12">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="active" value="1" <?= old('active', $profile['active'] ?? 1) ? 'checked' : '' ?>>
              Perfil activo
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar perfil</button>
      </div>
    </form>
  </div>
</div>
<?php
};
$renderProfileForm();
foreach (($profiles ?? []) as $profile) {
    $renderProfileForm($profile);
}
?>

<?= $this->endSection() ?>
