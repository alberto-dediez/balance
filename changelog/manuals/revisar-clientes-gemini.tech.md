# Revisar los clientes Gemini tras el aviso de deprecación de parámetros de Google — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-15 · **Bounded Contexts:** `Shared` (infraestructura `AI/Gemini`) · **Migración:** no

## Qué hace y por qué

Google avisó de que los próximos modelos Gemini rechazarán con `400 INVALID_ARGUMENT` las peticiones a `generateContent` que lleven `thinking_budget` o los parámetros de muestreo `temperature`, `top_p` y `top_k`, y de que un `thinking_level` no soportado por el modelo también da `400`. El cliente común de biohydra enviaba siempre `temperature` y traducía el nivel de razonamiento con una regla fija para los modelos `-pro`, que no encaja con los modelos 3.1-pro, 3.7+ ni 4.

El cambio deja el cliente listo para esos modelos sin romper los actuales (`gemini-2.5-flash` por defecto en `.env:147-149`):

- `temperature` desaparece del cuerpo de la petición, del log y del contrato `GeminiPrompt` (decisión «`temperature()` en el contrato `GeminiPrompt`» de `plan.md`). `topP` y `topK` nunca se enviaron.
- Los modelos `gemini-2.5*` siguen recibiendo `thinkingBudget` (decisión «`thinkingBudget` para modelos 2.5»: en 2.5 `thinkingLevel` da error).
- Los modelos `gemini-3*` o superiores reciben `thinkingLevel`, degradado solo cuando la familia del modelo no soporta el nivel pedido (decisión «Mapeo de niveles de razonamiento»).

La migración a la Interactions API queda fuera de alcance: `generateContent` sigue soportado.

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiPrompt.php` | Contrato de un prompt: `text()`, `responseSchema()`, `modelTier()`, `thinkingLevel()` (ya sin `temperature()`) | Modificado |
| Infraestructura | `src/Shared/Infrastructure/AI/Gemini/GeminiHttpClient.php` | Construye la petición sin `temperature`; nuevo mapeo de `thinkingLevel` por familia (`thinkingLevelName()`, `supportsMinimal()`, `supportsMedium()`); log sin `temperature` | Modificado |
| Tests | `tests/Unit/Shared/Infrastructure/AI/Gemini/GeminiHttpClientTest.php` | 7 casos nuevos en `thinkingConfigs()`, test `testDoesNotSendSamplingParameters()`, prompt anónimo sin `temperature()` | Modificado |
| Infraestructura | `GeminiClient.php`, `InMemoryGeminiClient.php`, `GeminiModelTier.php`, `GeminiThinkingLevel.php`, `GeminiResponseException.php` (misma carpeta) | No manejan los parámetros afectados | Sin cambios |
| Configuración | `src/Shared/Infrastructure/Symfony/DependencyInjection/services.yaml:11-15,83-93`, `.env:145-151` | Wiring y variables `GEMINI_*` | Sin cambios |

## Contrato `GeminiPrompt`

`src/Shared/Infrastructure/AI/Gemini/GeminiPrompt.php:7-17` declara cuatro métodos:

| Método | Uso en el cliente |
|---|---|
| `text(): string` | Única `part` de `contents` (`GeminiHttpClient.php:33`) |
| `responseSchema(): array` | `generationConfig.responseSchema` (`GeminiHttpClient.php:37`) |
| `modelTier(): GeminiModelTier` | Elige `GEMINI_MODEL_LOW`/`MEDIUM`/`HIGH` (`model()`, `GeminiHttpClient.php:74-81`) |
| `thinkingLevel(): GeminiThinkingLevel` | Se traduce a `thinkingConfig` según el modelo (`thinkingConfig()`, `GeminiHttpClient.php:86-110`) |

Retirar `temperature()` de la interfaz no rompe a las clases que la sigan implementando: un método extra no es error para PHP ni para PHPStan. En los proyectos derivados (reporting, events, food-loss) sus prompts compilarán conservando `temperature()` como código muerto; hay que buscarlos con `grep` al mezclar biohydra (hallazgo D1 de `review-fix.md`; el plan afirma lo contrario en *Riesgos*).

## Flujo de una petición

`GeminiHttpClient::send()` (`src/Shared/Infrastructure/AI/Gemini/GeminiHttpClient.php:27-72`):

1. Resuelve el modelo por `GeminiModelTier` (`:29`).
2. Construye el cuerpo (`:31-40`): `contents` con el texto y `generationConfig` con `responseMimeType: application/json`, `responseSchema` y el resultado de `thinkingConfig($model, $prompt->thinkingLevel())` expandido. No hay `temperature`, `topP` ni `topK`.
3. Con `GEMINI_LOG_ENABLED`, escribe el log de petición (`:42-45`).
4. `POST` a `…/v1beta/models/{model}:generateContent` con cabecera `x-goog-api-key` y `timeout` de 300 s (`:47-51`).
5. Si el motivo de fin no es `STOP`, lanza `GeminiResponseException::incomplete()`; si el texto no decodifica a array, `GeminiResponseException::invalidJson()` (`:63-69`).

### Traducción del nivel de razonamiento

`thinkingConfig()` (`GeminiHttpClient.php:86-110`) extrae `major` y `minor` con `/^gemini-(\d+)(?:\.(\d+))?/` (minor ausente = 0) y marca `isPro` si el nombre contiene `-pro`:

- **`major >= 3`** (`:96-98`): `['thinkingConfig' => ['thinkingLevel' => …]]` con `thinkingLevelName()` (`:112-120`): `MINIMAL` → `minimal` si el modelo lo soporta, si no `low`; `MEDIUM` → `medium` si lo soporta, si no `high`; `LOW` y `HIGH` se envían tal cual.
  - `supportsMinimal()` (`:122-125`): `!isPro && major === 3 && minor <= 6`.
  - `supportsMedium()` (`:127-130`): `!isPro || major > 3 || minor >= 1`.
- **`2.5`** (`:100-107`): `thinkingBudget` = `MINIMAL` 0 (128 en `-pro`), `LOW` 1024, `MEDIUM` 8192, `HIGH` 24576. Nunca `thinkingLevel`.
- **Cualquier otro** (p. ej. `gemini-2.0-flash` o un nombre sin prefijo `gemini-`): sin `thinkingConfig`; el modelo usa su nivel por defecto.

Resultado por familia:

| Familia | `MINIMAL` | `LOW` | `MEDIUM` | `HIGH` |
|---|---|---|---|---|
| `gemini-3*` … `gemini-3.6*`, no pro (p. ej. `gemini-3-flash-preview`, `gemini-3.5-flash-lite`) | `minimal` | `low` | `medium` | `high` |
| `gemini-3.7*` y posteriores no pro, incluido `gemini-4*` | `low` | `low` | `medium` | `high` |
| `gemini-3-pro*` (3.0) | `low` | `low` | `high` | `high` |
| `gemini-3.1-pro*` y cualquier `-pro` posterior | `low` | `low` | `medium` | `high` |
| `gemini-2.5*` | budget 0 (128 pro) | 1024 | 8192 | 24576 |
| Otros | — | — | — | — |

La tabla de soporte viene de la documentación de Google del 2026-10-15 (citada en *Hechos verificados* de `plan.md`).

### Log

`writeLog()` (`GeminiHttpClient.php:137-165`) guarda en `var/gemini-logs/` (`services.yaml:90`) un JSON por llamada con `model`, `model_tier`, `prompt_class`, `request.text`, `request.thinkingLevel` (nombre del caso del enum, no el valor traducido), `request.responseSchema`, `raw_response` y `parsed_result`. Ya no registra `temperature`.

## Configuración y operación

- Variables sin cambios: `GEMINI_API_KEY`, `GEMINI_MODEL_LOW`, `GEMINI_MODEL_MEDIUM`, `GEMINI_MODEL_HIGH` (todas `gemini-2.5-flash` en `.env:146-149`) y `GEMINI_LOG_ENABLED` (`.env:150`).
- En `test`, `GeminiClient` lo sirve `InMemoryGeminiClient` (`#[When(env: 'test')]`), que no construye petición alguna.
- En biohydra no hay ningún `GeminiPrompt` implementado: el cliente no tiene consumidores aquí y el cambio llega a reporting, waste, food-loss y events al mezclar biohydra.

## Cómo extenderlo

- **Nuevo prompt**: implementar `GeminiPrompt` con los cuatro métodos. No declarar `temperature()`: el cliente no la lee.
- **Nuevo modelo configurado en `.env.local`**: antes de apuntar un `GEMINI_MODEL_*` a un modelo nuevo, comprobar en la documentación de Google qué niveles soporta y que el modelo cae en la fila correcta de la tabla anterior. Un nivel no soportado devuelve `400 INVALID_ARGUMENT`.
- **Nueva familia con otros niveles**: ajustar `supportsMinimal()`/`supportsMedium()` (`GeminiHttpClient.php:122-130`) y añadir el caso a `thinkingConfigs()` del test. Si una familia dejara de soportar otro nivel (p. ej. `low`), hay que añadir un helper análogo y su rama en `thinkingLevelName()`.
- **Trampa**: `isPro` se calcula con `str_contains($model, '-pro')`, así que cualquier nombre con `-pro` en cualquier posición cuenta como pro.
- **Ficheros compartidos con reporting**: `GeminiPrompt.php`, `GeminiHttpClient.php` y `GeminiHttpClientTest.php` deben quedar idénticos en biohydra y reporting (*Restricciones* de `plan.md`); un cambio aquí se replica allí.

## Tests

- **Unitarios** (`tests/Unit/Shared/Infrastructure/AI/Gemini/GeminiHttpClientTest.php`):
  - `testSendsTheThinkingConfigOfTheModel` con el data provider `thinkingConfigs()` (`:39-56`, 15 casos): Gemini 3 flash/pro, 3.1 pro (`MEDIUM` → `medium`, `MINIMAL` → `low`), 3.5 flash lite (`minimal`), 3.8 flash (`MINIMAL` → `low`, `MEDIUM` → `medium`), 4 argon (`MINIMAL` → `low`), 2.5 flash/pro (`thinkingBudget`, incluido `HIGH` → 24576) y 2.0 flash sin config. También comprueba la URL del modelo.
  - `testDoesNotSendSamplingParameters` (`:58-70`): `generationConfig` sin `temperature`, `topP` ni `topK`.
  - `testReturnsTheParsedJson`, `testFailsWhenTheResponseIsCutOff`, `testFailsWhenThePromptIsBlocked`, `testFailsWhenTheJsonIsInvalid`: sin cambios.
- **Aceptación**: ninguno (no hay consumidores en biohydra y en `test` se usa `InMemoryGeminiClient`).
- **Ejecutar**: `make test-unit options="--filter GeminiHttpClientTest"`.
- **Huecos**: no hay test del contenido del log (`writeLog()`), ni casos `LOW`/`HIGH` en modelos ≥ 3 (se envían sin traducir).

## Desviaciones y deuda

Detalle en `## Implementación` de `plan.md`, `review.md` y `review-fix.md`:

- Sin desviaciones respecto al plan.
- B1 (aplicado): el manual técnico de `integrar-reporting` (`changelog/manuals/integrar-reporting.tech.md` y `.claude/plans/done/2026-10-15-integrar-reporting/tech-manual.md`) se actualizó al contrato de cuatro métodos y a la degradación por familia.
- Q1 (aplazado): la copia de los tres ficheros al checkout de reporting está pendiente hasta el merge; mientras tanto reporting sigue enviando `temperature` y el siguiente merge puede reintroducirla o dar conflicto.
- D1 (asumido): retirar `temperature()` del contrato no hace que PHPStan señale los prompts de los proyectos derivados; quedará como código muerto hasta que se retire a mano.
- events y food-loss usan una versión anterior del cliente que también envía `temperature`; fallarán con los modelos nuevos hasta que mezclen biohydra.
