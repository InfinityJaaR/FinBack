# CRUD de Gestión de Usuarios - Documentación

## 📋 Resumen de Implementación

Se ha implementado un sistema completo de CRUD para la gestión de usuarios con rol "Analista Financiero" por parte de administradores.

## 🔧 Backend (Laravel)

### Controlador: `UserController.php`

#### Métodos implementados:

1. **`index(Request $request)`** - Lista usuarios con filtros
   - Filtros disponibles:
     - `active`: 'todos', true, false
     - `empresa_id`: ID de empresa o 'null' para usuarios sin empresa
   
2. **`show($id)`** - Obtiene un usuario específico
   - Retorna usuario con sus roles y empresa

3. **`store(Request $request)`** - Crea nuevo usuario
   - Campos requeridos:
     - `name`: Nombre completo
     - `email`: Email único
     - `role_id`: ID del rol (siempre Analista Financiero en el frontend)
     - `empresa_id`: ID de empresa (opcional)
   - **NOTA**: No requiere contraseña. El usuario la establecerá en su primer login.

4. **`update(Request $request, $id)`** - Actualiza usuario
   - Campos opcionales:
     - `name`: Nuevo nombre
     - `email`: Nuevo email (debe ser único)
     - `role_id`: Cambiar rol
     - `active`: Activar/desactivar
     - `empresa_id`: Cambiar empresa
   - **NOTA**: No incluye contraseña

5. **`destroy($id)`** - Desactiva usuario (soft delete)
   - Establece `active = false`

6. **`reactive($id)`** - Reactiva usuario
   - Establece `active = true`

7. **`eliminarUsuario($id)`** - Elimina usuario permanentemente
   - Elimina el registro de la base de datos

### Rutas API (`routes/api.php`)

```php
Route::middleware(['auth:sanctum', 'role:Administrador', 'permiso:manage_users'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}/reactivate', [UserController::class, 'reactive']);
    Route::delete('/users/{id}/permanent', [UserController::class, 'eliminarUsuario']);
});
```

## 🎨 Frontend (React)

### Estructura de archivos

```
src/
├── services/
│   ├── GestionEmpresas/
│   │   └── Empresas/
│   │       └── EmpresasService.jsx
│   └── GestionUsuarios/
│       └── userService.jsx
├── hooks/
│   └── GestionUsuarios/
│       └── useUsers.jsx
├── components/
│   └── GestionUsuarios/
│       ├── UserTable.jsx
│       └── UserFormModal.jsx
└── pages/
    └── GestionUsuarios/
        └── UsersPage.jsx
```

### 1. Servicio: `userService.jsx`

Métodos disponibles:
- `getUsers(filters)` - Obtiene usuarios con filtros
- `getUser(id)` - Obtiene un usuario específico
- `createUser(userData)` - Crea nuevo usuario
- `updateUser(id, userData)` - Actualiza usuario
- `deactivateUser(id)` - Desactiva usuario
- `reactivateUser(id)` - Reactiva usuario
- `deleteUserPermanent(id)` - Elimina permanentemente
- `getAnalistaFinancieroRole()` - Obtiene el rol de Analista Financiero

### 2. Servicio: `EmpresasService.jsx`

Para obtener la lista de empresas para asignar:
- `getEmpresasBasic()` - Lista simple de empresas

### 3. Hook: `useUsers.jsx`

Estado y funciones disponibles:
```javascript
const {
    users,              // Array de usuarios
    empresas,           // Array de empresas disponibles
    analistaRole,       // Rol de Analista Financiero
    isLoading,          // Estado de carga
    error,              // Errores
    filters,            // Filtros actuales
    setFilters,         // Actualizar filtros
    handleCreateUser,   // Crear usuario
    handleUpdateUser,   // Actualizar usuario
    handleDeactivateUser,   // Desactivar
    handleReactivateUser,   // Reactivar
    handleDeleteUser,       // Eliminar permanentemente
    refreshUsers        // Recargar lista
} = useUsers();
```

### 4. Componente: `UserTable.jsx`

Tabla con las siguientes columnas:
- Nombre / Email
- Empresa asignada
- Estado (Activo/Inactivo)
- Acciones (Editar, Desactivar/Reactivar, Eliminar)

### 5. Componente: `UserFormModal.jsx`

Modal para crear/editar usuarios con:
- Campo Nombre (requerido)
- Campo Email (requerido, no editable en modo edición)
- Selector de Empresa (opcional)
- Rol fijo: Analista Financiero
- Nota informativa sobre la contraseña

### 6. Página: `UsersPage.jsx`

Página principal con:
- Header con botón "Nuevo Usuario"
- Filtros:
  - Búsqueda por nombre, email o empresa
  - Filtro por estado (Todos/Activos/Inactivos)
  - Filtro por empresa
- Contador de usuarios
- Tabla de usuarios
- Modal de formulario

### Rutas en `App.jsx`

```jsx
<Route
  path="usuarios"
  element={
    <PermissionRoute requiredPermissions={["manage_users"]}>
      <UsersPage />
    </PermissionRoute>
  }
/>
```

### Menú en Dashboard

Agregado item en el menú lateral:
```javascript
{
  icon: Users,
  label: "Usuarios",
  href: "/dashboard/usuarios",
  permissions: ["manage_users"],
  roles: ["Administrador"]
}
```

## 🔐 Seguridad y Permisos

- **Backend**: Requiere autenticación con Sanctum + rol "Administrador" + permiso "manage_users"
- **Frontend**: Protegido con `PermissionRoute` que valida el permiso "manage_users"
- **Contraseñas**: No se manejan en creación/edición. Los usuarios establecen su contraseña en el primer login

## 📝 Flujo de Usuario

1. **Crear Usuario**:
   - Admin accede a "Usuarios" desde el menú
   - Click en "Nuevo Usuario"
   - Completa nombre, email y opcionalmente empresa
   - Usuario recibe email para establecer contraseña
   - Primer login: usuario establece su contraseña

2. **Editar Usuario**:
   - Click en botón "Editar"
   - Puede cambiar: nombre y empresa
   - No puede cambiar: email (identificador único)

3. **Desactivar Usuario**:
   - Click en "Desactivar"
   - Usuario no puede acceder al sistema
   - Puede ser reactivado posteriormente

4. **Eliminar Usuario**:
   - Solo disponible para usuarios inactivos
   - Eliminación permanente (no se puede recuperar)

## 🎯 Características Especiales

1. **Sin contraseña en CRUD**: La contraseña se establece mediante el flujo de primer login
2. **Rol fijo**: Siempre crea usuarios con rol "Analista Financiero"
3. **Empresa opcional**: Puede asignarse durante la creación o posteriormente
4. **Filtros avanzados**: Por estado, empresa y búsqueda de texto
5. **Modal system**: Usa el ModalContext existente para confirmaciones
6. **UI consistente**: Sigue el mismo patrón de diseño que Empresas y Rubros

## 🧪 Pruebas

Para probar el sistema:

1. Inicia sesión como Administrador
2. Navega a "Usuarios" en el menú lateral
3. Crea un nuevo usuario con email único
4. Verifica que aparece en la tabla
5. Edita el usuario y cambia su empresa
6. Desactiva el usuario
7. Reactiva el usuario
8. Desactiva nuevamente y elimina permanentemente

## 📚 Dependencias

### Backend
- Laravel Sanctum (autenticación)
- Middleware de roles y permisos existente

### Frontend
- React Router (navegación)
- Axios (peticiones HTTP)
- Lucide React (iconos)
- Componentes UI existentes (Button, Alert)
- ModalContext (confirmaciones y alertas)

## 🔄 Próximos Pasos (Opcionales)

1. **Notificaciones por email**: Enviar email cuando se crea un usuario
2. **Historial de cambios**: Auditoría de modificaciones
3. **Asignación múltiple**: Permitir asignar múltiples empresas por usuario
4. **Importación masiva**: Cargar usuarios desde CSV/Excel
5. **Exportación**: Descargar lista de usuarios en Excel
