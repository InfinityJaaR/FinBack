# Fix: Compatibilidad MySQL vs MariaDB

## 🐛 Problema Identificado

Al desplegar la aplicación en Google Cloud con **MariaDB**, surgió un error donde el sistema indicaba que una empresa **no tenía catálogo de cuentas** cuando en realidad sí lo tenía. Este error **no ocurría en local** con MySQL.

### Contexto
- **Local**: MySQL
- **Producción (Google Cloud)**: MariaDB
- **Error**: "La empresa no tiene catálogo" al intentar guardar estados financieros

## 🔍 Causa Raíz

El problema se originaba por diferencias en cómo **MySQL** y **MariaDB** manejan ciertos queries de Eloquent, específicamente:

1. **`withCount()` con relaciones camelCase**: 
   - Laravel usa `withCount('catalogoCuentas')` para contar relaciones
   - MariaDB puede tener problemas interpretando esto debido a case-sensitivity en Linux
   - El contador `$empresa->catalogo_cuentas_count` retornaba `0` incorrectamente

2. **Strict Mode**:
   - MySQL y MariaDB tienen configuraciones diferentes de `strict mode`
   - MariaDB puede ser más estricto con queries ambiguas

## ✅ Solución Implementada

### 1. Cambio en `EstadoFinancieroController.php`

**Antes:**
```php
$empresas = Empresa::withCount('catalogoCuentas')
    ->get()
    ->map(function ($empresa) {
        return [
            'tiene_catalogo' => $empresa->catalogo_cuentas_count > 0,
        ];
    });
```

**Después:**
```php
$empresas = Empresa::get()
    ->map(function ($empresa) {
        $totalCuentas = CatalogoCuenta::where('empresa_id', $empresa->id)->count();
        return [
            'tiene_catalogo' => $totalCuentas > 0,
        ];
    });
```

### 2. Cambio en `CatalogoCuentaController.php`

Se aplicó el mismo fix en el método `obtenerEmpresas()`:

```php
// En lugar de withCount(), ahora hacemos el conteo directo
$totalCuentas = CatalogoCuenta::where('empresa_id', $empresa->id)->count();
```

### 3. Configuración de `config/database.php`

Cambiamos el `strict mode` para que sea configurable via `.env`:

```php
'mysql' => [
    'strict' => env('DB_STRICT', false), // Antes era: true
    // ...
],
```

### 4. Actualización de `.env.example`

Agregamos documentación sobre la configuración de `DB_STRICT`:

```bash
# DB_STRICT=false
# Nota: Si usas MariaDB en producción, asegúrate de configurar DB_STRICT=false para compatibilidad
```

## 📝 Instrucciones de Despliegue

### En Producción (Google Cloud con MariaDB)

1. **Actualizar el código** (ya incluido en los archivos modificados)

2. **Configurar `.env` en producción**:
   ```bash
   DB_CONNECTION=mysql
   DB_HOST=tu_host_mariadb
   DB_PORT=3306
   DB_DATABASE=tu_base_datos
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_password
   DB_STRICT=false
   ```

3. **Limpiar cachés**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Reconstruir caché de configuración** (solo en producción):
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

5. **Verificar que funciona**:
   ```bash
   php artisan tinker
   # Dentro de tinker:
   $empresa = \App\Models\Empresa::find(1);
   $total = \App\Models\CatalogoCuenta::where('empresa_id', 1)->count();
   echo "Total cuentas: " . $total;
   ```

## 🎯 Ventajas de esta Solución

1. ✅ **Mayor compatibilidad**: Funciona en MySQL, MariaDB y otros motores SQL
2. ✅ **Más explícito**: El conteo directo es más claro y predecible
3. ✅ **Menos dependencia de Eloquent**: Reduce posibles bugs por diferencias de interpretación
4. ✅ **Performance similar**: El query generado es prácticamente el mismo

## 📊 Testing

### Verificar en Local (MySQL)
```bash
# Asegúrate de que todo funciona igual que antes
php artisan test
```

### Verificar en Producción (MariaDB)
```bash
# Probar obtener empresas
curl -X GET "https://tu-api.com/api/estados-financieros/empresas" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Probar obtener catálogo
curl -X GET "https://tu-api.com/api/catalogo-cuentas/empresas" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🔄 Archivos Modificados

- `app/Http/Controllers/EstadoFinancieroController.php`
- `app/Http/Controllers/CatalogoCuentaController.php`
- `config/database.php`
- `.env.example`

## 📚 Referencias

- [Laravel Eloquent: Counting Related Models](https://laravel.com/docs/eloquent-relationships#counting-related-models)
- [MySQL vs MariaDB Differences](https://mariadb.com/kb/en/mariadb-vs-mysql-compatibility/)
- [Laravel Database Configuration](https://laravel.com/docs/database#configuration)

---

**Fecha de implementación**: 2025-11-11  
**Desarrollador**: Sistema  
**Versión**: 1.0
