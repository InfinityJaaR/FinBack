# Integración Frontend - Backend: Catálogo de Cuentas

## 📋 Resumen de la Integración

Se ha completado la integración del módulo de Catálogo de Cuentas conectando el frontend React con el backend Laravel.

---

## 🎯 Funcionalidades Implementadas

### 1. **Servicio de API** (`CatalogoCuentasService.jsx`)
Ubicación: `/src/services/GestionCuentas/CatalogoCuentas/`

**Métodos disponibles:**
- `getEmpresasConCatalogo()` - Lista empresas con info de catálogo
- `getCatalogoByEmpresa(empresaId)` - Obtiene catálogo de una empresa
- `cargarCatalogo(catalogoData)` - Carga/reemplaza catálogo completo
- `actualizarCuenta(id, cuentaData)` - Actualiza cuenta individual
- `eliminarCuenta(id)` - Elimina una cuenta

### 2. **Hook Personalizado** (`useCatalogoCuentas.jsx`)
Ubicación: `/src/hooks/CatalogoCuentas/`

**Estado gestionado:**
```javascript
{
  cuentas,          // Array de cuentas del catálogo
  empresas,         // Array de empresas disponibles
  empresa,          // Empresa seleccionada actual
  loading,          // Estado de carga
  error            // Mensajes de error
}
```

**Funciones disponibles:**
- `cargarEmpresas()` - Carga lista de empresas
- `cargarCatalogo(empresaId)` - Carga catálogo por empresa
- `guardarCatalogo(catalogoData)` - Guarda catálogo completo
- `actualizarCuenta(id, cuentaData)` - Actualiza cuenta
- `eliminarCuenta(id)` - Elimina cuenta

### 3. **Componentes Actualizados**

#### **CatalogoCuentas.jsx**
- ✅ Integrado con `useCatalogoCuentas` hook
- ✅ Carga dinámica de empresas desde el backend
- ✅ Muestra total de cuentas por empresa
- ✅ Estados de carga con spinner
- ✅ Manejo de errores con alertas
- ✅ Actualización en tiempo real de cuentas

#### **NuevoCatalogo.jsx**
- ✅ Integrado con `useCatalogoCuentas` hook
- ✅ Carga empresas desde el backend
- ✅ Muestra advertencia si empresa ya tiene catálogo
- ✅ Determina automáticamente el tipo de cuenta (ACTIVO, PASIVO, etc.)
- ✅ Guarda catálogo con transacción
- ✅ Redirección automática después de guardar
- ✅ Manejo de errores del servidor

### 4. **Rutas Protegidas** (App.jsx)
```javascript
<PermissionRoute requiredPermissions={["gestionar_catalogo_cuentas"]}>
  <CatalogoPage />
</PermissionRoute>

<PermissionRoute requiredPermissions={["gestionar_catalogo_cuentas"]}>
  <NuevoCatalogoPage />
</PermissionRoute>
```

---

## 🔐 Permisos Configurados

### Backend (PermisoSeeder.php)
✅ Permiso creado: `gestionar_catalogo_cuentas`

**Roles con acceso:**
- ✅ **Administrador** - Acceso completo
- ✅ **Analista Financiero** - Acceso completo
- ❌ **Inversor** - Sin acceso

### Frontend (App.jsx)
✅ Rutas protegidas con `PermissionRoute`
✅ Solo usuarios con permiso `gestionar_catalogo_cuentas` pueden acceder

---

## 🔄 Flujo de Datos

### Cargar Catálogo
```
Usuario selecciona empresa
     ↓
useCatalogoCuentas.cargarCatalogo(empresaId)
     ↓
CatalogoCuentasService.getCatalogoByEmpresa(empresaId)
     ↓
Backend: GET /api/catalogo-cuentas/empresa/{empresaId}
     ↓
Actualiza estado: cuentas[], empresa
     ↓
CatalogoCuentas.jsx muestra lista de cuentas
```

### Guardar Nuevo Catálogo
```
Usuario sube archivo CSV/Excel
     ↓
NuevoCatalogo.jsx parsea archivo
     ↓
Usuario selecciona empresa
     ↓
Usuario hace clic en "Guardar"
     ↓
determinarTipoCuenta() asigna tipo según código
     ↓
useCatalogoCuentas.guardarCatalogo(catalogoData)
     ↓
CatalogoCuentasService.cargarCatalogo(catalogoData)
     ↓
Backend: POST /api/catalogo-cuentas
     ↓
Backend elimina catálogo anterior (transacción)
     ↓
Backend crea nuevo catálogo
     ↓
Frontend muestra mensaje de éxito
     ↓
Redirección automática a /dashboard/catalogo-cuentas
```

### Actualizar Cuenta
```
Usuario hace clic en "Editar" en una cuenta
     ↓
EditarCuenta.jsx abre dialog
     ↓
Usuario modifica datos
     ↓
CatalogoCuentas.handleSaveAccount(updatedAccount)
     ↓
useCatalogoCuentas.actualizarCuenta(id, data)
     ↓
Backend: PUT /api/catalogo-cuentas/{id}
     ↓
Hook actualiza estado local
     ↓
Lista se actualiza automáticamente
```

---

## 🧪 Guía de Testing

### 1. Verificar Backend (con cURL o Postman)

#### Obtener empresas:
```bash
curl -X GET http://localhost:8000/api/catalogo-cuentas/empresas \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Accept: application/json"
```

#### Cargar catálogo:
```bash
curl -X POST http://localhost:8000/api/catalogo-cuentas \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "empresa_id": 1,
    "cuentas": [
      {"codigo": "1.1.01", "nombre": "Caja General", "tipo": "ACTIVO"},
      {"codigo": "2.1.01", "nombre": "Proveedores", "tipo": "PASIVO"}
    ]
  }'
```

### 2. Testing Frontend

#### Paso 1: Iniciar el servidor Laravel
```bash
cd backend/FinBack
php artisan serve
```

#### Paso 2: Iniciar el frontend React
```bash
cd frontend/ContabilidadCliente
npm run dev
```

#### Paso 3: Probar flujo completo

1. **Login:**
   - Iniciar sesión con usuario Administrador o Analista Financiero
   - Verificar que aparece "Catálogo de Cuentas" en el menú

2. **Ver Catálogo:**
   - Navegar a `/dashboard/catalogo-cuentas`
   - Seleccionar una empresa del dropdown
   - Verificar que carga las cuentas correctamente
   - Probar búsqueda por código o nombre

3. **Editar Cuenta:**
   - Hacer clic en "Editar" en cualquier cuenta
   - Modificar nombre o código
   - Guardar y verificar actualización

4. **Nuevo Catálogo:**
   - Hacer clic en "Nuevo Catálogo"
   - Descargar plantilla CSV
   - Seleccionar empresa
   - Cargar archivo
   - Verificar preview de cuentas
   - Guardar y verificar redirección

5. **Validar Permisos:**
   - Cerrar sesión
   - Iniciar con usuario Inversor
   - Verificar que NO aparece opción de Catálogo de Cuentas
   - Intentar acceder directamente a `/dashboard/catalogo-cuentas`
   - Verificar redirección al dashboard

---

## 📝 Lógica de Negocio Implementada

### Determinación Automática de Tipo de Cuenta
```javascript
const determinarTipoCuenta = (codigo) => {
  const primerDigito = codigo.toString()[0]
  
  switch(primerDigito) {
    case '1': return 'ACTIVO'
    case '2': return 'PASIVO'
    case '3': return 'PATRIMONIO'
    case '4': return 'INGRESO'
    case '5': return 'GASTO'
    default: return 'ACTIVO'
  }
}
```

### Reemplazo de Catálogo
- ⚠️ **Advertencia visual**: Se muestra "(Tiene catálogo - Se reemplazará)" en el selector
- 🔄 **Transaccional**: Backend elimina todo el catálogo anterior antes de insertar el nuevo
- ✅ **Sin duplicados**: Backend valida códigos únicos por empresa

---

## 🐛 Manejo de Errores

### Frontend
```javascript
// Errores de red
catch (err) {
  const errorMsg = err.response?.data?.message || 
                   err.message || 
                   'Error al guardar el catálogo'
  setError(errorMsg)
}
```

### Backend
- **422**: Errores de validación (códigos duplicados, empresa inexistente)
- **404**: Empresa o cuenta no encontrada
- **500**: Error del servidor

---

## 📊 Formato de Datos

### Request: Cargar Catálogo
```json
{
  "empresa_id": 1,
  "cuentas": [
    {
      "codigo": "1.1.01",
      "nombre": "Caja General",
      "tipo": "ACTIVO",
      "es_calculada": false
    }
  ]
}
```

### Response: Lista de Empresas
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
  ]
}
```

### Response: Catálogo por Empresa
```json
{
  "success": true,
  "data": {
    "empresa": {
      "id": 1,
      "nombre": "Empresa ABC S.A."
    },
    "cuentas": [
      {
        "id": 1,
        "codigo": "1.1.01",
        "nombre": "Caja General",
        "tipo": "ACTIVO",
        "es_calculada": false
      }
    ],
    "total": 1
  }
}
```

---

## ✅ Checklist de Integración

- ✅ Servicio API creado
- ✅ Hook personalizado implementado
- ✅ CatalogoCuentas integrado con API
- ✅ NuevoCatalogo integrado con API
- ✅ Rutas protegidas con permisos
- ✅ Permisos agregados al seeder
- ✅ Estados de carga implementados
- ✅ Manejo de errores completo
- ✅ Validación de permisos frontend
- ✅ Documentación API disponible
- ⏳ **PENDIENTE: Testing completo**

---

## 🚀 Próximos Pasos

1. **Testing completo** de todos los endpoints
2. **Validación de permisos** con diferentes roles
3. **Testing de carga de archivos** CSV y Excel grandes
4. **Optimización** si hay problemas de rendimiento
5. **Agregar funcionalidad de eliminación** de cuentas desde la UI (actualmente solo edición)

---

## 📞 Soporte

Si encuentras algún error durante el testing:

1. Verificar que el token de autenticación sea válido
2. Verificar que el usuario tenga el permiso `gestionar_catalogo_cuentas`
3. Revisar la consola del navegador para errores de red
4. Revisar los logs de Laravel: `storage/logs/laravel.log`
5. Verificar que las rutas en `api.php` estén correctas

---

**Documentación actualizada**: 2 de noviembre de 2025
