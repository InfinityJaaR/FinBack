# Documentación: RatioDefinicionController

Última actualización: 2025-11-01

Resumen
-------
Este documento explica la lógica del controlador `RatioDefinicionController` (namespace `App\Http\Controllers\Api`) que gestiona las definiciones de ratios financieros.

El controlador realiza las operaciones CRUD sobre `RatioDefinicion` y coordina la persistencia y sincronización de sus componentes (tabla pivote `ratio_componentes`). Además utiliza requests validados (`StoreRatioDefinicionRequest` y `UpdateRatioDefinicionRequest`) y aplica transacciones para asegurar consistencia.

Artefactos relacionados
-----------------------
- Modelo: `App\Models\RatioDefinicion`
- Request: `App\Http\Requests\StoreRatioDefinicionRequest`
- Request: `App\Http\Requests\UpdateRatioDefinicionRequest`
- Servicio: `App\Services\RatioCalculator` (lógica de cálculo de ratios)
- Modelo pivote: relación `componentes()` en `RatioDefinicion` con datos en la tabla pivote (campos en pivot: `rol`, `orden`, `requiere_promedio`)

Objetivos del controlador
-------------------------
- Proveer endpoints RESTful para administrar las definiciones de ratios.
- Validar la entrada usando FormRequests específicos (Store/Update).
- Persistir la definición principal y sincronizar los componentes a la tabla pivote en una transacción.
- Devolver respuestas JSON consistentes con códigos HTTP apropiados.

Resumen de endpoints y comportamiento
-------------------------------------
Asumiendo registro en rutas tipo resource, los métodos y su comportamiento son:

- index() — GET /ratios-definiciones
  - Devuelve una lista paginada de definiciones: `RatioDefinicion::with('componentes.concepto')->paginate(10)`.
  - Respuesta: 200 con `{ success: true, data: <paginator> }` o 500 si ocurre excepción.

- create() — GET /ratios-definiciones/create
  - Devuelve datos auxiliares para el formulario (ej. `ConceptoFinanciero::select('id','nombre_concepto')->get()`).
  - Respuesta: 200 con `{ success: true, conceptos_disponibles: [...] }`.

- store(StoreRatioDefinicionRequest) — POST /ratios-definiciones
  - Valida entrada con `StoreRatioDefinicionRequest` (ver sección "Validaciones").
  - Dentro de una transacción:
    1. Crea el `RatioDefinicion` con `create($request->except(['componentes']))`.
    2. Construye un array para sync() sobre la relación `componentes()` donde la clave es `concepto_id` y el valor es un array con pivot attributes (`rol`, `orden`, y opcionalmente `requiere_promedio`).
    3. Llama a `$ratio->componentes()->sync($componentesData)` para persistir relaciones en la tabla pivote.
  - Respuestas:
    - 201 OK `{ success: true, message: '...', data: <ratio_con_componentes> }` en éxito.
    - 500 con mensaje en caso de excepción y rollback.

- show(RatioDefinicion $ratioDefinicion) — GET /ratios-definiciones/{id}
  - Carga relaciones: `componentes.concepto`, `benchmarks`.
  - Respuesta: 200 con `{ success: true, data: <ratio> }`.

- edit(RatioDefinicion $ratioDefinicion) — GET /ratios-definiciones/{id}/edit
  - Devuelve la definición y los conceptos disponibles para edición.
  - Respuesta: 200 con `{ success: true, ratio_definicion: <ratio>, conceptos_disponibles: [...] }`.

- update(UpdateRatioDefinicionRequest, RatioDefinicion $ratioDefinicion) — PUT /ratios-definiciones/{id}
  - Valida entrada con `UpdateRatioDefinicionRequest`.
  - Dentro de una transacción:
    1. Actualiza la definición con `$ratioDefinicion->update($request->except(['componentes']))`.
    2. Calcula `$componentesData` y ejecuta `$ratioDefinicion->componentes()->sync($componentesData)` para reflejar los cambios (crear/actualizar/eliminar relaciones en la pivote).
  - Respuestas:
    - 200 con `{ success: true, message: '...', data: <ratio_actualizado> }`.
    - 500 y rollback ante excepción.

- destroy(RatioDefinicion $ratioDefinicion) — DELETE /ratios-definiciones/{id}
  - Intenta eliminar la definición. Si la DB impone restricciones (ej: valores ya calculados referencian al ratio), devuelve 500 con mensaje explicativo.
  - Respuesta: 200 en éxito.

Validaciones (StoreRatioDefinicionRequest y UpdateRatioDefinicionRequest)
------------------------------------------------------------------------
Ambos FormRequests definen reglas importantes y mensajes personalizados. Resumen:

Campos del objeto principal `ratios_definiciones`:
- codigo: string required max:30
  - Store: unique:ratios_definiciones,codigo
  - Update: unique:ratios_definiciones,codigo -> ignore(currentId)
- nombre: string required max:120
- formula: string required
- sentido: string required in ["MAYOR_MEJOR","MENOR_MEJOR","CERCANO_A_1"]

Campos del array `componentes` (requerido, array mínimo 2 elementos):
- componentes.*.concepto_id: required, integer, exists:conceptos_financieros,id
- componentes.*.rol: required, string, in ["NUMERADOR","DENOMINADOR","OPERANDO"]
- componentes.*.orden: required, integer, min:1
- componentes.*.requiere_promedio: required, boolean

Mensajes claves de validación (resumen):
- `codigo.unique` -> mensaje amigable si hay duplicado
- `componentes.*.requiere_promedio.required` / `.boolean` -> mensajes específicos

Nota: `UpdateRatioDefinicionRequest` obtiene el id del modelo por Route Model Binding para ignorarlo en la regla unique.

Estructura esperada del payload para `store` / `update` (ejemplo)
-----------------------------------------------------------------
JSON de ejemplo para crear una definición con 2 componentes:

{
  "codigo": "ROA",
  "nombre": "Return on Assets",
  "formula": "(Utilidad Neta) / Activos Totales",
  "sentido": "MAYOR_MEJOR",
  "componentes": [
    { "concepto_id": 1, "rol": "NUMERADOR", "orden": 1, "requiere_promedio": false },
    { "concepto_id": 2, "rol": "DENOMINADOR", "orden": 1, "requiere_promedio": true }
  ]
}

Nota importante: `componentes` se transforma internamente a la forma que acepta `sync()`:
- Se construye un array asociativo: `componentesData[concepto_id] = ['rol' => ..., 'orden' => ..., 'requiere_promedio' => ...]`.

Cómo se sincronizan los componentes (pivote)
------------------------------------------
- El controlador mapea cada elemento de `componentes` usando `mapWithKeys` para transformar la lista en la estructura requerida por `sync()`:

  componente (input): { concepto_id: 5, rol: 'NUMERADOR', orden: 1, requiere_promedio: true }
  -> mapea a: [ 5 => ['rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => true] ]

- Luego `->componentes()->sync($componentesData)` hace:
  - Inserta nuevas asociaciones
  - Actualiza los datos en pivot para conceptos ya asociados
  - Elimina associations no incluidas en el array (por lo tanto `update` reemplaza la lista completa de componentes)

Transacciones y consistencia
---------------------------
- `store()` y `update()` envuelven las operaciones en `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` para asegurar que la definición y sus componentes se guarden o se desechen en conjunto ante errores.
- Esto evita estado inconsistente donde la definición exista sin componentes o viceversa.

Interacción con `RatioCalculator`
----------------------------------
El `RatioCalculator` (servicio) es responsable de calcular el valor numérico de una definición de ratio para una `Empresa` y `Periodo` dados. Puntos clave:

1. Componentes y Pivot
- `RatioDefinicion->componentes()` incluye los campos pivot `rol`, `orden` y `requiere_promedio`. `RatioCalculator` recorre estos componentes y toma decisiones según `rol` y `requiere_promedio`.

2. Roles
- NUMERADOR: contribuye al numerador (suma)
- OPERANDO: contribuye al numerador pero se resta (p. ej. Inventario en Prueba Ácida)
- DENOMINADOR: valor del denominador (se asume un único denominador principal)

3. Lógica de promedio (`requiere_promedio`)
- Si `requiere_promedio` es false: usar el valor de cierre del periodo actual.
- Si true: intenta obtener periodo anterior (por `anio - 1`). Si no existe, emplea el valor actual; si existe, calcula promedio entre cierre actual y cierre anterior: (actual + anterior) / 2.0.

4. Obtención de valores contables
- `queryValorEnPeriodo()` suma montos de `detalles_estado` filtrando por `estados` de la `empresa` y `periodo` y utilizando el mapeo `cuenta_concepto` para seleccionar las cuentas que pertenecen al `concepto_financiero`.
- Resultado: `float` con la suma total (casting explícito a float).

5. Errores y excepciones
- Si no hay componentes -> Exception: "El ratio 'X' no tiene componentes definidos.".
- Si el numerador no puede construirse -> Exception.
- Si denominador es 0.0 o null -> Exception: "División por cero...".

Recomendaciones y consideraciones de diseño
-------------------------------------------
- Validar que la tabla pivote (`ratio_componentes`) tenga columnas para `rol`, `orden` y `requiere_promedio` y que `requiere_promedio` sea boolean.
- Asegurar índices y claves foráneas para `concepto_id` y `ratio_definicion_id`.
- Al usar `sync()`, el `update()` reemplaza totalmente la colección de componentes: el cliente debe enviar la lista completa de componentes deseada.
- Dado que `requiere_promedio` impacta el cálculo, usar constantes o un Enum para los valores de `rol` y `sentido` en los modelos para evitar strings mágicos.

Casos de prueba sugeridos (unit/integration)
--------------------------------------------
1. Crear definición mínima (2 componentes) -> assert 201 y que pivot contenga ambos registros con atributos esperados.
2. Actualizar definición cambiando roles/orden -> assert 200 y que `componentes()` refleje cambios y que no existan registros obsoletos en la pivote.
3. Calcular ratio con `RatioCalculator` con:
   - denominador = 0 -> debe lanzar excepción esperada
   - componente con `requiere_promedio = true` y sin periodo anterior -> usa valor actual
   - componente con `requiere_promedio = true` y periodo anterior existente -> promedia correctamente
4. Integración: crear RatioDefinicion + poblar `detalles_estado` y `cuenta_concepto` -> calcular y comparar resultado esperado

Ejemplos de peticiones y respuestas
----------------------------------
- Store (request): ver JSON de ejemplo en la sección "Estructura esperada..."
- Store (response success): 201
  {
    "success": true,
    "message": "Definición de Ratio y componentes creados exitosamente.",
    "data": { ... ratio con componentes ... }
  }

Errores típicos y sus orígenes
------------------------------
- 422 ValidationError: cuerpo mal formado o campos faltantes (`componentes` mínimo 2, `concepto_id` inexistente, etc.).
- 500 en store/update: excepción durante persistencia (posible error SQL, falta de columna en pivote, clave foránea rota). Revisar logs para el stack trace.
- 500 en destroy si existen dependencias en la BD (por ej. `ratios_valores` referenciando la definición)

Checklist rápido para depuración
--------------------------------
- ¿La tabla pivote contiene las columnas pivot necesarias? (rol, orden, requiere_promedio)
- ¿Las reglas `exists:conceptos_financieros,id` son correctas? -> revisar tabla `conceptos_financieros`.
- ¿Se están enviando todos los componentes en update? (sync elimina los no enviados)
- ¿Existen registros en `detalles_estado` y `cuenta_concepto` para probar cálculo?

Preguntas del equipo
--------------------
| Pregunta del Equipo | Estado | Solución / Comentario |
|---|---:|---|
| ¿La tabla pivote contiene las columnas pivot necesarias? (rol, orden, requiere_promedio) | RESUELTA ✅ | Se creó y se ejecutó la migración `add_requiere_promedio_to_ratio_componentes_table`. La base de datos está actualizada, y los modelos y Requests ya incluyen esta columna clave. |
| ¿Las reglas `exists:conceptos_financieros,id` son correctas? | RESUELTA ✅ | Sí, son correctas. Además, el error de *Undefined array key* se resolvió corrigiendo el orden de los Seeders, lo que garantiza que los `conceptos_financieros` existan en la BD antes de que el `RatioDefinicionSeeder` los use, cumpliendo así con la regla `exists`. |
| ¿Se están enviando todos los componentes en update? (sync elimina los no enviados) | RESUELTA ✅ | Sí, se confirmó. El equipo debe enviar la lista completa y final de componentes, ya que el `RatioDefinicionController` utiliza `sync()`, que reemplaza totalmente la colección de relaciones M:N. |
| ¿Existen registros en `detalles_estado` y `cuenta_concepto` para probar cálculo? | PLANIFICADA ⚙️ | No, no existen aún. Plan: desarrollar un Seeder de Datos de Prueba (por ejemplo `DetallesEstadoSeeder`) para poblar estas tablas y permitir pruebas unitarias e integrales del `RatioCalculator`. |

Posibles mejoras futuras
-----------------------
- Separar la transformación de `componentes` a una clase/formal transformer para facilitar pruebas unitarias.
- Implementar DTOs o FormObjects para encapsular la lógica del pivote antes de llamar a `sync()`.
- Añadir prueba automatizada (Feature Test) que cubra flujo completo store -> calcular ratio -> destroy.

Contacto rápido
---------------
Si quieres, puedo:
- Generar ejemplos de tests (PHPUnit) para `store()` y `update()` y para `RatioCalculator`.
- Crear un pequeño Seeder/Tinker snippet que cree un `RatioDefinicion` y datos contables para probar cálculo.

---
Archivo generado automáticamente: `docs/RatioDefinicionController.md`
