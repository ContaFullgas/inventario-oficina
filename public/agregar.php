<?php
// Archivo: public/agregar.php

// Si las variables no existen (por si se carga directo), las consultamos
if (!isset($clases)) { $clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll(); }
if (!isset($conds)) { $conds = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll(); }
if (!isset($ubis)) { $ubis = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll(); }
?>

<style>
/* ===== ESTILOS DEL MODAL Y FORMULARIO ===== */
.custom-form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.custom-input {
    background-color: var(--bg-body);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 0.5rem 0.8rem;
    font-size: 0.95rem;
    color: var(--text-dark);
    transition: all 0.2s ease;
}

.custom-input:focus {
    background-color: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
    outline: none;
}

/* ===== ZONA DRAG & DROP PARA LA IMAGEN ===== */
.drop-zone {
    max-width: 100%;
    height: 140px;
    /* Reducido para compactar */
    padding: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    cursor: pointer;
    color: var(--text-muted);
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    background-color: var(--bg-body);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.drop-zone:hover {
    background-color: #f1f5f9;
    border-color: #cbd5e1;
}

.drop-zone--over {
    border-style: solid;
    background-color: var(--primary-light) !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
}

.drop-zone__input {
    display: none;
}

.drop-zone__thumb {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    background-color: #cccccc;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
}

/* ==========================================================================
   MODO OSCURO (agregar / modal)
   ========================================================================== */

body.dark-mode .custom-input:focus {
    background-color: #cac9c9c5
}

body.dark-mode .drop-zone:hover {
    background-color: var(--bg-card);
    border-color: var(--border-color);
}

/* Bootstrap fuerza estos colores con !important, hay que igualarlo
   (se aplica a #modalAgregar y #modalEditar porque editar.php no tiene
   su propio <style> y reutiliza este bloque) */
body.dark-mode #modalAgregar .text-dark,
body.dark-mode #modalEditar .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalAgregar .text-muted,
body.dark-mode #modalEditar .text-muted {
    color: var(--text-muted) !important;
}

/* Opciones de los <select> */
body.dark-mode #modalAgregar select option,
body.dark-mode #modalEditar select option {
    background: var(--bg-card);
    color: var(--text-main);
}

/* Botón "Cancelar" (btn-light, fondo blanco fijo de Bootstrap) */
body.dark-mode #modalAgregar .btn-light,
body.dark-mode #modalEditar .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}

</style>

<!-- MODAL AGREGAR ARTÍCULO -->
<div class="modal fade" id="modalAgregar" tabindex="-1" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 750px;">
        <!-- Ligeramente más estrecho -->
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

            <!-- Modal Header -->
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3" id="modalAgregarLabel">
                    <div class="text-primary rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px; background-color: var(--primary-light);">
                        <i class="fas fa-box-open fs-5"></i>
                    </div>
                    Registrar Nuevo Artículo
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 pt-3">
                <form id="formAgregarArticulo" method="post" enctype="multipart/form-data"
                    action="public/agregar_guardar.php" class="row g-3">
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>

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
                        <label class="custom-form-label"><i class="bi bi-layers text-primary"></i> Stock Inicial</label>
                        <input type="number" name="cantidad" class="form-control custom-input" value="0" min="0"
                            placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="custom-form-label"><i class="bi bi-arrow-down-circle text-primary"></i> Stock
                            Mínimo</label>
                        <input type="number" name="min_stock" class="form-control custom-input" value="0" min="0"
                            placeholder="0">
                    </div>

                    <div class="col-md-4">
                        <label class="custom-form-label"><i class="bi bi-arrow-up-circle text-primary"></i> Stock
                            Máximo</label>
                        <input type="number" name="max_stock" class="form-control custom-input" value="0" min="0"
                            placeholder="0">
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
                        <div class="drop-zone" id="dropZoneContainer">
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

                    <!-- Footer del Formulario -->
                    <div class="col-12 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <!-- Botón de guardar con tamaño adecuado -->
                        <button type="submit"
                            class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
                            style="background-color: var(--primary); border: none; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// LÓGICA PARA EL DRAG & DROP DE LA IMAGEN
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
        const dropZoneElement = inputElement.closest(".drop-zone");

        // Al hacer clic en el contenedor, se activa el input de archivo oculto
        dropZoneElement.addEventListener("click", (e) => {
            // Evitar que un clic en la miniatura active multiples veces
            if (e.target !== inputElement) {
                inputElement.click();
            }
        });

        // Cuando se selecciona un archivo (por clic)
        inputElement.addEventListener("change", (e) => {
            if (inputElement.files.length) {
                updateThumbnail(dropZoneElement, inputElement.files[0]);
            }
        });

        // Efecto visual al arrastrar por encima
        dropZoneElement.addEventListener("dragover", (e) => {
            e.preventDefault();
            dropZoneElement.classList.add("drop-zone--over");
        });

        // Quitar efecto visual si el cursor sale
        ["dragleave", "dragend"].forEach((type) => {
            dropZoneElement.addEventListener(type, (e) => {
                dropZoneElement.classList.remove("drop-zone--over");
            });
        });

        // Al soltar el archivo
        dropZoneElement.addEventListener("drop", (e) => {
            e.preventDefault();

            if (e.dataTransfer.files.length) {
                inputElement.files = e.dataTransfer.files;
                updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
            }
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    // Función para actualizar la miniatura
    function updateThumbnail(dropZoneElement, file) {
        let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

        // Quitar el texto de aviso "Arrastra aquí" la primera vez
        if (dropZoneElement.querySelector(".drop-zone__prompt")) {
            dropZoneElement.querySelector(".drop-zone__prompt").style.display = 'none';
        }

        // Crear el div de la miniatura si no existe
        if (!thumbnailElement) {
            thumbnailElement = document.createElement("div");
            thumbnailElement.classList.add("drop-zone__thumb");
            dropZoneElement.appendChild(thumbnailElement);
        }

        // Mostrar miniatura si es una imagen
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();

            reader.readAsDataURL(file);
            reader.onload = () => {
                thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
            };
        } else {
            thumbnailElement.style.backgroundImage = null;
        }
    }
});
</script>