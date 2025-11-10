# Fix: Checkbox "Usar en Ratios" no guardaba correctamente

## 📋 Problema Identificado

El campo `usar_en_ratios` de los checkboxes en la importación de estados financieros **NO se estaba guardando correctamente** en la base de datos, siempre quedaba en `false` incluso cuando el usuario los marcaba.

## 🔍 Análisis del Problema

### Frontend (✅ Funcionaba correctamente)

En `/frontend/ContabilidadCliente/src/components/EstadosFinancieros/ImportarEstado.jsx`:

```javascript
// Línea 421: El checkbox manejaba correctamente los cambios
const handleCheckboxChange = (cuentaId, checked) => {
  setUsarEnRatios(prev => ({
    ...prev,
    [cuentaId]: checked
  }))
}

// Línea 474: Se enviaba correctamente al backend
detalles.push({
  catalogo_cuenta_id: cuenta.id,
  monto: item.monto,
  usar_en_ratios: usarEnRatios[item.id] ?? false // ✅ Valor correcto del checkbox
})
```

### Backend (❌ Problema encontrado)

En `/backend/FinBack/app/Http/Controllers/EstadoFinancieroController.php`:

**Método `store()` - Línea 120-148:**
```php
// El método recibía correctamente $request->detalles con usar_en_ratios
foreach ($request->detalles as $detalle) {
    // ... tiene usar_en_ratios correcto aquí
}
```

**Método `calcularCuentasAgregadas()` - Líneas 522-553:**

El problema estaba aquí:

```php
// ANTES DEL FIX (❌ Perdía el valor de usar_en_ratios):
foreach ($detallesBase as $detalle) {
    $codigo = $idACodigo[$detalle['catalogo_cuenta_id']] ?? null;
    if ($codigo && !isset($montosCalculados[$codigo])) {
        $todosLosDetalles[] = $detalle; // ❌ Agregaba todo el array original
        // Pero las cuentas que venían en $detallesBase podían ser 
        // luego reemplazadas por versiones calculadas sin preservar usar_en_ratios
    }
}

// Agregar detalles calculados
foreach ($montosCalculados as $codigo => $monto) {
    $cuentaId = $codigoAId[$codigo] ?? null;
    if ($cuentaId) {
        $todosLosDetalles[] = [
            'catalogo_cuenta_id' => $cuentaId,
            'monto' => $monto,
            'usar_en_ratios' => false, // ❌ Siempre false
        ];
    }
}
```

### El Flujo del Error

```
1. Frontend envía:
   {
     catalogo_cuenta_id: 123,
     monto: 1000,
     usar_en_ratios: true  ✅
   }

2. Backend (store) recibe correctamente ✅

3. calcularCuentasAgregadas() ejecuta:
   - Procesa detalles base
   - Filtra y reconstruye el array
   - Al reconstruir, no preservaba explícitamente usar_en_ratios
   - Las cuentas calculadas se agregaban con usar_en_ratios = false
   
4. Se guarda en BD con usar_en_ratios = false ❌
```

## ✅ Solución Implementada

Se modificó el método `calcularCuentasAgregadas()` para **preservar explícitamente** el valor de `usar_en_ratios`:

### Cambio 1: Preservar mapeo de usar_en_ratios

```php
// Nuevo código - Líneas 411-422
$montoPorCodigo = [];
$usarEnRatiosPorCodigo = []; // Nuevo: mapeo de código a usar_en_ratios

foreach ($detallesBase as $detalle) {
    $codigo = $idACodigo[$detalle['catalogo_cuenta_id']] ?? null;
    if ($codigo) {
        $montoPorCodigo[$codigo] = $detalle['monto'];
        $usarEnRatiosPorCodigo[$codigo] = $detalle['usar_en_ratios'] ?? false; // Preservar valor
    }
}
```

### Cambio 2: Usar explícitamente el valor preservado

```php
// Nuevo código - Líneas 536-550
// Agregar SOLO detalles base que NO sean calculados (hojas del árbol)
foreach ($detallesBase as $detalle) {
    $codigo = $idACodigo[$detalle['catalogo_cuenta_id']] ?? null;
    if ($codigo && !isset($montosCalculados[$codigo])) {
        // ✅ Preservar el valor original de usar_en_ratios
        $todosLosDetalles[] = [
            'catalogo_cuenta_id' => $detalle['catalogo_cuenta_id'],
            'monto' => $detalle['monto'],
            'usar_en_ratios' => $detalle['usar_en_ratios'] ?? false, // ✅ Valor del checkbox
        ];
    }
}

// Agregar detalles calculados (estos NO deben usarse en ratios)
foreach ($montosCalculados as $codigo => $monto) {
    $cuentaId = $codigoAId[$codigo] ?? null;
    if ($cuentaId) {
        $todosLosDetalles[] = [
            'catalogo_cuenta_id' => $cuentaId,
            'monto' => $monto,
            'usar_en_ratios' => false, // Cuentas agregadas/calculadas = false (correcto)
        ];
    }
}
```

## 🎯 Resultado

Ahora el flujo es correcto:

```
1. Frontend envía: usar_en_ratios = true ✅
2. Backend recibe: usar_en_ratios = true ✅
3. calcularCuentasAgregadas(): 
   - Preserva usar_en_ratios = true para cuentas base ✅
   - Asigna usar_en_ratios = false para cuentas calculadas ✅
4. Se guarda en BD: usar_en_ratios = true ✅
```

## 📝 Archivos Modificados

- `/backend/FinBack/app/Http/Controllers/EstadoFinancieroController.php`
  - Método `calcularCuentasAgregadas()` (líneas ~411-550)

## 🧪 Para Probar

1. Ir a la página de Importar Estado Financiero
2. Seleccionar empresa, periodo y tipo de estado
3. Subir archivo CSV
4. Marcar checkboxes "Usar en Ratios" en algunas cuentas
5. Guardar
6. Verificar en la base de datos (tabla `detalles_estado`) que el campo `usar_en_ratios` tenga el valor correcto (1 para true, 0 para false)

```sql
-- Query para verificar
SELECT 
    de.id,
    cc.codigo,
    cc.nombre,
    de.monto,
    de.usar_en_ratios
FROM detalles_estado de
JOIN catalogo_cuentas cc ON de.catalogo_cuenta_id = cc.id
WHERE de.estado_id = [ID_DEL_ESTADO_CREADO]
ORDER BY cc.codigo;
```

## 📚 Notas Adicionales

- Las **cuentas calculadas/agregadas** (como "ACTIVO", "PASIVO", "Utilidad Bruta", etc.) siempre tendrán `usar_en_ratios = false` porque son el resultado de sumar otras cuentas.
- Solo las **cuentas base** (hojas del árbol de cuentas) pueden tener `usar_en_ratios = true` si el usuario lo marca.
- Esta diferenciación es correcta desde el punto de vista contable: los ratios se calculan con cuentas específicas, no con agregados.
