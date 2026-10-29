# Transferencia de crédito a varias cuentas — manual técnico

- **Plan:** `plan.md` (misma carpeta) · **Implementado:** 2026-10-29 · **Bounded Contexts:** `Balance` (con `Security` para el selector de cuentas y el registro de eventos)

## Qué hace y por qué

La pantalla admin «Transferir crédito» admitía una sola cuenta de destino. Ahora acepta una lista: las toneladas indicadas, o todo el remanente transferible si el campo se deja vacío, se reparten **a partes iguales con 6 decimales** entre las cuentas elegidas. Cada cuenta recibe su propio lote del mismo serial (número de lote correlativo). La operación es de todo o nada: se ejecuta dentro de un único `CreditReservationLock::withLock`.

Al diseñarlo apareció un error previo en el **tramo del serial** asignado al lote recibido: el lote recibido siempre terminaba en `purchasedTonnes`, de modo que a partir de la segunda transferencia desde el mismo lote los destinos recibían más toneladas que las transferidas (100 t, 40 t a A y luego 20 t a B dejaba a B con 60 t). Se corrige con una columna nueva `upperOffsetTonnes` (lo ajeno por encima del tramo del lote) y un comando de consola que detecta y repara los lotes ya afectados. Las decisiones y sus descartes están en la tabla «Decisiones tomadas» de `plan.md`.

Modelo mental: cada lote ocupa un tramo `[offset, P − upperOffset − voided]` del serial `[0, P]`. Las asignaciones a aportaciones consumen desde abajo (`offset`), las cesiones se hacen desde arriba (`ceilingTonnes()`).

## Mapa de piezas

| Capa | Fichero | Rol | Estado |
|---|---|---|---|
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchase.php` | `upperOffsetTonnes`, `ceilingTonnes()`, `receive(..., upperOffsetTonnes)`, `correctRange()`, `availableTonnes()` | modificado |
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchaseReceived.php` | campo y payload `upperOffsetTonnes` (por defecto `0.0` al reconstruir eventos antiguos) | modificado |
| Dominio | `src/Balance/Domain/Model/CreditPurchase/CreditPurchaseRangeCorrected.php` | evento de reparación del tramo | nuevo |
| Aplicación | `src/Balance/Application/CreditPurchase/Transfer/TransferCreditPurchaseService.php` | reparto, importes, bloqueo, tramo correcto | modificado |
| Aplicación | `.../Transfer/TransferCreditPurchaseRequest.php` / `TransferCreditPurchaseResponse.php` | lista de destinos / lotes y totales | modificado |
| Aplicación | `.../Transfer/TransferredLot.php` | resultado por destino | nuevo |
| Aplicación | `src/Balance/Application/CreditRange/Check/*` (`CheckCreditRangesService`, `Request`, `Response`, `CreditRangeFinding`, `CreditRangeFindingCode`) | chequeo y reparación de tramos | nuevo |
| Persistencia | `src/Balance/Infrastructure/Persistence/Doctrine/Mapping/CreditPurchase.CreditPurchase.orm.xml` | columna `upper_offset_tonnes` | modificado |
| Persistencia | `.../Repository/DoctrineCreditPurchaseRepository.php` | sumas SQL descuentan `upperOffsetTonnes` | modificado |
| Persistencia | `migrations/Version20261029123515.php` | migración | nuevo |
| Infraestructura | `src/Balance/Infrastructure/Symfony/Form/TransferCreditPurchaseType.php` | selector múltiple, opción `source_account_id` | modificado |
| Infraestructura | `src/Security/Infrastructure/Symfony/Form/Type/AccountIdType.php` | opción `excluded_account_id` | modificado |
| Infraestructura | `src/Balance/Infrastructure/UI/Web/Admin/CreditPurchase/TransferController.php` | flashes y opción del form | modificado |
| Infraestructura | `src/Balance/Infrastructure/UI/Console/CreditPurchase/CheckCreditRangesCommand.php` | `balance:credit-ranges:check` | nuevo |
| Vistas | `templates/balance/admin/credits/transfer.html.twig`, `templates/balance/credits/_bar.html.twig`, `list.html.twig`, `_allocations.html.twig` | campo múltiple; barra con «Otros lotes del serial» | modificado |
| i18n | `translations/messages+intl-icu.{es,en,tr}.xlf` | claves nuevas | modificado |

## Modelo de dominio

`CreditPurchase` (`CreditPurchase.php`):

- `availableTonnes() = max(0, purchased − offset − upperOffset − voided)` si está validado (`:185-197`).
- `ceilingTonnes() = purchased − upperOffset − voided`: desde donde cede la siguiente transferencia (`:194-197`).
- `receive(...)` lleva el nuevo parámetro `upperOffsetTonnes` y lo propaga a `CreditPurchaseReceived`; `applyCreditPurchaseReceived` lo asigna y `requestedTonnes = receivedTonnes()` (`purchased − offset − upperOffset`, `CreditPurchaseReceived.php:74-77`).
- `correctRange(offset, upperOffset)` (`:346-368`): lanza `NonTransferableCreditPurchaseException` si el lote no está `Validated` o `lotNumber <= 1`; no hace nada si los valores coinciden dentro de `CreditBalance::TONNE_TOLERANCE`; si no, publica `CreditPurchaseRangeCorrected` (payload: `creditPurchaseId`, `previousOffsetTonnes`, `previousUpperOffsetTonnes`, `offsetTonnes`, `upperOffsetTonnes`). `applyCreditPurchaseRangeCorrected` recalcula `requestedTonnes = round(purchased − offset − upperOffset, 6)`.

Transferir `t` desde el lote S al nuevo D (`TransferCreditPurchaseService::transfer`, `:225-271`):

```
ceiling       = S.ceilingTonnes()            (antes de ceder)
D.offset      = round(ceiling − t, 6)
D.upperOffset = round(P − ceiling, 6)
S.voided     += t
```

Invariante: los tramos propios de todos los lotes de un serial son disjuntos y su unión es `[0, P]`.

## Flujo de una operación

Ruta `admin_credit_purchases_transfer` (`/admin/credits/{creditPurchaseId}/transfer`, `TransferController.php:28`).

1. **Controlador** (`:43-62`): `denyAccessUnlessGranted(Edit)`; si el transferible es ≤ tolerancia, redirige al listado con aviso. Crea el form con `source_account_id` = cuenta del lote de origen.
2. **Servicio** `__invoke` (`TransferCreditPurchaseService.php:46`):
   1. Carga el origen y `denyUnlessCanDoThat(Edit)`.
   2. Exige cuenta, proyecto, referencia y `PaidByBioscore`; si no, `NonTransferableCreditPurchaseException` (`:60-67`).
   3. `destinationAccountIds()` (`:147-170`): descarta repetidas; si alguna es la cuenta de origen, o la lista queda vacía, `NonTransferableCreditPurchaseException`.
   4. `split()` previo (sin consumir nada) para fallar pronto (`:71`).
   5. Adhiere cada destino con `SelectProjectService` (`:73-78`).
   6. `withLock(origen, proyecto)` (`:81-106`): repite `split()` con el estado bloqueado, calcula importes y llama a `transfer()` por destino en el orden recibido; el último recibe `lastShare`. Devuelve `{split, transfers}`.
   7. Fuera del bloqueo, por destino: `ReallocateUnderallocatedContributionsService` y construcción de `TransferredLot` con `absorbedTonnes` (`:108-122`).
   8. Respuesta con totales (`:130-139`).
3. **Controlador** (`:75-112`): flash `success` (texto de una cuenta si `count(lots) === 1`, texto de reparto si no) y flashes `info` si `retainedTonnes > 0.0` o `lastAccountExtraTonnes > 0.0`. Redirige a `admin_credit_purchases`. Las `NonTransferableCreditPurchaseException` / `InsufficientTransferableCreditException` se muestran como error de formulario (`:67-73`).

### Reparto (`split`, `:172-197`)

- `transferable = TransferableCredit::forPurchase(origen)`; si ≤ tolerancia → `InsufficientTransferableCreditException`.
- `total = round(min(tonnes ?? transferable, transferable), 6)` (recorta sin avisar).
- Aritmética entera en micro-toneladas: `shareMicro = intdiv(micro, n)`, `remainderMicro = micro − shareMicro × n`. Si `shareMicro = 0` → `InsufficientTransferableCreditException`.
- Si `total < transferable − tolerancia` (el origen conserva libre): `lastShare = share`, `retained = remainder`, `emptied = false`. Si no: `lastShare = share + remainder`, `retained = 0`, `emptied = true`.

### Importes (`amounts`, `:204-222`)

Desviación respecto al plan, introducida en la revisión (hallazgo M1): el importe no se calcula por destino con `round(€/t × parte, 2)`, sino en **céntimos enteros**. `cents = round(min(round(€/t × toneladas_totales, 2), importe_origen) × 100)`; cada destino recibe `intdiv(cents, n)` y, solo si `emptied`, el último suma el resto de céntimos. Así la suma nunca supera el importe del origen (que no puede quedar negativo). Devuelve `null` si el origen no tiene importe o precio.

## Autorización y ámbito

- Transferencia: `CreditPurchaseAuthorizer`, `Action::Edit` sobre el lote (servicio `:54`, controlador `:45`). Un Owner recibe 403 (feature `credit_purchase_transfer.feature`, «As an owner I cannot transfer a credit lot»).
- Chequeo de tramos: `Action::Admin` (Admin o SuperAdmin) sobre un `CreditPurchase` vacío (`CheckCreditRangesService.php:43-47`).
- Origen excluido del selector: `AccountIdType` recibe `excluded_account_id` y filtra `choices` en un normalizador (`AccountIdType.php`); el valor por defecto `null` deja el resto de usos intactos. Si la cuenta de origen se inyecta igualmente, el `ChoiceType` la rechaza antes de llegar al servicio (la excepción del servicio queda como defensa en profundidad).

## Persistencia y migración

- Columna `credit_purchases.upper_offset_tonnes` (`float`, `nullable=false`) en `CreditPurchase.CreditPurchase.orm.xml`.
- `DoctrineCreditPurchaseRepository` (balance y total comprado por proyecto) descuenta `upperOffsetTonnes`. `InMemoryCreditPurchaseRepository` usa `availableTonnes()` y no cambia.
- Migración aplicada por el usuario: `migrations/Version20261029123515.php`, que ejecuta `ALTER TABLE credit_purchases ADD upper_offset_tonnes DOUBLE PRECISION NOT NULL` **sin `DEFAULT 0`**. `migration.sql` de la carpeta del plan sí lo proponía (`DEFAULT 0 NOT NULL AFTER offset_tonnes`). Comprobar en entornos con filas existentes que el motor las rellena con 0.
- No hay backfill SQL: el payload de `recorded_events` se serializa como `array` PHP. La corrección va por consola.

## Configuración y operación

Comando (`CheckCreditRangesCommand.php`):

```
make sf c="balance:credit-ranges:check <username>"            # diagnóstico, no modifica
make sf c="balance:credit-ranges:check <username> --repair"   # corrige
```

Pasos recomendados tras desplegar la migración: primero sin `--repair`, revisar la tabla `Check | Lot | Detail | Repaired` y luego con `--repair`.

Hallazgos (`CreditRangeFindingCode.php`):

| Código | Significado |
|---|---|
| `range_mismatch` | El lote tiene `offset` / `upperOffset` distintos de los reconstruidos. Con `--repair` se corrige con `correctRange()`. |
| `overallocated_after_repair` | Con el tramo exacto, lo asignado al lote supera su disponible. Al reparar, `upperOffset` se recorta para que el libre sea 0. Nunca se marca como reparado: se resuelve a mano. |
| `missing_transfer_event` | Lote > 1 sin transferencia registrada que lo haya originado (o con origen no simulable). |
| `overceded_range` | Una transferencia cedió por debajo del suelo del lote de origen (cedió más que su tramo exacto). Se marca como reparado cuando el lote destino queda con su tramo corregido. Añadido tras la revisión (A3). |

Algoritmo (`CheckCreditRangesService.php`):

1. `transfers()` (`:194-222`): carga los eventos `creditPurchaseTransferred` del contexto `'balance'` (constante `CONTEXT`) con `limit: 0` y `orderWay = 'ASC'` (el constructor de `RecordedEventFilter` fuerza `DESC`), los reconstruye con `rebuild()` y los ordena por `occurredOn` y, en empate, por el número de lote del destino (la BD no garantiza orden; hallazgo A2).
2. `simulate()` (`:96-146`): por cada origen mantiene `ceiling`, `floor` y `ceded`. El lote 1 se inicializa con `purchased − upperOffset`; un origen con lote > 1 sin simulación previo es `missing_transfer_event`. Para cada evento: `top = max(floor, ceiling − ceded)`, destino esperado `offset = max(floor, top − t)`, `upperOffset = P − top`. Si `top − t < floor`, emite `overceded_range`.
3. `checkLot()` (`:229-280`): compara con tolerancia 0,000001. Si la asignación al lote (`allocatedTonnesByPurchase`) supera el disponible esperado, recorta `upperOffset` y añade `overallocated_after_repair`. Con `--repair` llama a `correctRange()` y persiste.
4. Los lotes validados con `lotNumber > 1` que no aparecen en la simulación se reportan como `missing_transfer_event`.

Salida y código de retorno (`CheckCreditRangesCommand.php:41-100`): sin hallazgos → `SUCCESS` con «N received lot(s) checked, every range is correct.»; usuario inexistente → `FAILURE`. Sin `--repair`, `SUCCESS` solo si todo lo reportado es un `overceded_range` ya reparado; con `--repair`, `SUCCESS` si no queda ningún `range_mismatch` ni `overceded_range` sin reparar. Un `overallocated_after_repair` no afecta al código de salida con `--repair` (limitación aceptada, hallazgo B5).

No hay cambios en `services.yaml`, colas ni `CommandSchedule`; el comando es manual.

## Interfaz

- Formulario (`TransferCreditPurchaseType.php`): `destinationAccountIds` (`AccountIdType`, `multiple`, obligatorio, `Assert\Count(min: 1)`), `tonnes` (`NumberType`, escala 4, `Positive`, opcional) y botón `saveAndReturn`.
- Plantilla `transfer.html.twig`: tarjeta «Lote de origen», tarjeta «Transferencia» (`form_row(form.destinationAccountIds)`, `form_row(form.tonnes)`), `ActionBar`.
- Acceso: menú Proyectos → «Créditos de CO2» (`NodeLister.php:208-210`), icono de transferir en el listado (`templates/balance/admin/credits/list.html.twig:70`, solo lotes transferibles) y botón «Transferir crédito» en la ficha (`view.html.twig:10`).
- Barra de crédito (`_bar.html.twig`): macro `segment`; franja «Otros lotes del serial» para `offsetTonnes` (delante) y `upperOffsetTonnes` (detrás), y franja «Transferido» solo para `voidedTonnes`. La leyenda «Transferido» aparece si `voidedTonnes > 0`; «Otros lotes del serial» si `offset + upperOffset > 0`. Cambio posterior a la implementación inicial (hallazgo B3) que sustituye al comportamiento del plan.
- `_allocations.html.twig:30-49` sin cambios: «Toneladas recibidas» sale de `requestedTonnes`, «Toneladas del serial» de `purchasedTonnes` y «Transferido» solo si `isTransferred` (`voidedTonnes` > tolerancia).

## Cómo extenderlo

- **Cambiar la regla de reparto** (p. ej. proporcional): sustituye `split()` y `amounts()`; el contrato es `share`, `lastShare`, `retained`, `emptied`. Mantén el cálculo en enteros y el recorte del tramo en `transfer()`.
- **Crear lotes desde otro flujo**: nunca instancies `CreditPurchase` con un tramo calculado a mano; usa `ceilingTonnes()` y pasa `offsetTonnes` y `upperOffsetTonnes` a `receive()`, o los tramos dejarán de ser disjuntos.
- **Nuevo hallazgo en el chequeo**: añade el caso a `CreditRangeFindingCode`, emítelo desde `CheckCreditRangesService` y decide su efecto en el código de salida del comando.
- **Trampas**: `RecordedEventFilter` fuerza `orderWay = 'DESC'` en el constructor; hay que reasignarlo tras construirlo. Los eventos `CreditPurchaseReceived` antiguos no traen `upperOffsetTonnes` y se reconstruyen con `0.0`. Cualquier suma SQL de crédito disponible debe restar `upperOffsetTonnes`.

## Tests

- `tests/Unit/Balance/Application/CreditPurchase/Transfer/TransferCreditPurchaseServiceTest.php` (27 tests): reparto, restos, importes, duplicados, lista vacía, origen entre destinos, `absorbedTonnes`, adhesión al proyecto, transferencias sucesivas.
- `tests/Unit/Balance/Application/CreditRange/Check/CheckCreditRangesServiceTest.php` (13 tests): serial correcto, reparación, sobreasignación, lote sin evento, diagnóstico sin modificar, permisos.
- `tests/Unit/Balance/Domain/Model/CreditPurchase/CreditPurchaseTest.php`, `CreditPurchaseRepositoryTest.php`, `CreditPurchaseMother.php`; `tests/Unit/Balance/Application/CreditPurchase/Alerts/CheckCreditAlertsServiceTest.php` (alertas sobre lo recibido).
- Behat: `tests/Acceptance/Balance/credit_purchase_transfer.feature` (reparto, resto, toneladas indicadas, transferencias sucesivas, rango de aportaciones, selector, alertas, ficha del lote), `credit_purchase_allocations.feature` (adaptado a `destinationAccountIds`) y `credit_ranges_check.feature` (dos transferencias sucesivas, reparto entre varias cuentas, usuario inexistente).
- Ejecutar: `make test-unit`, `make test-acceptance` o `make test-parallel` (el `Makefile` no define `test-this`). Para una sola clase: `make test-unit options="--filter TransferCreditPurchaseServiceTest"` y `make test-acceptance options="tests/Acceptance/Balance/credit_purchase_transfer.feature"`.
- Hueco: el tramo erróneo, la reparación, la sobreasignación y el lote sin evento no tienen escenario Behat (no hay `Given` para sembrar tramos antiguos); solo cobertura unitaria.

## Desviaciones y deuda

Ver `plan.md` (`## Implementación`) y `review.md`. Resumen:

- Flashes `info` con `> 0.0` en lugar de `> TONNE_TOLERANCE`; flash de una sola cuenta con texto propio (B1).
- El cierre de `withLock` devuelve `{split, transfers}`.
- Importes en céntimos enteros (M1), `overceded_range` y orden por número de lote (A2, A3), barra con «Otros lotes del serial» (B3): correcciones de la revisión, ya aplicadas.
- Abiertos sin acción por decisión del usuario: B4 (claves de traducción antiguas siguen en los catálogos) y B5 (`overallocated_after_repair` no cambia el código de salida con `--repair`).
- Deuda detectada y no corregida: `src/Security/Domain/Event/RecordEventSubscriber.php:13` importa un `UserMother` de tests; `Makefile` sin `test-this`.
- Solo existen catálogos ES, EN y TR.
- La migración aplicada no incluye `DEFAULT 0` (ver «Persistencia y migración»).
