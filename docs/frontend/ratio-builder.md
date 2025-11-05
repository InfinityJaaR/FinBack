# Guía para la vista frontend: Crear / editar una Definición de Ratio

Última actualización: 2025-11-05

Esta guía explica cómo debe comportarse la vista (UI) para construir un nuevo ratio y consumir la lógica actual del backend (`RatioDefinicionController` y endpoints relacionados). Incluye el contrato JSON, validaciones, UX recomendadas, ejemplos de Axios y notas de integración.

## Objetivo

Permitir que un usuario con permisos (Administrador) cree o edite una definición de ratio financiera en la UI. El formulario debe producir un payload que el backend acepte sin errores y que permita:
- persistir la definición y sus componentes (tabla pivote `ratio_componentes`),
- realizar una vista previa / dry-run del cálculo con datos de ejemplo,
- dar señales visuales cuando haya mappings faltantes entre conceptos y cuentas (catalogo).

## Endpoints principales

- GET /api/ratios/categorias
  - Devuelve categorías permitidas (ej.: LIQUIDEZ, RENTABILIDAD...).

- GET /api/conceptos (o endpoint equivalente)
  - Devuelve lista de `ConceptoFinanciero` para popular selects. Usar `id`, `codigo` y `nombre_concepto`.

- POST /api/ratios/definiciones
  - Crea una definición. Devuelve 201 con la definición creada y sus componentes.

- PUT /api/ratios/definiciones/{id}
  - Actualiza una definición y reemplaza completamente los componentes (backend hace `sync`).

- GET /api/ratios/definiciones/{id}/calculate?empresa={empresaId}&periodo={periodoId}
  - (Preview/dry-run) Devuelve el resultado del cálculo para la empresa/periodo seleccionado, además de un breakdown por componente. Útil para vista previa.

> Nota: adaptar URLs si tu proyecto frontend usa rutas distintas (prefijo `/api`).

## Diseño de la vista (layout sugerido)

1. Encabezado
   - Título: "Crear definición de ratio" o "Editar definición".
   - Breadcrumbs y botón "Cancelar".

2. Formulario principal (dos columnas responsivas)
   - Columna izquierda: campos generales
     - Código (input) — obligatorio
     - Nombre (input) — obligatorio
     - Fórmula (textarea, descriptivo) — obligatorio
     - Sentido (select) — `MAYOR_MEJOR` | `MENOR_MEJOR` | `CERCANO_A_1`
     - Categoría (select) — cargar desde `/api/ratios/categorias`
     - Multiplicadores (agrupable, opcional)
       - multiplicador_numerador (number)
       - multiplicador_denominador (number)
       - multiplicador_resultado (number)
     - is_protected (switch) — si está activo, bloquear edición para usuarios no-admin en edición posterior

   - Columna derecha: panel de componentes (tabla editable)
     - Lista ordenada de componentes (numerador, denominador, operando)
     - Cada fila representa un `componentes[]` y contiene:
       - Selector de concepto (buscar por nombre o código) — usa lista de `ConceptoFinanciero` obtenida por GET.
       - Rol (select): `NUMERADOR` | `DENOMINADOR` | `OPERANDO`
       - Orden (number) — posición en la fórmula (1..N)
       - Requiere promedio (checkbox)
       - Operación (select): `ADD` | `SUB` | `MUL` | `DIV` (obligatorio)
       - Factor (number) — por defecto 1.0
       - Botón eliminar (fila)

3. Footer del formulario
   - Botones: "Vista previa" (dry-run), "Guardar" (crear/actualizar), "Cancelar".
   - Mensajes de estado (errores globales / validaciones).

## Reglas de validación en cliente (mirar FormRequest en backend también)

- Validaciones principales (antes de enviar):
  - `codigo`: string no vacío, max 30. (El servidor valida unicidad).
  - `nombre`: string no vacío, max 120.
  - `formula`: string no vacío.
  - `sentido`: uno de `MAYOR_MEJOR`,`MENOR_MEJOR`,`CERCANO_A_1`.
  - `categoria`: uno de los valores devueltos por `/api/ratios/categorias`.
  - `componentes` debe ser array y tener al menos 2 entradas (al menos un NUMERADOR y un DENOMINADOR).
  - Cada `componente`:
    - `concepto_id`: integer y existente en catálogo de conceptos (prevalidar localmente si ya cargaste conceptos).
    - `rol`: string en [`NUMERADOR`,`DENOMINADOR`,`OPERANDO`].
    - `orden`: integer >= 1.
    - `requiere_promedio`: boolean.
    - `operacion`: string en [`ADD`,`SUB`,`MUL`,`DIV`].
    - `factor`: number (opcional, default 1.0).

> Importante: al actualizar la definición, el backend espera recibir la lista completa de componentes (porque usa `sync()`); siempre enviar `componentes` completos.

## Flujo de UX y validaciones interactivas

- Pre-carga: cuando la vista se monta, cargar `conceptos` y `categorias` en background.
- Al añadir una fila de componente, el foco debe ir inmediatamente al selector de `concepto`.
- Reordenamiento: permitir drag-and-drop de filas para ajustar `orden`. Al guardar, mapear la posición visual a `orden` numérico (1..N).
- Validación inline: marcar errores por fila (p. ej. concepto faltante, operación no seleccionada).
- Advertencias de mapping: cerca del selector de concepto mostrar un badge con cuántas cuentas del catálogo están mapeadas a ese concepto (backend puede proveer endpoint o el frontend puede pedir `/api/catalogo/mapeos?concepto_id=`). Si el conteo es 0, mostrar una alerta amarilla: "No hay cuentas mapeadas a este concepto — el cálculo omitirá este componente".

## Botón Vista previa / Dry-run (recomendado)

- Objetivo: permitir al usuario validar que la definición produce el cálculo esperado antes de `guardar`.
- Implementación:
  1. En la UI, después de validación local, llamar al endpoint:

     GET /api/ratios/definiciones/{id}/calculate?empresa={empresaId}&periodo={periodoId}

     - Si el ratio aún no existe (creación), el backend puede ofrecer un endpoint POST /api/ratios/definiciones/dry-run que acepte un payload como el de creación y devuelva el cálculo sin persistir.

  2. Mostrar resultado numérico y un breakdown:
     - Numerador (valor + desglose por componente y operación aplicada)
     - Denominador (valor + desglose)
     - Resultado final (con multiplicador_resultado aplicado y formato)
     - Mostrar errores (p. ej. división por cero) de forma clara.

  3. Si la respuesta del backend incluye warnings por componentes sin mapeo, mostrarlas en el panel lateral.

## Payload (ejemplo de creación)

Ejemplo JSON que el frontend debe enviar en POST /api/ratios/definiciones:

```json
{
  "codigo": "PRUEBA_ACIDA",
  "nombre": "Prueba Ácida",
  "formula": "(Activo Corriente - Inventario) / Pasivo Corriente",
  "sentido": "MAYOR_MEJOR",
  "categoria": "LIQUIDEZ",
  "multiplicador_resultado": 1.0,
  "multiplicador_numerador": null,
  "multiplicador_denominador": null,
  "is_protected": true,
  "componentes": [
    { "concepto_id": 10, "rol": "NUMERADOR", "orden": 1, "requiere_promedio": false, "operacion": "ADD", "factor": 1.0 },
    { "concepto_id": 4, "rol": "OPERANDO", "orden": 2, "requiere_promedio": false, "operacion": "SUB", "factor": 1.0 },
    { "concepto_id": 11, "rol": "DENOMINADOR", "orden": 1, "requiere_promedio": false, "operacion": "ADD", "factor": 1.0 }
  ]
}
```

> Nota: el `concepto_id` debe corresponder al id real del `ConceptoFinanciero` obtenido desde `/api/conceptos`.

## Ejemplo Axios (crear)

```js
import axios from 'axios';

const token = localStorage.getItem('token');

const payload = { /* objetp como arriba */ };

axios.post('/api/ratios/definiciones', payload, { headers: { Authorization: `Bearer ${token}` } })
  .then(resp => {
    // mostrar éxito y redirigir a detalle
  })
  .catch(err => {
    if (err.response?.status === 422) {
      // mostrar errores por campo (err.response.data.errors)
    } else if (err.response?.status === 403) {
      // mostrar permiso insuficiente
    } else {
      // error genérico
    }
  });
```

## Mensajes y manejo de errores

- Validación 422: el servidor devuelve un objeto `errors` con campos. Mostrar inline por campo y por fila. Si `componentes.1.concepto_id` devuelve error, anclar el mensaje en la fila 2.
- 403/401: bloquear acceso y mostrar CTA para iniciar sesión o contacto admin.
- 500: mostrar modal con "Error interno" y opción para reintentar. Recolectar trace si ocurre en staging.

## Accesibilidad y UX fino

- Etiquetas visibles para inputs, y atributos aria para filas de componentes.
- Keyboard accessibility: permitir añadir/eliminar filas y reordenar con teclado.
- Confirm modal para `is_protected: true` explicando que editar después será restringido.

## Tests y QA sugeridos

- E2E: flujo crear ratio → preview → guardar → verificar en API que componentes se sincronizaron.
- Unit: validación de formulario (mínimo 2 componentes, concept_id válido).
- Integration: crear ratio y poblar detalles contables de prueba para comprobar cálculo con `RatioCalculator`.

## Puntos de integración con backend (checks)

- Asegurar que la lista de `conceptos` se carga antes de permitir crear componentes. Si no existen conceptos, mostrar alerta y link a la pantalla de administradores para crear conceptos.
- Mostrar conteo de cuentas mapeadas por concepto (si backend provee endpoint de mapeos) para advertir al usuario.
- En edición, precargar `componentes` y mapear `operacion` y `factor` a los controles correspondientes.

## Resumen corto — Checklist para el desarrollador frontend

- [ ] Cargar `conceptos` y `categorias` al montar la vista.
- [ ] Validar localmente los campos antes de llamar a backend.
- [ ] Ofrecer vista previa (dry-run) antes de persistir.
- [ ] Enviar `componentes` completos al backend en create/update.
- [ ] Mostrar warnings cuando conceptos no tengan cuentas mapeadas.
- [ ] Tratar correctamente `is_protected` (confirm y deshabilitar edición si no admin).

---

Si quieres, puedo generar un componente React/Vue de ejemplo (JSX/TSX o SFC) con la estructura de formulario, validaciones y llamadas Axios listadas arriba. ¿Deseas que lo genere para React o Vue?
