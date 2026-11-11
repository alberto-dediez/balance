# Manual del integrador — Greenhost

Guía paso a paso para conectar vuestro sistema con la API de la plataforma y **listar las propiedades de Greenhost**.

Al terminar sabréis hacer dos cosas:

1. **Iniciar sesión** en la API usando un *token de API* (en lugar de la contraseña).
2. **Listar las propiedades** del distribuidor Greenhost.

| Dato | Valor |
|------|-------|
| Distribuidor | Greenhost |
| Identificador del distribuidor (`distributorId`) | `01a1828c-dd06-74b1-aa60-64fe0cc0dc0d` |
| Formato | JSON (`Content-Type: application/json`) |
| Autenticación | JWT (Bearer), obtenido con usuario + token de API |

> En todo el manual, `https://balance.bioscore.info` es la dirección base de la plataforma.

---

## Visión general

```
1. Crear un token de API (una sola vez, desde la web)
        │
        ▼
2. POST /api/login   { usuario + token de API }   →   { "token": "<JWT>" }
        │
        ▼
3. GET /api/distributors/{distributorId}/properties
   Cabecera:  Authorization: Bearer <JWT>        →   lista de propiedades
```

Solo hay **un paso manual** (crear el token de API en la web). El resto lo hace vuestro programa.

---

## Paso 1. Crear el token de API

Un token de API es una clave personal que se usa **en el lugar de la contraseña** al iniciar sesión. Ventajas: vuestro sistema no guarda la contraseña real del usuario y el token se puede revocar en cualquier momento.

La función ya está habilitada para la cuenta de Greenhost.

1. Entrad en la plataforma con el usuario que usará la integración.
2. Desplegad el menú de vuestro **perfil** (arriba a la derecha) y elegid **Tokens de API**.
3. Pulsad el botón de crear un token nuevo.
4. Rellenad:
   - **Nombre**: algo que recuerde para qué sirve, por ejemplo `Integración listado de propiedades`.
   - **Fecha de caducidad** (opcional): si la indicáis, debe ser posterior a hoy y el token funciona hasta el final de ese día. Si la dejáis vacía, no caduca.
5. Guardad. La plataforma mostrará el valor completo del token, con este aspecto:

   ```
   hyd_AbCdEf0123456789...........................
   ```

> ⚠️ **Copiad el token en ese momento.** Solo se muestra **una vez**. Si lo perdéis, tendréis que crear otro y revocar el anterior.

Reglas que conviene conocer:

- Un token da **exactamente los mismos permisos** que el usuario que lo creó.
- Cada usuario puede tener como máximo **10 tokens activos** (los revocados y caducados no cuentan).
- Una vez creado no se puede cambiar su nombre ni su caducidad. Si hace falta cambiarlos, crear uno nuevo.
- Para **revocarlo**, volved a **Tokens de API** y usad la acción *Revocar*. Deja de funcionar al instante, incluso en sesiones ya abiertas con él.
- Tratadlo como una contraseña: guardadlo en un gestor de secretos o en una variable de entorno, nunca en el código fuente ni en repositorios.

---

## Paso 2. Iniciar sesión (obtener el JWT)

Se hace una petición `POST` a `/api/login` enviando el **email del usuario** en `username` y el **token de API** en `password`.

### Petición

```
POST https://balance.bioscore.info/api/login
Content-Type: application/json
```

```json
{
  "username": "usuario@greenhost.example",
  "password": "hyd_AbCdEf0123456789..........................."
}
```

### Respuesta correcta (HTTP 200)

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...."
}
```

Ese valor es el **JWT**. Guardadlo: lo necesitaréis en el paso 3.

### Ejemplo con curl

```bash
curl -s -X POST "https://balance.bioscore.info/api/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"usuario@greenhost.example","password":"'"$API_TOKEN"'"}'
```

### Cosas importantes sobre el login

- **El JWT dura 1 hora (3600 s).** Pasado ese tiempo la API responde `401`; basta con repetir el login. Lo recomendable es renovarlo cuando recibáis un `401`, o unos minutos antes de que expire.
- Si el token de API se **revoca o caduca**, los JWT obtenidos con él también dejan de funcionar (`401`).
- **Límite de intentos:** tres intentos fallidos seguidos bloquean el acceso temporalmente. Si recibís `401`, comprobad el usuario y el token antes de reintentar en bucle.
- Si preferís, la contraseña normal del usuario también funciona en el campo `password`, pero **recomendamos siempre el token de API**.

### Errores del login

| Código | Significado | Qué hacer |
|--------|-------------|-----------|
| `401` | Usuario o token incorrectos, token revocado o caducado, cuenta sin la función activada | Revisad el email, copiad de nuevo el token (sin espacios ni saltos de línea) o crear uno nuevo. Por seguridad la API no indica el motivo exacto |
| `429` | Demasiados intentos | Esperad unos minutos |

---

## Paso 3. Listar las propiedades de Greenhost

Con el JWT del paso anterior, se pide el listado del distribuidor.

### Petición

```
GET https://balance.bioscore.info/api/distributors/01a1828c-dd06-74b1-aa60-64fe0cc0dc0d/properties
Authorization: Bearer <JWT>
```

No lleva cuerpo ni parámetros: devuelve **todas** las propiedades (sin paginar) que pertenecen a Greenhost o que tiene asignadas como distribuidor.

### Ejemplo con curl

```bash
JWT="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...."

curl -s "https://balance.bioscore.info/api/distributors/01a1828c-dd06-74b1-aa60-64fe0cc0dc0d/properties" \
  -H "Authorization: Bearer $JWT"
```

### Respuesta correcta (HTTP 200)

Un array JSON; cada elemento es una propiedad:

```json
[
  {
    "propertyId": "8de87304-f72e-4a62-bca9-313b27d068f3",
    "name": "Hotel Abaco",
    "accountId": "01a1828c-dd06-74b1-aa60-64fe0cc0dc0d",
    "propertyType": "Hotel",
    "address": "Calle Mayor 1",
    "postalCode": "29001",
    "destinationId": "dest-123",
    "countryId": "country-123",
    "operatingRegime": "owned",
    "operatorEmail": "franquicia@hotel.es",
    "operatorName": "Ana",
    "operatorLocale": "es"
  }
]
```

Si no hay propiedades, la respuesta es un array vacío: `[]`.

### Descripción de los campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `propertyId` | texto | Identificador único de la propiedad |
| `name` | texto | Nombre de la propiedad |
| `accountId` | texto o `null` | Cuenta a la que pertenece la propiedad |
| `propertyType` | texto o `null` | Tipo de propiedad (por ejemplo, `Hotel`) |
| `address` | texto o `null` | Dirección |
| `postalCode` | texto o `null` | Código postal |
| `destinationId` | texto o `null` | Identificador del destino |
| `countryId` | texto o `null` | Identificador del país |
| `operatingRegime` | texto | Régimen de explotación (por ejemplo, `owned`) |
| `operatorEmail` | texto o `null` | Email del operador (si lo hay) |
| `operatorName` | texto o `null` | Nombre del operador |
| `operatorLocale` | texto o `null` | Idioma del operador (`es`, `en`…) |

Cualquier campo marcado como «o `null`» puede venir vacío: vuestro código debe tolerarlo.

### Errores del listado

| Código | Significado | Qué hacer |
|--------|-------------|-----------|
| `401` | Falta la cabecera `Authorization`, el JWT ha caducado (dura 1 h) o el token de API se revocó | Repetir el login (paso 2) |
| `403` | El usuario no tiene permiso para ver las propiedades de ese distribuidor | Usad un usuario de Greenhost con permisos de consulta de propiedades |
| `404` | La cuenta indicada no es un distribuidor activo | Revisad que el `distributorId` es exactamente `01a1828c-dd06-74b1-aa60-64fe0cc0dc0d` |

---

## Ejemplo completo

Guardad el token de API en una variable de entorno y ejecutad:

```bash
#!/usr/bin/env bash
set -euo pipefail

BASE_URL="https://balance.bioscore.info"
USUARIO="usuario@greenhost.example"
DISTRIBUIDOR="01a1828c-dd06-74b1-aa60-64fe0cc0dc0d"
: "${API_TOKEN:?Definid la variable API_TOKEN con el token de API}"

JWT=$(curl -sf -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"$USUARIO\",\"password\":\"$API_TOKEN\"}" \
  | jq -r '.token')

curl -sf "$BASE_URL/api/distributors/$DISTRIBUIDOR/properties" \
  -H "Authorization: Bearer $JWT" \
  | jq '.'
```

## Buenas prácticas

- **Guardad el JWT y reutilizadlo** durante su hora de vida; no hagáis login en cada petición.
- **Renovad ante un `401`**: repetid el login una vez y reintentad la petición. Si vuelve a fallar, avisad en lugar de reintentar en bucle (por el límite de intentos).
- **Un token por integración** (y uno por entorno: pruebas, producción). Así, si hay que revocar uno, el resto no se ve afectado.
- **Usad una fecha de caducidad** y anotad cuándo toca renovar el token, para evitar cortes del servicio.
- **No registréis nunca** el token de API ni el JWT en los logs.
- **Siempre HTTPS.**

## Lista de comprobación antes de dar la integración por terminada

- [ ] Tengo el token de API creado y guardado de forma segura.
- [ ] `POST /api/login` devuelve `200` con un campo `token`.
- [ ] `GET /api/distributors/01a1828c-dd06-74b1-aa60-64fe0cc0dc0d/properties` devuelve `200` y un array.
- [ ] Mi código tolera campos `null` en la respuesta.
- [ ] Mi código vuelve a hacer login cuando recibe un `401`.
- [ ] El token no aparece en el código fuente ni en los logs.

## Resumen de rutas

| Acción | Método y ruta | Autenticación |
|--------|---------------|---------------|
| Iniciar sesión | `POST /api/login` | Ninguna (usuario + token de API en el cuerpo) |
| Listar propiedades del distribuidor | `GET /api/distributors/01a1828c-dd06-74b1-aa60-64fe0cc0dc0d/properties` | `Authorization: Bearer <JWT>` |
