# Ejemplos de Pruebas API - Relación Usuario-Empresa

## Configuración Inicial

### Variables de Entorno
```bash
BASE_URL=http://localhost:8000/api
TOKEN=tu_token_de_autenticacion
```

---

## 1. Autenticación

### Login como Administrador
```bash
curl -X POST ${BASE_URL}/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "administrador@test.com",
    "password": "tu_password"
  }'
```

**Respuesta esperada:**
```json
{
  "data": {
    "id": 1,
    "name": "Administrador test",
    "email": "administrador@test.com",
    "empresa_id": null,
    "roles": [...]
  },
  "access_token": "1|xxxxx...",
  "token_type": "Bearer"
}
```

---

## 2. Gestión de Usuarios con Empresa

### 2.1 Listar Todos los Usuarios
```bash
curl -X GET "${BASE_URL}/users" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 2.2 Filtrar Usuarios por Empresa
```bash
# Usuarios de la empresa con ID 1
curl -X GET "${BASE_URL}/users?empresa_id=1" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 2.3 Filtrar Usuarios Sin Empresa
```bash
curl -X GET "${BASE_URL}/users?empresa_id=null" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 2.4 Ver Usuario Específico
```bash
curl -X GET "${BASE_URL}/users/2" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "user": {
    "id": 2,
    "name": "Analista Financiero test",
    "email": "analista_financiero@test.com",
    "empresa_id": 1,
    "empresa": {
      "id": 1,
      "nombre": "Empresa Demo S.A.",
      "codigo": "EMP001"
    },
    "roles": [...]
  }
}
```

---

## 3. Registrar Usuario con Empresa

### 3.1 Registrar con Empresa Asignada
```bash
curl -X POST ${BASE_URL}/register \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "María García",
    "email": "maria@example.com",
    "role_id": 2,
    "empresa_id": 5
  }'
```

### 3.2 Registrar Sin Empresa
```bash
curl -X POST ${BASE_URL}/register \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Carlos López",
    "email": "carlos@example.com",
    "role_id": 3
  }'
```

---

## 4. Actualizar Empresa de Usuario

### 4.1 Asignar Empresa a Usuario
```bash
curl -X PUT ${BASE_URL}/users/3 \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 7
  }'
```

### 4.2 Remover Empresa de Usuario
```bash
curl -X PUT ${BASE_URL}/users/3 \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": null
  }'
```

### 4.3 Actualizar Usuario Completo
```bash
curl -X PUT ${BASE_URL}/users/3 \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Carlos López Actualizado",
    "email": "carlos.lopez@example.com",
    "empresa_id": 5,
    "role_id": 2,
    "active": true
  }'
```

---

## 5. Gestión de Empresas

### 5.1 Listar Todas las Empresas
```bash
curl -X GET "${BASE_URL}/empresas" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 5.2 Ver Empresa Específica
```bash
curl -X GET "${BASE_URL}/empresas/5" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 5.3 Listar Usuarios de una Empresa
```bash
curl -X GET "${BASE_URL}/empresas/5/usuarios" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "empresa": {
      "id": 5,
      "nombre": "TechCorp S.A.",
      "codigo": "TECH001"
    },
    "usuarios": [
      {
        "id": 2,
        "name": "María García",
        "email": "maria@example.com",
        "empresa_id": 5,
        "roles": [
          {
            "id": 2,
            "name": "Analista Financiero"
          }
        ]
      },
      {
        "id": 4,
        "name": "Pedro Martínez",
        "email": "pedro@example.com",
        "empresa_id": 5,
        "roles": [
          {
            "id": 3,
            "name": "Inversor"
          }
        ]
      }
    ],
    "total_usuarios": 2
  }
}
```

---

## 6. Pruebas de Middleware (empresa.access)

### 6.1 Acceso Permitido (Administrador)
```bash
# Administrador puede acceder a cualquier empresa
curl -X GET "${BASE_URL}/empresas/5/ratios" \
  -H "Authorization: Bearer ${TOKEN_ADMIN}" \
  -H "Accept: application/json"
```

### 6.2 Acceso Permitido (Usuario con Empresa)
```bash
# Usuario con empresa_id=5 accede a su empresa
curl -X GET "${BASE_URL}/empresas/5/ratios" \
  -H "Authorization: Bearer ${TOKEN_USER_EMP5}" \
  -H "Accept: application/json"
```

### 6.3 Acceso Denegado (Usuario Diferente Empresa)
```bash
# Usuario con empresa_id=3 intenta acceder a empresa 5
curl -X GET "${BASE_URL}/empresas/5/ratios" \
  -H "Authorization: Bearer ${TOKEN_USER_EMP3}" \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "No tienes acceso a esta empresa."
}
```

### 6.4 Acceso Denegado (Usuario Sin Empresa)
```bash
# Usuario sin empresa_id intenta acceder
curl -X GET "${BASE_URL}/empresas/5/ratios" \
  -H "Authorization: Bearer ${TOKEN_USER_NO_EMP}" \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "success": false,
  "message": "No tienes una empresa asignada."
}
```

---

## 7. Combinaciones de Filtros

### 7.1 Usuarios Activos de una Empresa
```bash
curl -X GET "${BASE_URL}/users?empresa_id=5&active=true" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

### 7.2 Usuarios Inactivos Sin Empresa
```bash
curl -X GET "${BASE_URL}/users?empresa_id=null&active=false" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

---

## 8. Casos de Error

### 8.1 Empresa No Existe
```bash
curl -X PUT ${BASE_URL}/users/3 \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 9999
  }'
```

**Respuesta esperada:**
```json
{
  "empresa_id": [
    "The selected empresa id is invalid."
  ]
}
```

### 8.2 Usuario No Existe
```bash
curl -X GET "${BASE_URL}/users/9999" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json"
```

**Respuesta esperada:**
```json
{
  "message": "Usuario no encontrado"
}
```

---

## 9. Scripts de Prueba Automatizada

### Script Bash Completo
```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"

# 1. Login
echo "=== LOGIN ==="
LOGIN_RESPONSE=$(curl -s -X POST ${BASE_URL}/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "administrador@test.com",
    "password": "password"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.access_token')
echo "Token obtenido: ${TOKEN:0:20}..."

# 2. Listar empresas
echo -e "\n=== LISTAR EMPRESAS ==="
curl -s -X GET "${BASE_URL}/empresas" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json" | jq '.data.data[0]'

# 3. Crear usuario con empresa
echo -e "\n=== CREAR USUARIO CON EMPRESA ==="
NEW_USER=$(curl -s -X POST ${BASE_URL}/register \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test'$(date +%s)'@example.com",
    "role_id": 2,
    "empresa_id": 1
  }')

USER_ID=$(echo $NEW_USER | jq -r '.data.id')
echo "Usuario creado con ID: $USER_ID"
echo $NEW_USER | jq '.data'

# 4. Ver usuarios de la empresa
echo -e "\n=== USUARIOS DE EMPRESA 1 ==="
curl -s -X GET "${BASE_URL}/empresas/1/usuarios" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json" | jq '.data'

# 5. Cambiar empresa del usuario
echo -e "\n=== CAMBIAR EMPRESA DEL USUARIO ==="
curl -s -X PUT ${BASE_URL}/users/${USER_ID} \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 2
  }' | jq '.user'

echo -e "\n=== PRUEBAS COMPLETADAS ==="
```

### Guardar y ejecutar:
```bash
chmod +x test_usuario_empresa.sh
./test_usuario_empresa.sh
```

---

## 10. Pruebas con Postman

### Colección Postman (JSON)
```json
{
  "info": {
    "name": "Usuario-Empresa API Tests",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "1. Login",
      "request": {
        "method": "POST",
        "header": [],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"email\": \"administrador@test.com\",\n  \"password\": \"password\"\n}",
          "options": { "raw": { "language": "json" } }
        },
        "url": { "raw": "{{base_url}}/login" }
      }
    },
    {
      "name": "2. Listar Usuarios",
      "request": {
        "method": "GET",
        "header": [
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "url": { "raw": "{{base_url}}/users" }
      }
    },
    {
      "name": "3. Usuarios por Empresa",
      "request": {
        "method": "GET",
        "header": [
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "url": { "raw": "{{base_url}}/users?empresa_id=1" }
      }
    },
    {
      "name": "4. Usuarios de Empresa",
      "request": {
        "method": "GET",
        "header": [
          { "key": "Authorization", "value": "Bearer {{token}}" }
        ],
        "url": { "raw": "{{base_url}}/empresas/1/usuarios" }
      }
    }
  ],
  "variable": [
    { "key": "base_url", "value": "http://localhost:8000/api" },
    { "key": "token", "value": "" }
  ]
}
```

---

## 📝 Notas

1. Reemplaza `${TOKEN}` con tu token real
2. Ajusta `${BASE_URL}` según tu entorno
3. Los IDs (usuario, empresa) deben existir en tu BD
4. Algunos endpoints requieren permisos específicos

---

## 🎯 Checklist de Pruebas

- [ ] Login exitoso
- [ ] Listar usuarios con empresa
- [ ] Filtrar por empresa_id
- [ ] Filtrar usuarios sin empresa
- [ ] Crear usuario con empresa
- [ ] Crear usuario sin empresa
- [ ] Actualizar empresa de usuario
- [ ] Listar usuarios de una empresa
- [ ] Middleware bloquea acceso no autorizado
- [ ] Administrador accede a todas las empresas
- [ ] Validación de empresa_id inválido
- [ ] Manejo de errores 404
