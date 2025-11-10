## Guía: Crear un Rubro y sus Benchmarks promedio desde el mismo formulario

Propósito
- Explicar qué debe implementar el frontend para que, al crear un rubro (sector), se puedan definir y persistir en la misma interacción los valores promedio (benchmarks) por ratio.

Audiencia
- Desarrolladores frontend del equipo (Vue/React con Axios o fetch).

Resumen rápido
- Flujo recomendado: 1) El formulario permite capturar datos del rubro + una sección por cada ratio donde se puede ingresar `valor_promedio` y `fuente`. 2) Al enviar, el frontend crea primero el rubro (API) y, con el `rubro_id` devuelto, crea/actualiza los benchmarks por ratio usando la API de benchmarks. 3) Manejo de errores: rollback parcial o mostrar errores por bloque.

Asunciones
- Endpoints backend asumidos (confirma con backend si difieren):
  - GET /api/ratios/definiciones  -> lista de definiciones de ratio (id, codigo, nombre, multiplicador_resultado, componentes...)
  - POST /api/rubros               -> crea un rubro y devuelve el objeto con `id`.
  - POST /api/rubros/{rubro}/benchmarks -> crea/actualiza benchmark para un ratio (payload: { ratio_id, valor_promedio, fuente }).
  - Alternativa posible: si el backend acepta nested payload (crear rubro con array `benchmarks`), usar POST /api/rubros con body que incluya `benchmarks`.

Contrato de la API (payloads)
- Crear rubro (ejemplo):

  {
    "nombre": "Comercio al por menor",
    "codigo": "COM_RET",
    "descripcion": "Sector X",
    "otros_campos": "..."
  }

- Crear/actualizar benchmark (por ratio):

  POST /api/rubros/:rubroId/benchmarks
  Body: { "ratio_id": 12, "valor_promedio": 2.34, "fuente": "INFORME_SECTORIAL" }

  Respuesta esperada: 200/201 con el objeto `benchmark` creado/actualizado.

UX y diseño del formulario
- Layout sugerido:
  1) Bloque principal: campos de Rubro (nombre, código, descripción, agrupaciones).
  2) Bloque secundario (collapsible): "Benchmarks promedio por ratio".
     - Para cada ratio mostrar: etiqueta (nombre), input numérico `valor_promedio`, input texto `fuente` y un toggle para `usar_valor_default`.
     - Permitir "auto-fill" con valores por defecto obtenidos desde `GET /api/ratios/definiciones` o desde un endpoint de `benchmarks/defaults` si existe.

Validaciones en frontend
- Campos Rubro: validar required/longitud/regex según reglas del backend.
- Benchmarks:
  - `valor_promedio`: número decimal, puede permitir 0 o null (depende de la política). Normalizar separador decimal a punto antes de enviar.
  - `fuente`: cadena opcional hasta X caracteres (ej. 255).
  - `ratio_id`: debe existir y ser entero.

Flujo de envío recomendado (robusto)
1) Validar localmente el formulario (rubros + benchmarks). Mostrar errores al usuario.
2) Desactivar el botón de submit y mostrar spinner.
3) Llamar a POST /api/rubros con los datos del rubro.
   - Si falla: mostrar error global y reactivar el formulario.
4) Si la creación del rubro succeed y devuelve `rubro.id`:
   - Preparar un array de requests para crear benchmarks solo para los ratios que el usuario completó (o para todos si desea semilla explícita).
   - Lanzar las requests de benchmarks en paralelo con Promise.all (o por lotes si son muchas) a POST /api/rubros/:rubroId/benchmarks.
   - Si todas succeed -> mostrar success y redirigir/actualizar la vista.
   - Si alguna falla:
     - Opción A: mostrar qué benchmarks fallaron y permitir reintentar individualmente (recomendado para UX).
     - Opción B: intentar rollback borrando el rubro (DELETE /api/rubros/:rubroId) si la integridad requiere crear todos o ninguno. Nota: el rollback puede tener efectos secundarios; coordinar con backend.

Ejemplo (Axios) — creación secuencial + benchmarks en paralelo

```javascript
// crear rubro
const rubroPayload = { nombre, codigo, descripcion };
const rubroRes = await axios.post('/api/rubros', rubroPayload);
const rubroId = rubroRes.data.id;

// preparar requests de benchmarks
const benchmarkRequests = benchmarksList
  .filter(b => b.valor_promedio !== null && b.valor_promedio !== '')
  .map(b => axios.post(`/api/rubros/${rubroId}/benchmarks`, {
    ratio_id: b.ratio_id,
    valor_promedio: Number(String(b.valor_promedio).replace(',', '.')),
    fuente: b.fuente || null
  }));

// ejecutar en paralelo
try {
  const results = await Promise.all(benchmarkRequests);
  // todos creados correctamente
  // mostrar mensaje y redirigir
} catch (err) {
  // manejar fallos: mostrar errores por ratio y permitir reintentos
}
```

Bulk endpoint alternativa (si backend lo soporta)
- Si el backend acepta crear el rubro con `benchmarks` embebidos, enviar todo en un solo POST para simplificar transacciones:

```json
POST /api/rubros
{
  "nombre": "Comercio",
  "codigo": "COM_RET",
  "benchmarks": [ { "ratio_id": 1, "valor_promedio": 1.23, "fuente": "X" }, ... ]
}
```

Optimistic UI y feedback
- Mostrar indicadores de proceso por sección (Rubros vs Benchmarks).
- En caso de fallo en benchmarks, no eliminar inmediatamente el rubro; mostrar estado "parcialmente creado" con acciones para completar/reintentar.

Errores y rollback
- Preferir reintento por benchmark en vez de borrar el rubro (mejor UX). Si el equipo decide transaccionalidad completa, coordinar con backend para que soporte creación atómica.

Pruebas y QA
- Testear los siguientes escenarios:
  1) Crear rubro sin benchmarks -> OK.
  2) Crear rubro con 3 benchmarks válidos -> OK.
  3) Crear rubro con 1 benchmark inválido (p. ej. texto en valor) -> mostrar validación y evitar POST.
  4) Crear rubro; la creación del rubro succeed pero uno de los benchmarks falla -> probar la UI de reintento y el manejo de errores.

Notas de integración (técnicas)
- Token / auth: incluir cabeceras Authorization (Bearer) si la API lo requiere.
- Manejo de locales: normalizar separador decimal antes de enviar al backend.
- Si la API devuelve `validation errors` (422), mapearlos a los inputs correspondientes.

Checklist de aceptación (QA)
- [ ] Al crear rubro desde el formulario con valores en Benchmarks, el rubro se crea y los benchmarks se persisten.
- [ ] Errores de validación son mostrados correctamente por campo.
- [ ] Reintento de benchmarks fallidos funciona sin necesidad de recrear el rubro.
- [ ] Opcional: si se requiere atomicidad, la API debe soportar creación nested o transaccional.

Preguntas para backend (confirmar antes de implementar)
1) ¿Existe endpoint bulk para crear benchmarks junto con el rubro? (preferible)
2) ¿Cuál es la ruta exacta para crear/actualizar benchmarks? (el ejemplo usa `/api/rubros/:id/benchmarks`).
3) ¿Cuál es la política de rollback si algunos benchmarks fallan? ¿Debemos borrar rubro creado o solo informar?

Resumen
- La implementación frontend recomendada es: fetch de definiciones de ratios, render de inputs por ratio dentro del formulario de creación de rubro, crear rubro primero, luego persistir benchmarks por ratio en paralelo. Manejar errores por benchmark y ofrecer reintentos individuales para mejor UX. Coordinar con backend sobre endpoints bulk y política de transacción.

Fin de la guía.
