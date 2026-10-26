# Modelos de IA intercambiables (Gemini o Anthropic) — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-26 · **Bounded Contexts:** `Shared` (`Infrastructure/AI/`)

## Qué hace y por qué

Las plataformas Bioscore construidas sobre biohydra (reporting, events, food-loss) generan informes, traducciones y sugerencias con modelos de IA. Hasta ahora el único cliente común hablaba solo con la API de Google (Gemini). Este cambio introduce un contrato neutro (`AiClient`) y un segundo adaptador para la API de Anthropic (Claude), y el proveedor se elige por entorno con `AI_PROVIDER`.

Decisiones clave (tabla *Decisiones tomadas* de `plan.md`): contrato renombrado sin alias `Gemini*`; `gemini` por defecto para no alterar ninguna plataforma al mezclar; transporte directo con `symfony/http-client` (sin SDK); los prompts siguen devolviendo el esquema en formato Gemini (tipos en mayúsculas) y se normaliza al enviarlo a Anthropic; sin fallback ante un rechazo de seguridad. En biohydra no hay prompts ni consumidores: solo la infraestructura. Los consumidores viven en los proyectos derivados.

## Mapa de piezas

| Fichero | Rol | Estado |
|---|---|---|
| `src/Shared/Infrastructure/AI/AiClient.php` | Puerto: `send(AiPrompt): array` | Nuevo (antes `Gemini/GeminiClient`) |
| `src/Shared/Infrastructure/AI/AiPrompt.php` | Contrato de prompt: `text()`, `responseSchema()`, `modelTier()`, `thinkingLevel()` | Nuevo (antes `GeminiPrompt`) |
| `src/Shared/Infrastructure/AI/AiModelTier.php`, `AiThinkingLevel.php` | Enums `LOW/MEDIUM/HIGH` y `MINIMAL/LOW/MEDIUM/HIGH` | Nuevos (antes `Gemini*`) |
| `src/Shared/Infrastructure/AI/AiProvider.php` | Enum `Gemini = 'gemini'`, `Anthropic = 'anthropic'` | Nuevo |
| `src/Shared/Infrastructure/AI/AiResponseException.php` | Fábricas `incomplete`, `httpError`, `invalidJson` | Nuevo (antes `GeminiResponseException`; `httpError` añadida en la review) |
| `src/Shared/Infrastructure/AI/AiRequestLog.php` | Log JSON por llamada, compartido | Nuevo (extraído de `GeminiHttpClient::writeLog()`) |
| `src/Shared/Infrastructure/AI/ProviderAiClient.php` | `AiClient` que delega según `AI_PROVIDER` | Nuevo |
| `src/Shared/Infrastructure/AI/InMemoryAiClient.php` | Doble de test (`#[When(env: 'test')]`) | Nuevo (antes `InMemoryGeminiClient`) |
| `src/Shared/Infrastructure/AI/Gemini/GeminiHttpClient.php` | Adaptador Gemini | Modificado |
| `src/Shared/Infrastructure/AI/Anthropic/AnthropicHttpClient.php` | Adaptador Anthropic (`POST /v1/messages`) | Nuevo |
| `src/Shared/Infrastructure/AI/Anthropic/JsonSchemaNormalizer.php` | Esquema estilo Gemini → JSON Schema de Anthropic | Nuevo |
| `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml` | Parámetros `app.ai_*`, `app.anthropic_*`; wiring en `when@dev` y `when@prod` | Modificado |
| `.env` | Bloques `ai`, `google/gemini` y `anthropic` | Modificado |
| `tests/Unit/Shared/Infrastructure/AI/**` | 5 clases de test (ver *Tests*) | Nuevos/modificado |

## Modelo de dominio

No hay agregados ni eventos: es infraestructura de `Shared`.

Contrato (`AiPrompt.php:9-18`): cada prompt de negocio declara texto, esquema de respuesta (`array`), nivel de modelo y nivel de razonamiento. `AiClient::send()` devuelve siempre el JSON decodificado como `array`.

Excepciones (`AiResponseException.php:9-22`), todas `\RuntimeException`:

- `incomplete($model, $finishReason, $usage)`: la respuesta no terminó (`max_tokens`, `refusal`, bloqueo de Gemini…).
- `httpError($model, $status, $message)`: respuesta HTTP >= 400.
- `invalidJson($model, $error)`: el texto no es un objeto/array JSON.

## Flujo de una operación

1. El prompt llama a `AiClient::send()`. En `dev`/`prod` el alias `AiClient` apunta a `ProviderAiClient` (`services.yaml`, bloques `when@dev`/`when@prod`); en `test` es `InMemoryAiClient`, única implementación.
2. `ProviderAiClient` resuelve `AiProvider::from($provider)` en el constructor (`ProviderAiClient.php:22`): un valor desconocido de `AI_PROVIDER` lanza `\ValueError` al construir el servicio. `send()` delega con `match` (`:28-31`).
3. **Anthropic** (`AnthropicHttpClient.php:38-102`):
   - Cuerpo: `model` del tier, `max_tokens = 32000`, un mensaje `user` y `output_config` con `effort` y `format = {type: json_schema, schema: <normalizado>}` (`:42-50`). No envía `thinking`, `temperature`, `top_p`, `top_k` ni `fallbacks`, ni cabecera `anthropic-beta`.
   - Cabeceras `x-api-key` y `anthropic-version: 2026-10-21` (`:59`).
   - `AiThinkingLevel` → `effort`: `MINIMAL`/`LOW` → `low`, `MEDIUM` → `medium`, `HIGH` → `high` (`:132-139`).
   - Timeout: 300 s, 600 s con `AiThinkingLevel::HIGH` (`:127-130`).
   - Estado >= 400: se lee el cuerpo con `getContent(false)`, mensaje `error.message` o, si no es JSON, el cuerpo recortado a 200 caracteres, y se lanza `httpError` (`:64-75`).
   - `stop_reason` distinto de `end_turn` lanza `incomplete`; con `refusal` añade la categoría de `stop_details` (`:87-95`). No hay reintento ni fallback.
   - Texto = primer bloque `type: text` (`:107-116`); si no es array JSON, `invalidJson` (`:97-99`).
4. **Gemini** (`GeminiHttpClient.php:35-95`): igual estructura; cuerpo `contents` + `generationConfig` con `thinkingConfig` según familia del modelo (`:111-139`), cabecera `x-goog-api-key`, éxito solo con `finishReason === 'STOP'`; los errores HTTP también lanzan `httpError` (`:64-73`) y su timeout es 300/600 s (`:106-109`).
5. **Log** (`AiRequestLog::write`, `AiRequestLog.php:24-51`): si `AI_LOG_ENABLED=true`, cada adaptador escribe dos veces en el mismo fichero (antes con respuesta vacía, después con la respuesta o el error) en `var/ai-logs/<Y-m-d_H-i-s-u>_<Prompt>.json` con `provider`, `model`, `model_tier`, `prompt_class`, `request{text, thinkingLevel, responseSchema, body}`, `raw_response`, `parsed_result`. Un prompt de clase anónima se registra como `AnonymousPrompt` (`:31`).

### Normalización del esquema (`JsonSchemaNormalizer.php:31-75`)

Recursiva sobre `properties`, `items` (objeto o lista), `anyOf`, `allOf`, `$defs` y `definitions`:

- tipos a minúsculas (también en lista); `nullable: true` pasa a `[tipo, 'null']`;
- `additionalProperties: false` en todo objeto (tipo `object`, lista con `object` o con `properties`);
- `oneOf` se renombra a `anyOf` solo si no existe ya `anyOf` (`:55-58`);
- se eliminan claves no soportadas (`UNSUPPORTED_KEYS`, `:9-22`: límites numéricos, longitudes, `minItems`/`maxItems`, `pattern`, `propertyOrdering`, `nullable`) y los `format` fuera de `SUPPORTED_FORMATS` (`:24`, `:70-72`).

## Autorización y ámbito

No aplica: no hay `Authorizer`, ni acciones, ni impersonación, ni roles. El cliente lo usan servicios de los proyectos derivados, que son quienes autorizan.

## Persistencia y migración

Ninguna. No hay migración.

## Configuración y operación

| Variable | Defecto en `.env` | Uso |
|---|---|---|
| `AI_PROVIDER` | `gemini` | `gemini` \| `anthropic`; valor desconocido → `\ValueError` |
| `AI_LOG_ENABLED` | `false` | Activa `var/ai-logs/` (sustituye a `GEMINI_LOG_ENABLED`, eliminada) |
| `GEMINI_API_KEY`, `GEMINI_MODEL_LOW\|MEDIUM\|HIGH` | `tu_clave_de_api`, `gemini-2.5-flash` | Adaptador Gemini |
| `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL_LOW\|MEDIUM\|HIGH` | `tu_clave_de_api`, `claude-haiku-5-5`, `claude-sonnet-5-5`, `claude-sonnet-5-5` | Adaptador Anthropic |

- Para cambiar de proveedor basta editar el `.env.local` de la plataforma (`AI_PROVIDER` y la clave y modelos del proveedor).
- Wiring: parámetros `app.ai_provider`, `app.ai_log_enabled`, `app.gemini_*`, `app.anthropic_*` en `services.yaml` (cabecera) y servicios `AiRequestLog`, `GeminiHttpClient`, `AnthropicHttpClient`, `ProviderAiClient` y el alias `AiClient` repetidos en `when@dev` y `when@prod`. Las clases de adaptador, `ProviderAiClient` y su wiring llevan `#[When(env: 'dev')]`/`#[When(env: 'prod')]` como `HttpBioscoreClient`; así en `test` el alias ya no tapa al doble (en el estado anterior el alias era incondicional).
- No hay comandos de consola, colas ni tareas programadas propias. Para ver el log: `AI_LOG_ENABLED=true` y revisar `var/ai-logs/`.

## Cómo extenderlo

Añadir un tercer proveedor:

1. Crear `Infrastructure/AI/<Proveedor>/<Proveedor>HttpClient` que implemente `AiClient`, con `#[When(env: 'dev')]` y `#[When(env: 'prod')]`, usando `AiRequestLog` y lanzando `AiResponseException`.
2. Añadir el caso a `AiProvider` y la rama al `match` de `ProviderAiClient::send()` (PHPStan fallará si falta).
3. Declarar parámetros `app.<proveedor>_*`, el servicio en los bloques `when@dev` y `when@prod` y las variables en `.env`.
4. Cubrirlo con tests y actualizar `ProviderAiClientTest`.

Trampas conocidas:

- Los bloques `when@dev` y `when@prod` de `services.yaml` están duplicados y deben mantenerse idénticos.
- Los prompts siguen expresando el esquema con tipos en mayúsculas (formato Gemini); no hay que escribir JSON Schema estándar.
- En Anthropic `effort` solo toma `low|medium|high`; `MINIMAL` y `LOW` son indistinguibles.
- Tras la migración, los ficheros `AI/` ya no son idénticos a los de reporting: reporting los recibe por merge de biohydra y debe renombrar mecánicamente los usos `Gemini*` → `Ai*` (ver *Riesgos* del plan).
- Sin streaming: una respuesta que agote `max_tokens` o el timeout falla (`AiResponseException` o error de transporte).

## Tests

Unitarios en `tests/Unit/Shared/Infrastructure/AI/`:

- `AnthropicHttpClientTest`: esfuerzo por nivel, modelo por tier, cabeceras, timeout, esquema normalizado, ausencia de `thinking` y de parámetros de muestreo, JSON del primer bloque de texto, corte por `max_tokens`, `refusal`, JSON inválido, sin bloque de texto, error HTTP con y sin cuerpo JSON, log.
- `Gemini/GeminiHttpClientTest`: `thinkingConfig` por modelo (15 casos), sin muestreo, timeout, JSON, corte, bloqueo, JSON inválido, error HTTP, log.
- `Anthropic/JsonSchemaNormalizerTest`: minúsculas, `additionalProperties`, claves no soportadas, `nullable`, `items`, composiciones, `oneOf`/`anyOf`, definiciones.
- `ProviderAiClientTest`: delegación en cada proveedor y `\ValueError`.
- `AiRequestLogTest`: escritura, prompt anónimo, desactivado.

Ejecución: `make test-unit options="--filter AI"` (sin acceptance, que no cubre esta funcionalidad), y `make phpstan`. Antes de cerrar, `make test-parallel`. No hay escenarios Behat ni pruebas contra las APIs reales.

## Desviaciones y deuda

- `review.md` (misma carpeta): 7 hallazgos (B1–B7) corregidos y 1 consulta (Q1) resuelta; no queda nada abierto.
- Correcciones posteriores que difieren del plan original (`plan.md`): `oneOf` se renombra a `anyOf` y se normaliza `$defs`/`definitions`; `httpError` es una tercera fábrica de excepción y cambia el tipo de excepción de los errores HTTP también con Gemini; el timeout es 600 s con `HIGH` en ambos adaptadores (el plan decía 300 s).
- Cambios sin commitear en el árbol de trabajo en el momento de documentar (ficheros `Gemini*` retirados, `Ai*` nuevos sin seguimiento).
