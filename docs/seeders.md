# Seeders - guía rápida

Este documento resume qué seeders están activos, cuáles fueron movidos a *deprecated*, y cómo trabajar con ellos tras las migraciones que introdujeron la nueva semántica de ratios (pivot `operacion` y `factor`, y `multiplicador_*` en `ratios_definiciones`).

## Seeders activos (recomendados)
- `RolSeeder` / `PermisoSeeder` / `UserSeeder` — seguridad.
- `RubroSeeder` — rubros maestros.
- `ConceptosFinancierosSeeder` — definiciones de conceptos (usar siempre `codigo` para referenciar).
- `EmpresaSeeder` — empresas demo.
- `RatioDefinicionesSeeder` — DEFINICIÓN CANÓNICA de ratios + sincroniza sus componentes.
  - Importante: este seeder ahora sincroniza los componentes a través de `concepto.codigo` y persiste en el pivot `operacion` y `factor`.
- `PeriodoSeeder`, `CatalogoYMapeoSeeder`, `VentaMensualSeeder`, `EstadosYDetallesSeeder` — datos dependientes y de prueba.

> Orden de ejecución recomendado: primero maestros (conceptos, rubros), luego definiciones (ratios), luego mapeos y datos transaccionales.

## Seeders movidos a deprecated
- `RatioDefinicionSeeder` (singular) — movido a `deprecated/` y reemplazado por `RatioDefinicionesSeeder` (plural) que unifica lógica y ahora está actualizada para la nueva semántica.
- `RatioComponentesSeeder` — su funcionalidad se consolidó dentro de `RatioDefinicionesSeeder`. Por tanto se marcó como deprecated.
- `Consolidar*` (si existiera algún seeder de consolidación singular) — si lo tenías, se marcó como deprecated; conservar histórico si es necesario.

> Los seeders deprecated se conservaron para historial pero ya no son invocados desde `DatabaseSeeder`.

## Cambios importantes tras migraciones
- Las definiciones de componentes ahora usan en el pivot:
  - `operacion`: textual: `ADD` | `SUB` | `MUL` | `DIV`.
  - `factor`: decimal (por defecto 1.0).
- Las definiciones de ratio ahora tienen columnas de multiplicador por bloque/resultado: `multiplicador_numerador`, `multiplicador_denominador`, `multiplicador_resultado`. El seeder actualiza `multiplicador_resultado` con el valor antiguo `multiplicador`.
- Los seeders se diseñaron para ser idempotentes: usan `updateOrCreate` y `componentes()->sync()` buscando conceptos por `codigo` (no por id numérico duro).

## Cómo añadir o actualizar un ratio en seeders
- Añadir la definición en `RatioDefinicionesSeeder::$definitions` usando `concepto_codigo` (el `codigo` del `ConceptoFinanciero`).
- En cada componente usar `rol` (`NUMERADOR`|`DENOMINADOR`|`OPERANDO`), `orden`, `requiere_promedio` (bool), y opcional `factor` (float). Si migras desde `sentido` numérico, el seeder mapea 1=>`ADD`, -1=>`SUB`.

## Ejecutar seeders (recomendado para dev)
1. Hacer backup de la BD si hay datos importantes.
2. Ejecutar migraciones:

```bash
php artisan migrate
```

3. Sembrar sólo los seeders controlados desde `DatabaseSeeder`:

```bash
php artisan db:seed
```

Si quieres resembrar sólo ratios (sin tocar otros datos):

```bash
php artisan db:seed --class=Database\\Seeders\\RatioDefinicionesSeeder
```

## Notas y buenas prácticas
- Evita usar ids numéricas en seeders; usa siempre `codigo`/`slug` para enlazar entre entidades.
- Mantén los seeders idempotentes (`firstOrCreate`, `updateOrCreate`, `sync`).
- Antes de eliminar columnas antiguas (`sentido`, `multiplicador` antiguo), confirma que todas las instancias y seeders ya usan la nueva semántica y que no hay código legacy leyendo las columnas viejas.

---
Documentado: cambios realizados en Nov 2025 para soportar `operacion`/`factor` y `multiplicador_*`.
