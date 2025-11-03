# API de Catálogo de Cuentas

## Descripción
Esta API permite gestionar el catálogo de cuentas contables de las empresas. Cada empresa tiene un único catálogo de cuentas que puede ser cargado y reemplazado completamente.

## Autenticación
Todas las rutas requieren autenticación mediante token Sanctum y el permiso `gestionar_catalogo_cuentas`.

**Headers requeridos:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Importante:** Los usuarios con rol **Analista Financiero** solo pueden ver, crear, editar y eliminar el catálogo de la empresa a la que están asociados (campo `empresa_id` en la tabla `users`). Los **Administradores** tienen acceso completo a todas las empresas.

---

## Endpoints

### 1. Obtener lista de empresas con información de catálogo

Obtiene todas las empresas con información sobre si tienen o no un catálogo cargado.

**Restricciones por rol:**
- **Administrador**: Ve todas las empresas del sistema
- **Analista Financiero**: Solo ve la empresa a la que está asociado

**Endpoint:** `GET /api/catalogo-cuentas/empresas`

**Respuesta exitosa (200) - Administrador:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Empresa ABC S.A.",
      "ruc": "20123456789",
      "tiene_catalogo": true,
      "total_cuentas": 50
    },
    {
      "id": 2,
      "nombre": "Comercial XYZ Ltda.",
      "ruc": "20987654321",
      "tiene_catalogo": false,
      "total_cuentas": 0
    }
  ],
  "message": "Empresas obtenidas exitosamente"
}
```

**Respuesta exitosa (200) - Analista Financiero:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Empresa ABC S.A.",
      "ruc": "20123456789",
      "tiene_catalogo": true,
      "total_cuentas": 50
    }
  ],
  "message": "Empresas obtenidas exitosamente"
}
```

**Respuesta de error (403) - Analista sin empresa asociada:**
```json
{
  "success": false,
  "message": "No tienes una empresa asociada"
}
```

---

### 2. Obtener catálogo de una empresa específica

Obtiene todas las cuentas del catálogo de una empresa.

**Restricciones por rol:**
- **Administrador**: Puede ver el catálogo de cualquier empresa
- **Analista Financiero**: Solo puede ver el catálogo de su empresa asociada

**Endpoint:** `GET /api/catalogo-cuentas/empresa/{empresaId}`

**Parámetros:**
- `empresaId` (number, requerido): ID de la empresa

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "empresa": {
      "id": 1,
      "nombre": "Empresa ABC S.A.",
      "ruc": "20123456789"
    },
    "cuentas": [
      {
        "id": 1,
        "empresa_id": 1,
        "codigo": "1.1.01",
        "nombre": "Caja General",
        "tipo": "ACTIVO",
        "es_calculada": false,
        "created_at": "2025-11-02T10:30:00.000000Z",
        "updated_at": "2025-11-02T10:30:00.000000Z"
      },
      {
        "id": 2,
        "empresa_id": 1,
        "codigo": "1.1.02",
        "nombre": "Bancos",
        "tipo": "ACTIVO",
        "es_calculada": false,
        "created_at": "2025-11-02T10:30:00.000000Z",
        "updated_at": "2025-11-02T10:30:00.000000Z"
      }
    ],
    "total": 2
  },
  "message": "Catálogo de cuentas obtenido exitosamente"
}
```

**Respuesta de error (403) - Analista intentando ver otra empresa:**
```json
{
  "success": false,
  "message": "No tienes permisos para ver el catálogo de esta empresa"
}
```

**Respuesta de error (404):**
```json
{
  "success": false,
  "message": "Error al obtener el catálogo de cuentas",
  "error": "No query results for model [App\\Models\\Empresa] 999"
}
```

---

### 3. Cargar/Reemplazar catálogo completo

Carga un nuevo catálogo de cuentas para una empresa. **IMPORTANTE:** Si la empresa ya tiene un catálogo, este será eliminado completamente y reemplazado por el nuevo.

**Restricciones por rol:**
- **Administrador**: Puede cargar el catáogo de cualquier empresa
- **Analista Financiero**: Solo puede cargar el catálogo de su empresa asociada

**Endpoint:** `POST /api/catalogo-cuentas`

**Body (JSON):**
```json
{
  "empresa_id": 1,
  "cuentas": [
    {
      "codigo": "1.1.01",
      "nombre": "Caja General",
      "tipo": "ACTIVO",
      "es_calculada": false
    },
    {
      "codigo": "1.1.02",
      "nombre": "Bancos Nacionales",
      "tipo": "ACTIVO",
      "es_calculada": false
    },
    {
      "codigo": "2.1.01",
      "nombre": "Cuentas por Pagar",
      "tipo": "PASIVO",
      "es_calculada": false
    },
    {
      "codigo": "3.1.01",
      "nombre": "Capital Social",
      "tipo": "PATRIMONIO",
      "es_calculada": false
    },
    {
      "codigo": "4.1.01",
      "nombre": "Ventas",
      "tipo": "INGRESO",
      "es_calculada": false
    },
    {
      "codigo": "5.1.01",
      "nombre": "Gastos Operacionales",
      "tipo": "GASTO",
      "es_calculada": false
    }
  ]
}
```

**Campos:**
- `empresa_id` (number, requerido): ID de la empresa
- `cuentas` (array, requerido): Array de cuentas (mínimo 1)
  - `codigo` (string, requerido, max: 50): Código único de la cuenta
  - `nombre` (string, requerido, max: 150): Nombre de la cuenta
  - `tipo` (enum, requerido): Debe ser uno de: ACTIVO, PASIVO, PATRIMONIO, INGRESO, GASTO
  - `es_calculada` (boolean, opcional, default: false): Indica si es una cuenta calculada

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "cuentas_creadas": 6,
    "cuentas": [
      {
        "id": 101,
        "empresa_id": 1,
        "codigo": "1.1.01",
        "nombre": "Caja General",
        "tipo": "ACTIVO",
        "es_calculada": false,
        "created_at": "2025-11-02T10:30:00.000000Z",
        "updated_at": "2025-11-02T10:30:00.000000Z"
      }
      // ... resto de cuentas
    ]
  },
  "message": "Catálogo de cuentas cargado exitosamente"
}
```

**Respuesta de validación (422):**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "empresa_id": ["La empresa especificada no existe"],
    "cuentas.0.codigo": ["El código de la cuenta es obligatorio"],
    "cuentas.1.tipo": ["El tipo debe ser: ACTIVO, PASIVO, PATRIMONIO, INGRESO o GASTO"]
  }
}
```

**Respuesta de códigos duplicados (422):**
```json
{
  "success": false,
  "message": "Existen códigos de cuenta duplicados en el archivo"
}
```

**Respuesta de error (403) - Analista intentando cargar catálogo de otra empresa:**
```json
{
  "success": false,
  "message": "No tienes permisos para cargar el catálogo de esta empresa"
}
```

---

### 4. Actualizar una cuenta específica

Actualiza los datos de una cuenta existente.

**Restricciones por rol:**
- **Administrador**: Puede actualizar cuentas de cualquier empresa
- **Analista Financiero**: Solo puede actualizar cuentas de su empresa asociada

**Endpoint:** `PUT /api/catalogo-cuentas/{id}`

**Parámetros:**
- `id` (number, requerido): ID de la cuenta

**Body (JSON):**
```json
{
  "codigo": "1.1.01",
  "nombre": "Caja General Actualizada",
  "tipo": "ACTIVO",
  "es_calculada": false
}
```

**Nota:** Todos los campos son opcionales, solo enviar los que se desean actualizar.

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "empresa_id": 1,
    "codigo": "1.1.01",
    "nombre": "Caja General Actualizada",
    "tipo": "ACTIVO",
    "es_calculada": false,
    "created_at": "2025-11-02T10:30:00.000000Z",
    "updated_at": "2025-11-02T15:45:00.000000Z"
  },
  "message": "Cuenta actualizada exitosamente"
}
```

**Respuesta de código duplicado (422):**
```json
{
  "success": false,
  "message": "Ya existe una cuenta con ese código en esta empresa"
}
```

**Respuesta de error (403) - Analista intentando actualizar cuenta de otra empresa:**
```json
{
  "success": false,
  "message": "No tienes permisos para actualizar cuentas de esta empresa"
}
```

---

### 5. Eliminar una cuenta específica

Elimina una cuenta del catálogo.

**Restricciones por rol:**
- **Administrador**: Puede eliminar cuentas de cualquier empresa
- **Analista Financiero**: Solo puede eliminar cuentas de su empresa asociada

**Endpoint:** `DELETE /api/catalogo-cuentas/{id}`

**Parámetros:**
- `id` (number, requerido): ID de la cuenta

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Cuenta eliminada exitosamente"
}
```

**Respuesta de error (403) - Analista intentando eliminar cuenta de otra empresa:**
```json
{
  "success": false,
  "message": "No tienes permisos para eliminar cuentas de esta empresa"
}
```

**Respuesta de error (404):**
```json
{
  "success": false,
  "message": "Error al eliminar la cuenta",
  "error": "No query results for model [App\\Models\\CatalogoCuenta] 999"
}
```

---

## Tipos de Cuenta

Los tipos de cuenta válidos son:
- `ACTIVO`: Recursos controlados por la empresa
- `PASIVO`: Obligaciones presentes de la empresa
- `PATRIMONIO`: Participación residual en los activos
- `INGRESO`: Incrementos en los beneficios económicos
- `GASTO`: Disminuciones en los beneficios económicos

---

## Códigos de Estado HTTP

- `200 OK`: Operación exitosa
- `201 Created`: Recurso creado exitosamente
- `401 Unauthorized`: No autenticado o token inválido
- `403 Forbidden`: No tiene permisos suficientes
- `404 Not Found`: Recurso no encontrado
- `422 Unprocessable Entity`: Error de validación
- `500 Internal Server Error`: Error del servidor

---

## 🔐 Control de Acceso por Rol

### Administrador
- ✅ Acceso completo a todas las empresas
- ✅ Puede ver catálogos de todas las empresas
- ✅ Puede cargar/actualizar/eliminar catálogos de cualquier empresa

### Analista Financiero
- ⚠️ **Acceso restringido solo a su empresa asociada**
- ✅ Puede ver el catálogo de su empresa (campo `empresa_id` en tabla `users`)
- ✅ Puede cargar/actualizar/eliminar cuentas de su empresa
- ❌ No puede acceder a catálogos de otras empresas
- ❌ Solo verá su empresa en la lista de empresas

**Nota importante:** Si un Analista Financiero no tiene una empresa asociada (`empresa_id` es `NULL`), recibirá un error 403 al intentar acceder a cualquier funcionalidad.

---

## Notas Importantes

1. **Reemplazo completo**: Al cargar un catálogo para una empresa que ya tiene uno, el catálogo anterior se elimina completamente.

2. **Códigos únicos**: Los códigos de cuenta deben ser únicos dentro de cada empresa.

3. **Transacciones**: La carga del catálogo se realiza dentro de una transacción de base de datos, si alguna cuenta falla, no se guarda ninguna.

4. **Permisos**: Todas las operaciones requieren el permiso `gestionar_catalogo_cuentas`.

5. **Relaciones**: Al eliminar una empresa, su catálogo de cuentas se elimina automáticamente (cascade).

---

## Ejemplo de uso con cURL

### Cargar catálogo desde archivo procesado:

```bash
curl -X POST http://tu-dominio.com/api/catalogo-cuentas \
  -H "Authorization: Bearer tu_token_aqui" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "empresa_id": 1,
    "cuentas": [
      {
        "codigo": "1.1.01",
        "nombre": "Caja General",
        "tipo": "ACTIVO"
      },
      {
        "codigo": "2.1.01",
        "nombre": "Proveedores",
        "tipo": "PASIVO"
      }
    ]
  }'
```

---

## Flujo recomendado para el frontend

1. **Listar empresas**: `GET /api/catalogo-cuentas/empresas`
2. **Usuario selecciona empresa y carga archivo** (CSV/Excel)
3. **Frontend procesa archivo** y extrae las cuentas
4. **Frontend determina tipo de cuenta** basándose en el código o permite al usuario seleccionarlo
5. **Frontend envía datos**: `POST /api/catalogo-cuentas` con empresa_id y array de cuentas
6. **Mostrar confirmación** al usuario
