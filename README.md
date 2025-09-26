# Inventario de Oficina (v2 - Campos solicitados)

Campos: **Nombre, Clase, Stock total, Condición, Notas, Ubicación, Mínimo, Máximo, Imagen**  
Incluye: **Agregar, Editar, Eliminar, Inventario, Mín/Máx, Galería** (PHP 8, MySQL, Bootstrap 5).

## Instalación
1. Crea la base y tabla: abre `schema.sql` y ejecútalo en MySQL.
2. Ajusta `config/db.php` (host/puerto/usuario/clave).
3. Asegura escritura en `uploads/`.
4. Abre `public/index.php` en el navegador.

## Importar CSV (opcional)
Encabezados esperados para carga masiva:
```
nombre,clase,cantidad,condicion,notas,ubicacion,min_stock,max_stock,imagen
```
> `imagen` puede quedar vacío; las imágenes se suben desde el formulario.

## Seguridad
- CSRF en formularios de alta/edición/eliminación.
- Validación básica de archivos: extensiones JPG/PNG/WEBP.