# Guía: cómo añadir una nueva fórmula (ratio) y crear promedios (benchmarks) por sector (rubro)

Esta guía explica, paso a paso, cómo añadir una nueva definición de ratio en el sistema y cómo sembrar (o crear) uno o varios promedios (benchmarks) por rubro/sector. Está orientada a un compañero que no conoce los detalles internos.

Resumen rápido
- Añadir una nueva fórmula -> editar/actualizar `database/seeders/RatioDefinicionesSeeder.php` o usar la UI/API para crear `RatioDefinicion` y sus componentes (tabla pivote `ratio_componentes`).
- Crear promedios de sector -> usar `database/seeders/RubroSeeder.php` (ejemplo) o llamar a la API `POST /api/rubros/{rubro}/benchmarks` para crear/actualizar benchmarks.

Contrato (qué hacen los artefactos)
- `ratios_definiciones` (model `RatioDefinicion`): almacena la definición del ratio (codigo, nombre, formula, multiplicador_resultado, categoria, etc.).
- `ratio_componentes` (pivot): relaciona `RatioDefinicion` con `ConceptoFinanciero` y define rol/orden/operacion/factor por componente.
- `benchmarks_rubro` (model `BenchmarkRubro`): almacena el promedio de un ratio para un `rubro` (par `rubro_id`, `ratio_id`) y metadatos (`valor_promedio`, `fuente`).

Antes de empezar (pre-requisitos)
- Tener la base de datos migrada y seeders básicos ejecutados (conceptos financieros, catálogo, etc.).
- Tener permisos para ejecutar `php artisan db:seed` o acceso para usar la API con credenciales.

1) Añadir una nueva fórmula (RatioDefinicion)

Opción A — Recomendado para ajustes rápidos en dev: editar el seeder
1. Abrir `database/seeders/RatioDefinicionesSeeder.php`.
2. Añadir un nuevo elemento en el arreglo `$definitions` con la siguiente estructura (ejemplo):

```php
[
  'codigo' => 'NUEVO_RATIO',
  'nombre' => 'Nombre legible del ratio',
  'formula' => 'Descripción/expresión legible',
  'sentido' => 'MAYOR_MEJOR' | 'MENOR_MEJOR',
  'categoria' => 'LIQUIDEZ' | 'EFICIENCIA' | 'RENTABILIDAD' | ...,
  'multiplicador' => 1.0,
  'is_protected' => false,
  'componentes' => [
      ['concepto_codigo' => 'ACT_COR', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
      // ... más componentes si necesitas 3/4/5 términos
  ],
],
```

Notas importantes:
- `concepto_codigo` debe existir en `conceptos_financieros` (ver `database/seeders/ConceptosFinancierosSeeder.php`). Si no existe, crea primero el `ConceptoFinanciero` (preferiblemente con su seeder o por UI).
- El seeder convierte internamente `sentido` numérico (1 -> ADD, -1 -> SUB) y agrega `operacion` y `factor` al pivot.
- El seeder es idempotente: usa `updateOrCreate` y `componentes()->sync(...)`, por lo que puedes re-ejecutarlo sin duplicados.

Después de editar el seeder:
```bash
# en dev puedes ejecutar solo ese seeder
php artisan db:seed --class=RatioDefinicionesSeeder
```

Opción B — Usar la API / UI de administración
- Si existe una UI/endpoint para crear `RatioDefinicion` (ver `RatioDefinicionController`), úsala para crear la definición y luego añadir componentes mediante la UI o endpoints que sincronicen los componentes.

2) Añadir componentes a la fórmula (más de 2-3 términos)
- Cada componente se persiste en la pivot `ratio_componentes` con campos útiles:
  - `rol`: NUMERADOR|DENOMINADOR|OPERANDO
  - `orden`: indica el orden de aplicación
  - `operacion`: ADD|SUB (se aplica cuando combinamos términos)
  - `factor`: multiplicador numérico (por ejemplo, si necesitas multiplicar el componente por 100)
  - `requiere_promedio`: booleano para indicar si el componente necesita promedio (p. ej. inventario promedio)

3) Crear promedios (benchmarks) por rubro (sector)
Hay dos formas: mediante seeders (batch) o por API (manual/automático).

A) Via seeder (batch / reproducible)
- Edita `database/seeders/RubroSeeder.php` o crea un nuevo seeder específico (recomendado: `BenchmarksSeeder`) para sembrar valores por rubro.
- Ejemplo (simplificado, ya hay uno en `RubroSeeder`):

```php
$ratios = DB::table('ratios_definiciones')->pluck('id', 'codigo')->toArray();
$defaults = [ 'RAZ_CORR'=>1.0, 'PRUEBA_ACIDA'=>0.9, /* etc */ ];
foreach ($rubros as $rubro) {
  foreach ($ratios as $codigo => $id) {
    $valor = $defaults[$codigo] ?? 0.0;
    DB::table('benchmarks_rubro')->updateOrInsert(
      ['rubro_id' => $rubroId, 'ratio_id' => $id],
      ['valor_promedio' => $valor, 'fuente' => 'seeder', 'created_at'=>now(), 'updated_at'=>now()]
    );
  }
}
```

Puntos clave:
- El esquema actual de `benchmarks_rubro` garantiza unicidad por par (`rubro_id`,`ratio_id`) — el seeder debe usar `updateOrInsert` para no duplicar.
- Si necesitas múltiples promedios por el mismo `rubro+ratio` (p. ej. distintas fuentes), hay que distinguir por una columna adicional (`fuente`, `tag` o reintroducir `anio`) y actualizar la clave única en la BD y el controlador.

Ejecutar el seeder:
```bash
php artisan db:seed --class=RubroSeeder
# o si creaste BenchmarksSeeder:
php artisan db:seed --class=BenchmarksSeeder
```

B) Via API (manual o integración)
- Endpoint para crear/actualizar un benchmark:
  POST /api/rubros/{rubro}/benchmarks
  Body: { "ratio_id": <id>, "valor_promedio": 1.23, "fuente": "estudio2024" }
- El controlador `BenchmarkRubroController::store` hace `updateOrCreate(['rubro_id','ratio_id'], ...)` por defecto.

4) Si quieres múltiples benchmarks por rubro+ratio
- Dos caminos seguros:
  1) Añadir `fuente` como parte de la clave única y modificar migraciones/índices y controlador para usar `['rubro_id','ratio_id','fuente']` en `updateOrCreate`.
  2) Mantener la única actual y crear otra tabla `benchmarks_rubro_versions` para históricos/variantes.

Recomendación: opción (1) es práctica si sólo necesitas distinguir por origen.

5) Verificaciones y pruebas rápidas
- Verifica que el `RatioDefinicionesSeeder` no use códigos de `ConceptoFinanciero` inexistentes. Si aparece la advertencia en consola, crea primero el `ConceptoFinanciero`.
- Después de sembrar, valida via SQL:

```sql
SELECT r.codigo, b.rubro_id, b.valor_promedio
FROM benchmarks_rubro b
JOIN ratios_definiciones r ON r.id = b.ratio_id
WHERE b.rubro_id = <id_del_rubro>;
```

6) Rollback / revert
- Los seeders no tienen `down()` — si necesitas revertir los datos, crea un seeder de limpieza o usa consultas manuales para eliminar por `fuente` o por `codigo` de ratio.
- Para cambios en índices/columnas, crear migraciones reversibles.

7) Checklist antes de enviar PR
- [ ] Agregaste el `codigo` y `componentes` correctos al seeder y confirmaste que los `concepto_codigo` existen.
- [ ] Elegiste la estrategia para los benchmarks (un solo promedio por par o múltiples por `fuente`).
- [ ] Ejecutaste `php artisan db:seed --class=RatioDefinicionesSeeder` y `php artisan db:seed --class=RubroSeeder` en dev y no hay errores.
- [ ] Añadiste notas en `docs/developer/adding_ratio_and_benchmarks.md` (este archivo) mencionando cualquier cambio en la DB (índices/columnas).

8) Ejemplos de comandos útiles
```bash
# Sembrar sólo definiciones de ratios
php artisan db:seed --class=RatioDefinicionesSeeder
# Sembrar rubros y benchmarks
php artisan db:seed --class=RubroSeeder
# Sembrar un seeder nuevo
php artisan db:seed --class=BenchmarksSeeder
```

9) Preguntas frecuentes
- Q: ¿Puedo usar `anio` para versionar benchmarks?  
  A: Actualmente la migración reciente eliminó `anio` para usar un único benchmark por par; si necesitas versionado, re-introduce `anio` o añade `fuente`.

- Q: ¿Qué hago si al correr el seeder obtengo errores SQL sobre índices?  
  A: Probablemente la migración que altera índices no corrió en el orden esperado. Verifica que las migraciones están aplicadas; si haces `migrate:fresh --seed`, asegúrate de que no haya dependencias rotas y revisa los logs.

10) Contacto y seguimiento
- Si quieres, puedo:
  - añadir un `BenchmarksSeeder` de ejemplo que genera promedios por rubro con valores realistas por sector, o
  - modificar el esquema para permitir `fuente` como parte de la unicidad.

---
Guía creada automáticamente: si quieres ejemplos concretos (snippet completo de seeder para un nuevo ratio con 5 componentes, o un `BenchmarksSeeder` listo para usar), dime el `codigo` y los `concepto_codigo` que quieres usar y lo genero y pruebo en dev.
