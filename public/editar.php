<?php
// Archivo: public/editar_modal.php
// Modal de edición de artículo (mismo diseño que public/agregar.php),
// pensado para incluirse dentro de public/inventario.php.

// Si las variables no existen (por si se carga fuera de inventario.php), las consultamos
if (!isset($clases)) { $clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll(); }
if (!isset($conds))  { $conds  = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll(); }
if (!isset($ubis))   { $ubis   = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll(); }
?>

<!-- MODAL EDITAR ARTÍCULO (mismo diseño que el modal de Agregar) -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 750px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3" id="modalEditarLabel">
                    <div class="text-primary rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px; background-color: var(--primary-light);">
                        <i class="fas fa-pen-to-square fs-5"></i>
                    </div>
                    Editar Artículo
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-4 pt-3">
                <form id="formEditarArticulo" method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="">

                    <!-- FILA 1: Nombre y Clase -->
                    <div class="col-md-7">
                        <label class="custom-form-label"><i class="bi bi-box-seam text-primary"></i> Nombre del producto
                            <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control custom-input"
                            placeholder="Ej. Filtro de Aceite" required>
                    </div>

                    <div class="col-md-5">
                        <label class="custom-form-label"><i class="bi bi-tags text-primary"></i> Clase <span
                                class="text-danger">*</span></label>
                        <select name="clase_id" class="form-select custom-input" required>
                            <option value="">Seleccione una Clase...</option>
                            <?php foreach($clases as $c): ?>
                            <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- FILA 2: Cantidades -->
                    <div class="col-md-4">
                        <label class="custom-form-label"><i class="bi bi-layers text-primary"></i> Stock</label>
                        <input type="number" name="cantidad" class="form-control custom-input" min="0" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="custom-form-label"><i class="bi bi-arrow-down-circle text-primary"></i> Stock
                            Mínimo</label>
                        <input type="number" name="min_stock" class="form-control custom-input" min="0" placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="custom-form-label"><i class="bi bi-arrow-up-circle text-primary"></i> Stock
                            Máximo</label>
                        <input type="number" name="max_stock" class="form-control custom-input" min="0" placeholder="0">
                    </div>

                    <!-- FILA 3: Condición y Ubicación -->
                    <div class="col-md-6">
                        <label class="custom-form-label"><i class="bi bi-check-circle text-primary"></i>
                            Condición</label>
                        <select name="condicion_id" class="form-select custom-input">
                            <option value="">Seleccione el estado...</option>
                            <?php foreach($conds as $c): ?>
                            <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="custom-form-label"><i class="bi bi-geo-alt text-primary"></i> Ubicación</label>
                        <select name="ubicacion_id" class="form-select custom-input">
                            <option value="">Seleccione la ubicación...</option>
                            <?php foreach($ubis as $u): ?>
                            <option value="<?=$u['id']?>"><?=h($u['nombre'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- FILA 4: Drag & Drop Imagen -->
                    <div class="col-12">
                        <label class="custom-form-label"><i class="bi bi-image text-primary"></i> Fotografía del
                            Artículo</label>
                        <div class="drop-zone" id="editDropZone">
                            <div class="drop-zone__prompt d-flex flex-column align-items-center">
                                <i class="bi bi-cloud-arrow-up text-primary mb-1" style="font-size: 2rem;"></i>
                                <span class="fw-semibold text-dark fs-6">Haz clic o arrastra la imagen aquí</span>
                                <span class="small text-muted">Soporta JPG, PNG (Max. 5MB)</span>
                            </div>
                            <input type="file" name="imagen" class="drop-zone__input" accept="image/*">
                        </div>
                    </div>

                    <!-- FILA 5: Notas -->
                    <div class="col-12">
                        <label class="custom-form-label"><i class="bi bi-journal-text text-primary"></i> Notas /
                            Descripción</label>
                        <textarea name="notas" class="form-control custom-input" rows="2"
                            placeholder="Detalles adicionales..."></textarea>
                    </div>

                    <div class="alert alert-danger d-none mb-0" id="editError" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span id="editErrorText"></span>
                    </div>

                    <!-- Footer del Formulario -->
                    <div class="col-12 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
                            style="background-color: var(--primary); border: none; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>



<!-- ==========================================================================
         SCRIPTS
         ========================================================================== -->
<script>
// Modal de Editar Artículo (mismo diseño que el modal de Agregar)
(function() {
    const modalEl = document.getElementById('modalEditar');
    const form = document.getElementById('formEditarArticulo');
    const dropZone = document.getElementById('editDropZone');
    const errBox = document.getElementById('editError');
    const errText = document.getElementById('editErrorText');

    function setDropZoneImage(imagen) {
        const prompt = dropZone.querySelector('.drop-zone__prompt');
        let thumb = dropZone.querySelector('.drop-zone__thumb');

        if (imagen) {
            if (!thumb) {
                thumb = document.createElement('div');
                thumb.classList.add('drop-zone__thumb');
                dropZone.appendChild(thumb);
            }
            thumb.style.backgroundImage = `url('uploads/${imagen}')`;
            if (prompt) prompt.style.display = 'none';
        } else {
            if (thumb) thumb.remove();
            if (prompt) prompt.style.display = '';
        }
    }

    // Abrir modal y precargar datos del renglón
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-item');
        if (!btn) return;

        const row = btn.closest('tr');
        if (!row) return;

        const d = row.dataset;

        form.reset();
        errBox.classList.add('d-none');

        form.querySelector('[name="id"]').value = d.itemId || '';
        form.querySelector('[name="nombre"]').value = d.itemNombre || '';
        form.querySelector('[name="clase_id"]').value = d.itemClase || '';
        form.querySelector('[name="cantidad"]').value = d.itemCantidad || 0;
        form.querySelector('[name="min_stock"]').value = d.itemMin || 0;
        form.querySelector('[name="max_stock"]').value = d.itemMax || 0;
        form.querySelector('[name="condicion_id"]').value = d.itemCondicion || '';
        form.querySelector('[name="ubicacion_id"]').value = d.itemUbicacion || '';
        form.querySelector('[name="notas"]').value = d.itemNotas || '';

        setDropZoneImage(d.itemImagen || '');

        new bootstrap.Modal(modalEl).show();
    });

    // Guardar cambios
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        errBox.classList.add('d-none');

        const data = new FormData(form);

        try {
            const res = await fetch('public/ajax/editar_item.php', {
                method: 'POST',
                body: data
            });
            const json = await res.json();

            if (!json.ok) {
                errText.innerText = (json.errors && json.errors.join(' ')) || json.message || 'No se pudo guardar';
                errBox.classList.remove('d-none');
                return;
            }

            bootstrap.Modal.getInstance(modalEl).hide();
            window.location.reload();

        } catch (err) {
            errText.innerText = 'Error de conexión al guardar';
            errBox.classList.remove('d-none');
        }
    });

    // Limpiar el formulario al cerrar el modal
    modalEl.addEventListener('hidden.bs.modal', function() {
        form.reset();
        setDropZoneImage('');
        errBox.classList.add('d-none');
    });
})();
</script>