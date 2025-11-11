# 🚀 Guía de Uso - CRUD de Usuarios

## 📋 Descripción

Sistema completo para la gestión de usuarios con rol "Analista Financiero". Los administradores pueden crear, editar, desactivar y eliminar usuarios sin necesidad de manejar contraseñas.

## 🎯 Características Principales

### ✨ Funcionalidades

- ✅ **Crear usuarios** sin contraseña (se establece en primer login)
- ✅ **Editar información** de usuarios (nombre, empresa)
- ✅ **Asignar empresa** al crear o editar
- ✅ **Desactivar usuarios** temporalmente
- ✅ **Reactivar usuarios** desactivados
- ✅ **Eliminar permanentemente** usuarios inactivos
- ✅ **Filtros avanzados** por estado, empresa y búsqueda
- ✅ **Estadísticas en tiempo real**

### 🎨 Interfaz de Usuario

La interfaz sigue el mismo diseño que el resto del sistema:
- Tabla responsive con información clara
- Modal elegante para crear/editar
- Estadísticas visuales con tarjetas
- Filtros intuitivos
- Búsqueda en tiempo real

## 🔧 Acceso al Sistema

### Requisitos previos

1. **Usuario con rol Administrador**
2. **Permiso `manage_users`** asignado

### Acceso desde el Dashboard

1. Inicia sesión como Administrador
2. En el menú lateral, busca la opción **"Usuarios"** (icono de personas)
3. Click en "Usuarios" para acceder a la gestión

## 📖 Guía de Uso

### 1️⃣ Crear Nuevo Usuario

**Paso a paso:**

1. Click en el botón **"Nuevo Usuario"** (esquina superior derecha)
2. Se abrirá un modal con el formulario
3. Completa los campos:
   - **Nombre**: Nombre completo del usuario (requerido)
   - **Email**: Email único del usuario (requerido)
   - **Empresa**: Selecciona una empresa o deja "Sin empresa asignada" (opcional)
4. Click en **"Crear Usuario"**
5. El usuario aparecerá en la tabla con estado "Activo"

**Importante:**
- El email debe ser único en el sistema
- No se requiere contraseña
- El usuario recibirá instrucciones para establecer su contraseña en el primer login
- El rol siempre será "Analista Financiero"

### 2️⃣ Editar Usuario

**Paso a paso:**

1. Localiza el usuario en la tabla
2. Click en el botón **"Editar"** (icono de lápiz)
3. Se abrirá el modal con los datos actuales
4. Modifica los campos que desees:
   - **Nombre**: Puedes cambiar el nombre
   - **Email**: No se puede modificar (identificador único)
   - **Empresa**: Puedes cambiar o quitar la asignación
5. Click en **"Actualizar Usuario"**

**Notas:**
- El email NO puede cambiarse por seguridad
- Los cambios se aplican inmediatamente

### 3️⃣ Desactivar Usuario

**Paso a paso:**

1. Localiza el usuario activo en la tabla
2. Click en el botón **"Desactivar"** (icono de X)
3. Confirma la acción en el diálogo
4. El usuario cambiará a estado "Inactivo"

**Efectos:**
- El usuario NO podrá iniciar sesión
- Los datos se conservan
- Puede reactivarse en cualquier momento

### 4️⃣ Reactivar Usuario

**Paso a paso:**

1. Filtra por usuarios "Inactivos" o visualiza "Todos"
2. Localiza el usuario inactivo (badge rojo)
3. Click en el botón **"Reactivar"** (icono de flecha circular)
4. Confirma la acción
5. El usuario cambiará a estado "Activo"

### 5️⃣ Eliminar Usuario Permanentemente

**⚠️ ADVERTENCIA: Esta acción NO se puede deshacer**

**Paso a paso:**

1. **Primero desactiva** el usuario (ver paso 3)
2. El botón **"Eliminar"** aparecerá solo para usuarios inactivos
3. Click en **"Eliminar"** (icono de papelera roja)
4. Confirma la eliminación permanente
5. El usuario se elimina de la base de datos

**Importante:**
- Solo usuarios inactivos pueden eliminarse
- Los datos se pierden permanentemente
- Se recomienda desactivar en lugar de eliminar

## 🔍 Filtros y Búsqueda

### Búsqueda Rápida

En el campo de búsqueda puedes escribir:
- Nombre del usuario
- Email
- Nombre de empresa

La búsqueda es en tiempo real (sin necesidad de presionar Enter).

### Filtro por Estado

Opciones disponibles:
- **Todos los usuarios**: Muestra activos e inactivos
- **Solo activos**: Solo usuarios que pueden acceder al sistema
- **Solo inactivos**: Solo usuarios desactivados

### Filtro por Empresa

Opciones:
- **Todas las empresas**: Sin filtro
- **Sin empresa**: Solo usuarios sin empresa asignada
- **[Nombre de empresa]**: Usuarios de esa empresa específica

## 📊 Estadísticas

Las tarjetas en la parte superior muestran:

1. **Total Usuarios**: Cantidad total de usuarios registrados
2. **Activos**: Usuarios que pueden acceder al sistema
3. **Inactivos**: Usuarios desactivados
4. **Con Empresa**: Usuarios con empresa asignada

## 🔐 Flujo de Primer Login del Usuario

Cuando creas un usuario:

1. El usuario recibe un email con su información de acceso
2. Al intentar iniciar sesión por primera vez:
   - Ingresa su email
   - El sistema detecta que no tiene contraseña
   - Se le solicita crear una contraseña
   - Debe cumplir con requisitos de seguridad (mínimo 8 caracteres)
3. Una vez establecida la contraseña:
   - Puede iniciar sesión normalmente
   - Tiene acceso según su empresa asignada

## 💡 Mejores Prácticas

### ✅ Recomendaciones

1. **Asignar empresa al crear**: Facilita la organización desde el inicio
2. **Desactivar en lugar de eliminar**: Permite recuperar usuarios si es necesario
3. **Usar filtros**: Para encontrar usuarios rápidamente en listas grandes
4. **Verificar email**: Asegúrate de que el email sea correcto antes de crear

### ❌ Evitar

1. **Emails genéricos**: Usa emails corporativos reales
2. **Eliminar sin desactivar primero**: Siempre prueba desactivando primero
3. **Nombres ambiguos**: Usa nombres completos y claros

## 🐛 Solución de Problemas

### Problema: "Email ya existe"
**Solución**: El email debe ser único. Verifica si el usuario ya existe o usa otro email.

### Problema: "No puedo eliminar un usuario"
**Solución**: Solo usuarios inactivos pueden eliminarse. Primero desactiva el usuario.

### Problema: "No veo la opción Usuarios en el menú"
**Solución**: Verifica que tengas:
- Rol de Administrador
- Permiso `manage_users` asignado

### Problema: "Error al crear usuario"
**Solución**: Verifica que:
- Todos los campos requeridos estén completos
- El email sea válido
- El email no esté registrado previamente

## 📞 Soporte

Si encuentras algún problema o necesitas ayuda:

1. Verifica esta documentación
2. Consulta los logs del sistema
3. Contacta al equipo de desarrollo

## 🔄 Actualizaciones Futuras

Posibles mejoras planificadas:
- [ ] Notificaciones por email automáticas
- [ ] Importación masiva de usuarios desde Excel
- [ ] Exportación de lista de usuarios
- [ ] Historial de cambios por usuario
- [ ] Asignación múltiple de empresas

---

**Versión**: 1.0  
**Última actualización**: 2025-01-10  
**Desarrollado para**: Sistema de Análisis Financiero
