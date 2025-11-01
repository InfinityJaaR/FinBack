## Documentación API - Empresas (Frontend)

Ruta base: `/api/empresas`

Autenticación: endpoints protegidos por Sanctum y middleware de permisos. Incluye el header `Authorization: Bearer <token>` en todas las llamadas.

## Endpoints principales

### 1) Listar empresas (paginado)
- Método: GET
- URL: `/api/empresas` (opcional `?page=N`)
- Respuesta 200:

```json
{
  "success": true,
  "data": { /* paginación */ }
}
```

### 2) Obtener una empresa
- Método: GET
- URL: `/api/empresas/{id}`
- Respuesta 200:

```json
{
  "success": true,
  "data": { "id":1, "nombre":"X", "activo": true, /* ... */ }
}
```

### 3) Crear empresa
- Método: POST
- URL: `/api/empresas`
- Body (JSON):

```json
{
  "rubro_id": 2,
  "codigo": "12345",
  "nombre": "Mi Empresa",
  "descripcion": "..."
}
```

Respuesta 201: objeto creado.

### 4) Actualizar empresa
- Método: PUT
- URL: `/api/empresas/{id}`
- Body: campos editables (`rubro_id`, `codigo`, `nombre`, `descripcion`, ...)

Respuesta 200 con `data` actualizado.

### 5) Desactivar / Activar empresa
- Método: PATCH
- URL: `/api/empresas/{id}/disable`
- Parámetros:
  - `action=disable` (por defecto) → desactiva la empresa
  - `action=enable` → activa la empresa
- Ejemplo body: `{ "action": "enable" }` o usar query `?action=enable`
- Respuesta 200:

```json
{
  "success": true,
  "message": "Empresa desactivada exitosamente.",
  "data": { /* empresa fresca */ }
}
```

### 6) Eliminar empresa (flujo seguro)
- Método: DELETE
- URL: `/api/empresas/{id}`
- Query opcional: `?force=true` → borrado forzado (elimina dependencias y la empresa)

Comportamiento:
- Si la empresa tiene dependencias (catalogo_cuentas, estados, detalles_estado, ratios_valores, ventas_mensuales) y no pasas `force=true` → respuesta 409 (NO se borra). La respuesta incluye `details` con conteos para mostrar en UI.
- Si pasas `force=true` pero la empresa está activa (`activo=true`) → respuesta 403. Debes desactivar la empresa primero.
- Si pasas `force=true` y la empresa está desactivada → el backend borra dependencias en orden dentro de una transacción y luego la empresa. Si algo falla, se hace rollback.
- Si la empresa no tiene dependencias → DELETE elimina la empresa (200).

Respuestas ejemplo:

- 409 (Conflict - dependencias existentes):

```json
{
  "success": false,
  "message": "No se puede eliminar la empresa porque tiene datos asociados. Puedes desactivarla o ejecutar el borrado forzado.",
  "details": {
    "catalogo_cuentas": 6,
    "estados": 2,
    "detalles_por_estados": 6,
    "detalles_por_cuentas": 0,
    "ratios_valores": 0,
    "ventas_mensuales": 12
  }
}
```

- 403 (forzado sin desactivar):

```json
{
  "success": false,
  "message": "Borrado forzado rechazado: desactiva la empresa antes de eliminarla."
}
```

- 200 (éxito):

```json
{
  "success": true,
  "message": "Empresa eliminada exitosamente."
}
```

## Ejemplos de uso (Axios)

- Obtener empresa:

```js
const res = await axios.get(`/api/empresas/${id}`, { headers: { Authorization: `Bearer ${token}` }});
```

- Desactivar empresa:

```js
await axios.patch(`/api/empresas/${id}/disable`, {}, { headers: { Authorization: `Bearer ${token}` }});
```

- Activar empresa:

```js
await axios.patch(`/api/empresas/${id}/disable?action=enable`, {}, { headers: { Authorization: `Bearer ${token}` }});
```

- Intento de eliminar (no forzado):

```js
try {
  await axios.delete(`/api/empresas/${id}`, { headers: { Authorization: `Bearer ${token}` }});
  // si llega aquí -> eliminado
} catch (err) {
  if (err.response && err.response.status === 409) {
    const details = err.response.data.details;
    // Mostrar modal con details y opciones: desactivar o eliminar permanentemente
  }
}
```

- Borrado forzado (tras confirmación y desactivada):

```js
await axios.delete(`/api/empresas/${id}?force=true`, { headers: { Authorization: `Bearer ${token}` }});
```

## Ejemplos cURL

- Desactivar:

```bash
curl -X PATCH "https://miapi.test/api/empresas/1/disable" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

- Borrar forzado:

```bash
curl -X DELETE "https://miapi.test/api/empresas/1?force=true" \
  -H "Authorization: Bearer $TOKEN"
```

## Recomendaciones de UI/UX (flujo)

1. El frontend debe intentar DELETE sin `force` primero.
2. Si recibe 409: mostrar un modal con
   - Resumen de conteos devueltos en `details`.
   - Opciones: "Desactivar empresa" y "Eliminar permanentemente".
3. Si el usuario elige "Desactivar empresa": llamar PATCH `/disable`.
4. Si el usuario elige "Eliminar permanentemente": exigir confirmación textual (p. ej. escribir "ELIMINAR") y verificar que la empresa esté desactivada antes de llamar DELETE `?force=true`.

Notas de seguridad:
- Haz backup antes del borrado forzado.
- Solo roles con permiso `gestionar_empresas` pueden llamar los endpoints (las rutas están protegidas por middleware en `routes/api.php`).

## SQL de inspección (no destructivo)

Antes de borrar, se puede ejecutar (o el backend ya lo hace cuando el frontend intenta DELETE sin force):

```sql
SELECT 'catalogo_cuentas' AS tabla, COUNT(*) AS cnt FROM catalogo_cuentas WHERE empresa_id = 1
UNION ALL
SELECT 'estados', COUNT(*) FROM estados WHERE empresa_id = 1
UNION ALL
SELECT 'ratios_valores', COUNT(*) FROM ratios_valores WHERE empresa_id = 1
UNION ALL
SELECT 'ventas_mensuales', COUNT(*) FROM ventas_mensuales WHERE empresa_id = 1;
```

Contar detalles por estados:

```sql
SELECT COUNT(*) AS detalles_por_estados
FROM detalles_estado
WHERE estado_id IN (SELECT id FROM estados WHERE empresa_id = 1);
```

## Notas y alternativas

- Si prefieres mantener historial en lugar de borrar, plantea usar SoftDeletes en modelos (Laravel) para permitir restauración fácil.
- Si el backend necesita soportar más FKs en el futuro, actualizar el método `destroy` para incluir esas tablas en el orden de borrado.

---

Archivo generado automáticamente: `docs/frontend/empresa.md`. Úsalo como referencia para implementar el flujo en el frontend.
