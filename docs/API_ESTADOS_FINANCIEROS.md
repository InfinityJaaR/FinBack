# API Estados Financieros - Documentación

## Base URL
```
http://localhost:8000/api
```

## Autenticación
Todas las rutas requieren autenticación mediante Sanctum token en el header:
```
Authorization: Bearer {token}
```

---

## 📋 Endpoints

### 1. Obtener Empresas con Catálogo

**GET** `/estados-financieros/empresas`

Obtiene la lista de empresas que tienen catálogo de cuentas disponible.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "Empresa A S.A.",
      "ruc": "1234567890",
      "tiene_catalogo": true
    }
  ],
  "message": "Empresas obtenidas exitosamente"
}
```

---

### 2. Obtener Periodos

**GET** `/estados-financieros/periodos`

Obtiene la lista de periodos fiscales disponibles.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "año": 2024,
      "nombre": "2024"
    }
  ],
  "message": "Periodos obtenidos exitosamente"
}
```

---

### 3. Descargar Plantilla CSV

**GET** `/estados-financieros/plantilla?empresa_id={id}&tipo={BALANCE|RESULTADOS}`

Descarga un archivo CSV pre-llenado con las cuentas del catálogo según el tipo de estado.

**Query Parameters:**
- `empresa_id` (required): ID de la empresa
- `tipo` (required): `BALANCE` o `RESULTADOS`

**Response:**
Archivo CSV con formato:
```csv
Código,Nombre de Cuenta,Monto
1.1.01,Caja,0
1.1.02,Bancos,0
...
```

**Características:**
- ✅ Filtra automáticamente las cuentas según `estado_financiero`
- ✅ BALANCE → cuentas con `estado_financiero='BALANCE_GENERAL'`
- ✅ RESULTADOS → cuentas con `estado_financiero='ESTADO_RESULTADOS'`
- ✅ Nombre de archivo: `plantilla_balance_{empresa}_{id}.csv`

---

### 4. Listar Estados Financieros

**GET** `/estados-financieros?empresa_id={id}&periodo_id={id}&tipo={tipo}`

Obtiene la lista de estados financieros con filtros opcionales.

**Query Parameters (opcionales):**
- `empresa_id`: Filtrar por empresa
- `periodo_id`: Filtrar por periodo
- `tipo`: Filtrar por tipo (`BALANCE` o `RESULTADOS`)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "empresa_id": 1,
      "periodo_id": 1,
      "tipo": "BALANCE",
      "created_at": "2024-11-02T10:00:00.000000Z",
      "empresa": {
        "id": 1,
        "nombre": "Empresa A S.A."
      },
      "periodo": {
        "id": 1,
        "año": 2024
      },
      "detalles": [
        {
          "id": 1,
          "estado_id": 1,
          "catalogo_cuenta_id": 5,
          "monto": "10000.00",
          "catalogo_cuenta": {
            "id": 5,
            "codigo": "1.1.01",
            "nombre": "Caja"
          }
        }
      ]
    }
  ],
  "message": "Estados financieros obtenidos exitosamente"
}
```

---

### 5. Obtener Estado Financiero Específico

**GET** `/estados-financieros/{id}`

Obtiene un estado financiero con todos sus detalles.

**Response:** Mismo formato que el item en el listado anterior.

---

### 6. Crear Estado Financiero

**POST** `/estados-financieros`

Crea un nuevo estado financiero con sus detalles.

**Request Body:**
```json
{
  "empresa_id": 1,
  "periodo_id": 1,
  "tipo": "BALANCE",
  "detalles": [
    {
      "catalogo_cuenta_id": 5,
      "monto": 10000.50
    },
    {
      "catalogo_cuenta_id": 6,
      "monto": 5000.00
    }
  ]
}
```

**Validaciones:**
- ✅ `empresa_id`: requerido, debe existir
- ✅ `periodo_id`: requerido, debe existir
- ✅ `tipo`: requerido, solo `BALANCE` o `RESULTADOS`
- ✅ `detalles`: requerido, array con mínimo 1 elemento
- ✅ `detalles.*.catalogo_cuenta_id`: requerido, debe existir
- ✅ `detalles.*.monto`: requerido, numérico
- ⚠️ No permite duplicados: empresa + periodo + tipo debe ser único

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "empresa_id": 1,
    "periodo_id": 1,
    "tipo": "BALANCE",
    "detalles": [...]
  },
  "message": "Estado financiero creado exitosamente"
}
```

**Error Duplicado (422):**
```json
{
  "success": false,
  "message": "Ya existe un estado financiero de este tipo para esta empresa y periodo"
}
```

---

### 7. Actualizar Estado Financiero

**PUT** `/estados-financieros/{id}`

Actualiza los detalles de un estado financiero existente.

**Request Body:**
```json
{
  "detalles": [
    {
      "catalogo_cuenta_id": 5,
      "monto": 15000.00
    },
    {
      "catalogo_cuenta_id": 6,
      "monto": 7500.00
    }
  ]
}
```

**Nota:** Los detalles se reemplazan completamente, no se hace merge.

**Response (200):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Estado financiero actualizado exitosamente"
}
```

---

### 8. Eliminar Estado Financiero

**DELETE** `/estados-financieros/{id}`

Elimina un estado financiero y todos sus detalles (cascade).

**Response (200):**
```json
{
  "success": true,
  "message": "Estado financiero eliminado exitosamente"
}
```

---

## 🔐 Permisos y Roles

### Analista Financiero
- ✅ Solo puede ver/crear estados de su empresa asignada
- ✅ `obtenerEmpresas()` devuelve solo su empresa
- ⚠️ No puede acceder a estados de otras empresas

### Administrador
- ✅ Acceso completo a todas las empresas
- ✅ Puede crear/editar/eliminar cualquier estado financiero

---

## 📊 Flujo de Uso Recomendado

### Crear Estado Financiero por Importación CSV

1. **Obtener empresas disponibles**
   ```
   GET /estados-financieros/empresas
   ```

2. **Obtener periodos disponibles**
   ```
   GET /estados-financieros/periodos
   ```

3. **Descargar plantilla CSV**
   ```
   GET /estados-financieros/plantilla?empresa_id=1&tipo=BALANCE
   ```

4. **Usuario completa el CSV** con los montos

5. **Frontend parsea el CSV** y construye el JSON

6. **Crear el estado financiero**
   ```
   POST /estados-financieros
   {
     "empresa_id": 1,
     "periodo_id": 1,
     "tipo": "BALANCE",
     "detalles": [...]
   }
   ```

---

## 🚨 Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 201 | Created - Recurso creado |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Error de validación |
| 500 | Internal Server Error - Error del servidor |

---

## 📝 Notas Importantes

1. **Plantilla CSV inteligente**: 
   - Filtra automáticamente las cuentas según el tipo de estado seleccionado
   - Balance → Solo muestra cuentas 1, 2, 3 (Activo, Pasivo, Patrimonio)
   - Resultados → Solo muestra cuentas 4, 5, 6, 7 (Ingresos, Costos, Gastos, Resultados)

2. **Prevención de duplicados**:
   - No se puede tener más de un Balance General para la misma empresa y periodo
   - No se puede tener más de un Estado de Resultados para la misma empresa y periodo

3. **Eliminación en cascada**:
   - Al eliminar un estado financiero, todos sus detalles se eliminan automáticamente

4. **Transacciones**:
   - Todas las operaciones de escritura usan transacciones DB para garantizar consistencia
