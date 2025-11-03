# Actualización: Control de Acceso para Analista Financiero

## 📋 Cambios Implementados

Se ha agregado control de acceso basado en rol para que el **Analista Financiero** solo pueda gestionar el catálogo de cuentas de la empresa a la que está asociado.

---

## 🔒 Restricciones Implementadas

### Método: `empresasConCatalogo()`
**Antes:** Retornaba todas las empresas del sistema  
**Ahora:** 
- **Administrador**: Ve todas las empresas
- **Analista Financiero**: Solo ve su empresa asociada (campo `empresa_id` de su usuario)

### Método: `index($empresaId)`
**Antes:** Cualquier usuario podía ver el catálogo de cualquier empresa  
**Ahora:**
- **Administrador**: Puede ver catálogo de cualquier empresa
- **Analista Financiero**: Solo puede ver catálogo de su empresa asociada
- **Error 403** si intenta ver otra empresa

### Método: `store(Request $request)`
**Antes:** Cualquier usuario podía cargar catálogo a cualquier empresa  
**Ahora:**
- **Administrador**: Puede cargar catálogo a cualquier empresa
- **Analista Financiero**: Solo puede cargar catálogo a su empresa asociada
- **Error 403** si intenta cargar a otra empresa

### Método: `update(Request $request, $id)`
**Antes:** Cualquier usuario podía actualizar cualquier cuenta  
**Ahora:**
- **Administrador**: Puede actualizar cuentas de cualquier empresa
- **Analista Financiero**: Solo puede actualizar cuentas de su empresa asociada
- **Error 403** si intenta actualizar cuenta de otra empresa

### Método: `destroy($id)`
**Antes:** Cualquier usuario podía eliminar cualquier cuenta  
**Ahora:**
- **Administrador**: Puede eliminar cuentas de cualquier empresa
- **Analista Financiero**: Solo puede eliminar cuentas de su empresa asociada
- **Error 403** si intenta eliminar cuenta de otra empresa

---

## 🛠️ Implementación Técnica

### Nuevo Método Auxiliar

Se agregó un método privado para verificar el rol del usuario:

```php
private function esAnalistaFinanciero($user)
{
    $roles = $user->roles->pluck('name')->toArray();
    return in_array('Analista Financiero', $roles) && !in_array('Administrador', $roles);
}
```

**Lógica:** Un usuario es considerado "Analista Financiero" solo si tiene ese rol y NO es Administrador (evita conflictos si un usuario tiene ambos roles).

### Validaciones Agregadas

En cada método se agregó la siguiente validación:

```php
$user = auth()->user();

if ($this->esAnalistaFinanciero($user)) {
    // Verificar que tiene empresa asociada
    if (!$user->empresa_id) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes una empresa asociada'
        ], 403);
    }
    
    // Verificar que está accediendo a su propia empresa
    if ($user->empresa_id != $empresaId) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para [acción] de esta empresa'
        ], 403);
    }
}
```

---

## 📊 Ejemplos de Respuestas

### Caso 1: Analista sin empresa asociada
```json
{
  "success": false,
  "message": "No tienes una empresa asociada"
}
```
**Status Code:** 403 Forbidden

### Caso 2: Analista intentando acceder a otra empresa
```json
{
  "success": false,
  "message": "No tienes permisos para ver el catálogo de esta empresa"
}
```
**Status Code:** 403 Forbidden

### Caso 3: Analista accediendo a su propia empresa
```json
{
  "success": true,
  "data": {
    "empresa": {...},
    "cuentas": [...]
  },
  "message": "Catálogo de cuentas obtenido exitosamente"
}
```
**Status Code:** 200 OK

---

## 🧪 Cómo Probar

### 1. Crear Usuario Analista Financiero

```sql
-- Asegurarse de que el usuario tenga empresa_id establecido
UPDATE users 
SET empresa_id = 1 
WHERE email = 'analista@empresa.com';
```

### 2. Obtener Token de Autenticación

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "analista@empresa.com",
    "password": "password"
  }'
```

### 3. Probar Lista de Empresas

```bash
# Como Analista - Debe retornar solo SU empresa
curl -X GET http://localhost:8000/api/catalogo-cuentas/empresas \
  -H "Authorization: Bearer TOKEN_ANALISTA"

# Como Administrador - Debe retornar TODAS las empresas
curl -X GET http://localhost:8000/api/catalogo-cuentas/empresas \
  -H "Authorization: Bearer TOKEN_ADMIN"
```

### 4. Intentar Acceder a Otra Empresa (Debe Fallar)

```bash
# Analista de empresa_id=1 intenta ver empresa_id=2
curl -X GET http://localhost:8000/api/catalogo-cuentas/empresa/2 \
  -H "Authorization: Bearer TOKEN_ANALISTA"

# Respuesta esperada: 403 Forbidden
```

### 5. Acceder a Su Propia Empresa (Debe Funcionar)

```bash
# Analista de empresa_id=1 ve su propia empresa
curl -X GET http://localhost:8000/api/catalogo-cuentas/empresa/1 \
  -H "Authorization: Bearer TOKEN_ANALISTA"

# Respuesta esperada: 200 OK con datos
```

---

## ✅ Checklist de Validación

- ✅ Analista solo ve su empresa en lista de empresas
- ✅ Analista puede ver catálogo de su empresa
- ✅ Analista NO puede ver catálogo de otras empresas (403)
- ✅ Analista puede cargar catálogo a su empresa
- ✅ Analista NO puede cargar catálogo a otras empresas (403)
- ✅ Analista puede actualizar cuentas de su empresa
- ✅ Analista NO puede actualizar cuentas de otras empresas (403)
- ✅ Analista puede eliminar cuentas de su empresa
- ✅ Analista NO puede eliminar cuentas de otras empresas (403)
- ✅ Analista sin empresa_id recibe error 403
- ✅ Administrador mantiene acceso completo a todas las empresas

---

## 📚 Archivos Modificados

1. **`/backend/FinBack/app/Http/Controllers/CatalogoCuentaController.php`**
   - Agregado método `esAnalistaFinanciero()`
   - Modificados 5 métodos con validaciones de acceso

2. **`/backend/FinBack/docs/API_CatalogoCuentas.md`**
   - Actualizada documentación con restricciones por rol
   - Agregados ejemplos de respuestas 403

3. **`/backend/FinBack/docs/RESUMEN_CATALOGO_CUENTAS.md`**
   - Actualizada tabla de permisos por rol
   - Agregado control de acceso detallado

4. **`/backend/FinBack/docs/CAMBIOS_CONTROL_ACCESO.md`** (este archivo)
   - Documentación de los cambios implementados

---

## 🔄 Comportamiento Frontend

El frontend **NO requiere cambios** ya que:

1. El servicio `CatalogoCuentasService` maneja automáticamente las respuestas 403
2. El hook `useCatalogoCuentas` captura los errores y los muestra al usuario
3. La lista de empresas se filtrará automáticamente en el backend
4. El selector de empresas mostrará solo las empresas permitidas

**Resultado:** El Analista Financiero solo verá y podrá interactuar con su empresa, sin necesidad de modificar el código del frontend.

---

## 🎯 Ventajas de Esta Implementación

1. **Seguridad en el backend**: Las validaciones están en el servidor, no se pueden eludir desde el cliente
2. **Sin cambios en frontend**: El mismo código funciona para ambos roles
3. **Mensajes claros**: Errores 403 con mensajes descriptivos
4. **Mantenible**: Lógica centralizada en método `esAnalistaFinanciero()`
5. **Flexible**: Fácil agregar más roles o reglas en el futuro

---

**Fecha de implementación:** 2 de noviembre de 2025  
**Desarrollador:** GitHub Copilot + InfinityJaaR  
**Estado:** ✅ Completado y listo para testing
