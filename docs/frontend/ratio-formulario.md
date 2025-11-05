# Especificación del formulario frontend para registrar un Ratio

Última actualización: 2025-11-01

Propósito
--------
Documento dirigido al equipo frontend que describe el formulario, validaciones, payload y comportamiento esperado al crear o editar una definición de ratio (`RatioDefinicion`).

Visión general
---------------
El formulario permite a un administrador crear o actualizar una definición de ratio que luego podrá ser usada para calcular indicadores sobre los estados financieros de las empresas. Solo usuarios con rol `Administrador` (backend) pueden enviar el formulario correctamente — el frontend debe ocultar el acceso para otros roles.

Endpoints relevantes
--------------------
- POST /api/ratios/definiciones  -> crear (permiso: gestionar_ratios_definicion)
- PUT  /api/ratios/definiciones/{id} -> actualizar (permiso: gestionar_ratios_definicion)
- GET  /api/ratios/definiciones/{id} -> obtener una definición
- GET  /api/ratios/categorias -> (público) lista de categorías permitidas

Contrato del formulario (campos principales)
-------------------------------------------
Campos del objeto principal (form-level)
- codigo (string) — requerido, máximo 30 caracteres.
  - Debe ser único en el servidor.
- nombre (string) — requerido, máximo 120 caracteres.
- formula (string) — requerido. Texto libre para mostrar en UI; no se evalúa directamente.
- sentido (string) — requerido. Uno de: `MAYOR_MEJOR`, `MENOR_MEJOR`, `CERCANO_A_1`.
- categoria (string) — requerido. Valores permitidos (obtener con GET /api/ratios/categorias):
  - `LIQUIDEZ`, `ENDEUDAMIENTO`, `RENTABILIDAD`, `EFICIENCIA`, `COBERTURA`.
-- multiplicador_resultado (number) — opcional. Factor que se aplicará al resultado final (por ejemplo 100 para presentar en %). Alternativamente puedes especificar `multiplicador_numerador` o `multiplicador_denominador` para escalar bloques específicos.
- is_protected (boolean) — opcional. Si `true`, solo administradores podrán editar/eliminar esta definición en el futuro.

Campos del array `componentes` (cada fila representa un componente de la fórmula)
- componentes (array) — requerido, mínimo 2 elementos (al menos NUMERADOR y DENOMINADOR).
  - componentes[].concepto_id (integer) — requerido; id de `ConceptoFinanciero` (obtener desde GET conceptos endpoint).
  - componentes[].rol (string) — requerido; uno de: `NUMERADOR`, `DENOMINADOR`, `OPERANDO`.
  - componentes[].orden (integer) — requerido; posición en la fórmula (1..N). En la práctica: numerador elementos suelen orden 1..n, denominador orden 1.
  - componentes[].requiere_promedio (boolean) — requerido; si `true`, se promedia con el periodo anterior cuando exista.
  - componentes[].operacion (string) — requerido; una de: `ADD`, `SUB`, `MUL`, `DIV`. Indica cómo se combina este componente dentro del bloque (p. ej. `SUB` para restar inventario del numerador).
  - componentes[].factor (number) — opcional; factor multiplicador aplicado a este componente (por defecto 1.0).

Ejemplo de payload JSON (crear)
--------------------------------
{
  "codigo": "PRUEBA_ACIDA",
  "nombre": "Prueba Ácida",
  "formula": "(Activo Corriente - Inventario) / Pasivo Corriente",
  "sentido": "MAYOR_MEJOR",
  "categoria": "LIQUIDEZ",
  "multiplicador_resultado": 1.0,
  "is_protected": true,
  "componentes": [
  { "concepto_id": 1, "rol": "NUMERADOR", "orden": 1, "requiere_promedio": false, "operacion": "ADD", "factor": 1.0 },
  { "concepto_id": 5, "rol": "OPERANDO", "orden": 2, "requiere_promedio": false, "operacion": "SUB", "factor": 1.0 },
  { "concepto_id": 2, "rol": "DENOMINADOR", "orden": 1, "requiere_promedio": false, "operacion": "ADD", "factor": 1.0 }
  ]
}

Notas de UX y validaciones en cliente
-------------------------------------
- Mostrar la lista de `categorias` desde GET /api/ratios/categorias y usar un select controlado.
- Validaciones cliente (antes de POST):
  - `codigo`, `nombre`, `formula`, `sentido`, `categoria` obligatorios.
  - `componentes` debe tener >= 2 elementos.
  - Cada componente debe tener `concepto_id`, `rol`, `orden`, `requiere_promedio` y `operacion`.
  - Enviar `multiplicador_resultado` si el usuario lo proporciona (convertir a number). También se aceptan `multiplicador_numerador` y `multiplicador_denominador`.
- Orden de componentes: el frontend puede permitir mover filas para ajustar `orden`; al enviar el formulario, mapear la posición visual a `orden` numérico.

Mensajes de error esperados desde backend (manejarlos en UI)
---------------------------------------------------------
- 422 ValidationError: estructura inválida (por ejemplo `componentes` faltantes o tipos incorrectos).
  - Mostrar errores por campo (cada FormRequest devuelve mensajes por campo). Ejemplo: `componentes.0.concepto_id: El campo es requerido`.
- 403 / 401: Si el usuario no tiene permisos (si el frontend permite ver la página pero no está autenticado o no es Admin).
- 500: Error en servidor — mostrar mensaje genérico y opción para reintentar.

Comportamiento especial
------------------------
- `is_protected`: si el formulario se envía con `is_protected: true`, el backend marcará la definición como protegida; solo admins podrán editar o borrar después. El frontend debe mostrar un aviso (tooltip) explicando la restricción.
-- `multiplicador_resultado`: comúnmente se usa para escalar resultados (ej. multiplicar por 100 para %). Mostrar ayuda contextual.
- `formula` es texto descriptivo; el cálculo real se basa en los `componentes` y en los mappings `cuenta_concepto`.

Checklist antes de enviar al backend
-----------------------------------
1. El usuario autenticado tiene rol Administrador (si no, ocultar botón enviar).
2. `componentes` tiene al menos un NUMERADOR y un DENOMINADOR.
3. Todos los `concepto_id` referenciados existen en la lista de `ConceptoFinanciero` (pre-cargar catálogo de conceptos).
4. Conversión correcta de tipos (orden -> integer, requiere_promedio -> boolean, sentido -> integer).

Patrones de UX recomendados
---------------------------
- Mostrar un resumen/preview del ratio (texto `formula`) y un botón "Probar cálculo" que use el endpoint de cálculo individual (GET /api/ratios/definiciones/{id}/calculate) para hacer un dry-run una vez creada la definición.
- Ofrecer controles drag-and-drop para reordenar componentes y un botón para añadir/eliminar filas.
- Mostrar una tabla de "Componentes detectados" donde se muestre cuántas cuentas del catálogo están mapeadas a cada `ConceptoFinanciero` (llamar al backend si hace falta) para que el usuario sepa si los conceptos están listos.

Notas para el equipo frontend
----------------------------
- Recuerda enviar la lista completa de `componentes` en cada update: el backend usa `sync()` y reemplaza la colección pivote completa.
- Si se crea una definición con `is_protected: true`, ocultar o deshabilitar la edición para usuarios que no sean Admin al renderizar el formulario de edición.

Ejemplo de llamada Axios (crear)
-------------------------------
```js
const payload = { /* ver ejemplo arriba */ };
const token = localStorage.getItem('token');

axios.post('/api/ratios/definiciones', payload, {
  headers: { Authorization: `Bearer ${token}` }
})
.then(resp => console.log('Creado', resp.data))
.catch(err => console.error(err.response?.data || err.message));
```

---
Archivo: `docs/frontend/ratio-formulario.md`
