# Relación Usuario-Empresa

## Descripción General

Se ha implementado una relación **Uno a Muchos** entre las tablas `empresas` y `users`:
- **Un usuario** puede pertenecer a **una empresa** o a **ninguna** (empresa_id nullable)
- **Una empresa** puede tener **múltiples usuarios** asignados

## Cambios en la Base de Datos

### Migración: `add_empresa_id_to_users_table`

Se agregó el campo `empresa_id` a la tabla `users`:
```php
$table->foreignId('empresa_id')
    ->nullable()
    ->after('active')
    ->constrained('empresas')
    ->onDelete('set null');
```

**Características:**
- Campo nullable: permite usuarios sin empresa asignada
- Foreign key a tabla `empresas`
- `onDelete('set null')`: Si se elimina la empresa, el usuario queda sin empresa asignada

## Modelos Actualizados

### Modelo User

**Cambios:**
1. Agregado `empresa_id` al array `$fillable`
2. Nueva relación:
```php
public function empresa()
{
    return $this->belongsTo(Empresa::class);
}
```

### Modelo Empresa

**Cambios:**
1. Nueva relación:
```php
public function usuarios(): HasMany
{
    return $this->hasMany(User::class);
}
```

## Controladores Actualizados

### UserController

**Método `index()`:**
- Ahora carga la relación `empresa` con `with(['roles', 'empresa'])`
- Nuevo parámetro de filtro `empresa_id`:
  - `?empresa_id=1` - Filtra usuarios de la empresa con ID 1
  - `?empresa_id=null` - Filtra usuarios sin empresa asignada

**Método `show()`:**
- Carga la relación `empresa` con el usuario

**Método `update()`:**
- Validación agregada: `'empresa_id' => 'nullable|exists:empresas,id'`
- Permite actualizar el campo `empresa_id`

### AuthController

**Método `register()`:**
- Validación agregada: `'empresa_id' => 'nullable|exists:empresas,id'`
- Permite asignar empresa al crear un usuario

### EmpresaController

**Nuevo método `usuarios()`:**
```php
GET /empresas/{empresa}/usuarios
```
Lista todos los usuarios asociados a una empresa específica.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "empresa": {
      "id": 1,
      "nombre": "Empresa Ejemplo",
      "codigo": "EMP001"
    },
    "usuarios": [
      {
        "id": 2,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "empresa_id": 1,
        "roles": [...]
      }
    ],
    "total_usuarios": 1
  }
}
```

## Rutas API

### Nueva Ruta

```php
GET /api/empresas/{empresa}/usuarios
```
- **Middleware:** `auth:sanctum`, `role:Administrador`, `permiso:gestionar_empresas`
- **Descripción:** Lista usuarios de una empresa
- **Respuesta:** Ver ejemplo arriba

### Rutas Existentes Actualizadas

**GET /api/users**
- Nuevo parámetro: `?empresa_id={id|null}`

**POST /api/register**
- Nuevo campo opcional: `empresa_id`

**PUT /api/users/{id}**
- Nuevo campo opcional: `empresa_id`

## Middleware EmpresaAccess (Opcional)

Se creó el middleware `EmpresaAccessMiddleware` que verifica el acceso por empresa.

### Lógica:
1. **Administradores**: Acceso completo a todas las empresas
2. **Usuarios con empresa_id**: Solo pueden acceder a su propia empresa
3. **Usuarios sin empresa_id**: No pueden acceder a empresas específicas

### Uso:
```php
Route::middleware(['auth:sanctum', 'empresa.access'])->group(function () {
    Route::get('/empresas/{empresa}/datos', [Controller::class, 'metodo']);
});
```

### Alias registrado:
```php
'empresa.access' => EmpresaAccessMiddleware::class
```

## Seeders Actualizados

### UserSeeder

**Cambios:**
- El usuario **Administrador** no tiene empresa asignada (`empresa_id = null`)
- Los usuarios **Analista Financiero** e **Inversor** se asignan a empresas existentes de forma rotativa

```php
// Ejemplo de asignación
$empresas = Empresa::limit(3)->get();
$empresaId = ($rol->name !== 'Administrador' && $empresas->isNotEmpty()) 
    ? $empresas[$index % $empresas->count()]->id 
    : null;
```

## Ejemplos de Uso

### 1. Crear usuario con empresa asignada

```bash
POST /api/register
Content-Type: application/json

{
  "name": "María García",
  "email": "maria@example.com",
  "role_id": 2,
  "empresa_id": 5
}
```

### 2. Actualizar empresa de un usuario

```bash
PUT /api/users/3
Content-Type: application/json

{
  "empresa_id": 7
}
```

### 3. Listar usuarios sin empresa

```bash
GET /api/users?empresa_id=null
```

### 4. Listar usuarios de una empresa específica

```bash
GET /api/users?empresa_id=5
```

### 5. Obtener usuarios de una empresa

```bash
GET /api/empresas/5/usuarios
```

## Casos de Uso

### Caso 1: Usuario Administrador
- No tiene `empresa_id` asignado
- Puede gestionar todas las empresas
- Puede ver y modificar usuarios de cualquier empresa

### Caso 2: Usuario Analista Financiero
- Tiene `empresa_id = 5`
- Solo puede acceder a datos de la empresa 5
- Con middleware `empresa.access`, no puede acceder a otras empresas

### Caso 3: Usuario Inversor
- Tiene `empresa_id = 3`
- Solo puede ver información de la empresa 3
- Puede ver ratios y estados financieros solo de su empresa

### Caso 4: Usuario sin Empresa
- `empresa_id = null`
- Usuario creado pero sin asignación
- Administrador debe asignarle una empresa

## Consideraciones de Seguridad

1. **Validación de empresa_id:**
   - Siempre se valida que la empresa exista: `exists:empresas,id`
   - El campo es nullable por diseño

2. **Protección de rutas:**
   - Usar middleware `empresa.access` en rutas sensibles
   - Los administradores tienen acceso completo

3. **Eliminación de empresas:**
   - `onDelete('set null')`: Los usuarios quedan sin empresa
   - Alternativa: usar `onDelete('restrict')` para evitar eliminar empresas con usuarios

## Migraciones

### Ejecutar migración:
```bash
php artisan migrate
```

### Revertir migración:
```bash
php artisan migrate:rollback
```

La reversión eliminará:
- La foreign key `empresa_id`
- La columna `empresa_id` de la tabla `users`

## Testing

### Verificar relaciones en Tinker:
```bash
php artisan tinker

# Ver usuarios con sus empresas
User::with('empresa')->get()

# Ver empresa con sus usuarios
Empresa::with('usuarios')->find(1)

# Contar usuarios por empresa
Empresa::withCount('usuarios')->get()
```

## Próximos Pasos Sugeridos

1. **Filtros adicionales:**
   - Filtrar empresas por usuarios activos
   - Estadísticas de usuarios por empresa

2. **Notificaciones:**
   - Notificar al usuario cuando se le asigna/desasigna una empresa

3. **Auditoría:**
   - Registrar cambios de empresa_id en logs

4. **Frontend:**
   - Select para asignar empresa en formulario de usuarios
   - Dashboard con filtro por empresa para usuarios no-admin
