# Plan de Implementación: Sistema de Alertas de Residuos

## Contexto y objetivo

El proyecto es una aplicación Symfony 7.3 con DDD + Arquitectura Hexagonal, organizada en bounded contexts bajo `src/`. El bounded context principal relevante es `src/Waste/`.

Se necesita implementar un sistema de alertas que detecte anomalías en los datos de mediciones y residuos diarios. Los tipos de alerta iniciales son:
- **Sin registros en X días** (`NoMeasurements`): alerta si una propiedad no ha registrado datos en N días
- **Valor anormalmente alto** (`AbnormallyHigh`): alerta si un valor supera la media + N desviaciones típicas
- **Umbral respecto a media** (`ThresholdExceeded`): alerta si un valor supera el X% de la media de los últimos 30 días

Las alertas:
- Se detectan mediante un comando de consola ejecutado diariamente (cron externo)
- Se muestran en un panel web filtrable
- Se envían como resumen mensual por email a los usuarios con rol Owner de cada cuenta
- Sus parámetros son **configurables por cuenta** (`AlertConfiguration`)
- Se crea **una alerta por ejecución** del checker (sin deduplicación)

---

## Modelos de referencia que seguir

Antes de implementar, leer estos ficheros para entender los patrones exactos del proyecto:

| Propósito | Fichero de referencia |
|-----------|----------------------|
| Aggregate root | `src/Waste/Domain/Model/Measurement/Measurement.php` |
| Value object UUID | `src/Waste/Domain/Model/Measurement/MeasurementId.php` |
| Domain event | `src/Waste/Domain/Model/Measurement/MeasurementRegistered.php` |
| Repository interface | `src/Waste/Domain/Model/Measurement/MeasurementRepository.php` |
| Filter object | `src/Waste/Domain/Model/DailyWaste/DailyWasteFilter.php` |
| Doctrine repository | `src/Waste/Infrastructure/Persistence/Doctrine/Repository/DoctrineDailyWasteRepository.php` |
| Doctrine type | `src/Waste/Infrastructure/Persistence/Doctrine/Type/MeasurementIdType.php` |
| InMemory repository | `src/Waste/Infrastructure/Persistence/InMemory/Repository/InMemoryDailyWasteRepository.php` |
| Application service | `src/Waste/Application/DailyWaste/List/ListDailyWastesService.php` |
| Authorizer | `src/Waste/Application/DailyWaste/DailyWasteAuthorizer.php` |
| Console command | `src/Waste/Infrastructure/UI/Console/DailyWaste/RecalculateCommand.php` |
| Web list controller | `src/Waste/Infrastructure/UI/Web/DailyWaste/ListController.php` |
| Email sender interface | `src/Shared/Domain/Model/Email/EmailSender.php` |
| Email sender impl | `src/Shared/Infrastructure/Email/SymfonyEmailSender.php` |
| Mailer subscriber | `src/DataExchange/Infrastructure/Notification/ImportationExecutedMailer.php` |
| Email template base | `templates/shared/layout/email.html.twig` |
| List template | `templates/waste/daily-wastes/list.html.twig` |
| Services YAML | `src/Waste/Infrastructure/Symfony/DependencyInjection/services.yaml` |

---

## Comandos de proyecto

```bash
make phpstan          # análisis estático nivel 8
make test-unit        # PHPUnit
make test-acceptance  # Behat
make migration        # genera migración Doctrine
make migrate          # aplica migraciones
make cc               # limpia caché Symfony
make sf c="..."       # ejecuta bin/console
```

---

## Estructura de ficheros a crear

### 1. Domain Layer

#### `src/Waste/Domain/Model/Alert/`

```
AlertId.php                    # UUID value object (igual que MeasurementId)
AlertType.php                  # enum: NoMeasurements, AbnormallyHigh, ThresholdExceeded
AlertStatus.php                # enum: New, Acknowledged
Alert.php                      # Aggregate root
AlertDetected.php              # Domain event
AlertAcknowledged.php          # Domain event
AlertFilter.php                # Filter para queries
AlertRepository.php            # Interface del repositorio
```

**`Alert.php`** — campos:

```php
private AlertId $id
private AlertType $type
private PropertyId $propertyId
private ?WasteId $wasteId
private AccountId $accountId
private AlertStatus $status  // = AlertStatus::New
private \DateTimeImmutable $detectedAt
private \DateTimeImmutable $periodFrom
private \DateTimeImmutable $periodTo
private ?float $value         // valor que disparó la alerta
private ?float $threshold     // umbral superado
```

Métodos del agregado:
- `detect(PropertyId, ?WasteId, AccountId, \DateTimeImmutable $detectedAt, \DateTimeImmutable $from, \DateTimeImmutable $to, ?float $value, ?float $threshold): void` — publica `AlertDetected`
- `acknowledge(): void` — publica `AlertAcknowledged`

**`AlertFilter.php`** — campos opcionales:
```php
?AccountId $accountId
?PropertyId $propertyId
?WasteId $wasteId
?AlertType $type
?AlertStatus $status
?\DateTimeImmutable $from
?\DateTimeImmutable $to
int $limit = 50
string $orderBy = 'detectedAt'
string $orderWay = 'DESC'
int $page = 1
```

**`AlertRepository.php`**:
```php
public function add(Alert $alert): void;
public function save(Alert $alert): void;
public function find(AlertId $id): ?Alert;
public function findByFilter(AlertFilter $filter): array;
public function totalResultsByFilter(AlertFilter $filter): int;
```

#### `src/Waste/Domain/Model/AlertConfiguration/`

```
AlertConfigurationId.php       # UUID value object
AlertConfiguration.php         # Aggregate root
AlertConfigurationRegistered.php  # Domain event
AlertConfigurationUpdated.php     # Domain event
AlertConfigurationRepository.php  # Interface
```

**`AlertConfiguration.php`** — campos:

```php
private AlertConfigurationId $id
private AccountId $accountId
private AlertType $type
private bool $enabled
private int $noMeasurementsDays        // default: 7
private float $deviationMultiplier     // default: 2.0  (para AbnormallyHigh)
private float $thresholdPercentage     // default: 150.0 (para ThresholdExceeded)
```

**`AlertConfigurationRepository.php`**:
```php
public function add(AlertConfiguration $config): void;
public function save(AlertConfiguration $config): void;
public function find(AlertConfigurationId $id): ?AlertConfiguration;
public function findByAccount(AccountId $accountId): array;
public function findByAccountAndType(AccountId $accountId, AlertType $type): ?AlertConfiguration;
```

---

### 2. Infrastructure / Persistence

#### Doctrine Types

`src/Waste/Infrastructure/Persistence/Doctrine/Type/`
- `AlertIdType.php` — igual que `MeasurementIdType.php`
- `AlertConfigurationIdType.php` — igual que `MeasurementIdType.php`

Registrar ambos en `config/packages/doctrine.yaml` (sección `dbal.types`), siguiendo el patrón de los tipos existentes.

#### ORM Mappings (XML)

`src/Waste/Infrastructure/Persistence/Doctrine/ORM/`

**`Alert.orm.xml`**:
```xml
<entity name="App\Waste\Domain\Model\Alert\Alert" table="alerts">
    <id name="id" type="alert_id" />
    <field name="type" type="string" enumType="App\Waste\Domain\Model\Alert\AlertType" />
    <field name="status" type="string" enumType="App\Waste\Domain\Model\Alert\AlertStatus" />
    <field name="propertyId" type="property_id" column="property_id" />
    <field name="wasteId" type="waste_id" column="waste_id" nullable="true" />
    <field name="accountId" type="account_id" column="account_id" />
    <field name="detectedAt" type="datetime_immutable" column="detected_at" />
    <field name="periodFrom" type="datetime_immutable" column="period_from" />
    <field name="periodTo" type="datetime_immutable" column="period_to" />
    <field name="value" type="float" nullable="true" />
    <field name="threshold" type="float" nullable="true" />
</entity>
```

**`AlertConfiguration.orm.xml`**:
```xml
<entity name="App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration" table="alert_configurations">
    <id name="id" type="alert_configuration_id" />
    <field name="accountId" type="account_id" column="account_id" />
    <field name="type" type="string" enumType="App\Waste\Domain\Model\Alert\AlertType" />
    <field name="enabled" type="boolean" />
    <field name="noMeasurementsDays" type="integer" column="no_measurements_days" />
    <field name="deviationMultiplier" type="float" column="deviation_multiplier" />
    <field name="thresholdPercentage" type="float" column="threshold_percentage" />
    <unique-constraints>
        <unique-constraint name="uq_alert_config_account_type" columns="account_id,type" />
    </unique-constraints>
</entity>
```

#### Doctrine Repositories

`src/Waste/Infrastructure/Persistence/Doctrine/Repository/`
- `DoctrineAlertRepository.php` — implementa `AlertRepository`, extiende `ServiceEntityRepository`
- `DoctrineAlertConfigurationRepository.php` — implementa `AlertConfigurationRepository`

Seguir exactamente el patrón de `DoctrineDailyWasteRepository.php`.

#### InMemory Repositories (para tests)

`src/Waste/Infrastructure/Persistence/InMemory/Repository/`
- `InMemoryAlertRepository.php`
- `InMemoryAlertConfigurationRepository.php`

---

### 3. Application Layer

#### `src/Waste/Application/Alert/`

```
AlertAuthorizer.php

Check/
  CheckAlertsService.php
  CheckAlertsRequest.php         # ?AccountId $accountId, \DateTimeImmutable $checkDate
  CheckAlertsResponse.php
  Checker/
    AlertChecker.php             # Interface
    NoMeasurementsAlertChecker.php
    AbnormallyHighValueAlertChecker.php
    ThresholdExceededAlertChecker.php

List/
  ListAlertsService.php
  ListAlertsRequest.php
  ListAlertsResponse.php

Acknowledge/
  AcknowledgeAlertService.php
  AcknowledgeAlertRequest.php    # AlertId $alertId
  AcknowledgeAlertResponse.php

MonthlyReport/
  MonthlyReportService.php
  MonthlyReportRequest.php       # \DateTimeImmutable $month (1st day of target month)
  MonthlyReportResponse.php      # array de alertas agrupadas por cuenta/propiedad
```

**`AlertChecker.php`** (interface):
```php
interface AlertChecker {
    public function type(): AlertType;
    /** @return Alert[] */
    public function check(
        AccountId $accountId,
        AlertConfiguration $config,
        \DateTimeImmutable $checkDate
    ): array;
}
```

**Lógica de los checkers** (todos inyectan `DailyWasteRepository`, `ContainerRepository` o `PropertyRepository` según necesiten):

- **`NoMeasurementsAlertChecker`**: Obtiene todas las propiedades activas de la cuenta. Para cada propiedad, busca `DailyWaste` de los últimos `$config->noMeasurementsDays()` días. Si no hay ninguno, crea un `Alert` (via `new Alert(...)` + `->detect(...)`).
  
- **`AbnormallyHighValueAlertChecker`**: Para cada combinación propiedad+residuo, obtiene los últimos 30 días de `DailyWaste`. Calcula media y desviación típica. Si el valor de `$checkDate - 1 day` supera `media + multiplier*stddev`, crea alerta.

- **`ThresholdExceededAlertChecker`**: Para cada combinación propiedad+residuo, obtiene los últimos 30 días (excluyendo ayer). Calcula media. Si el valor de ayer supera `media * (thresholdPercentage/100)`, crea alerta.

**`CheckAlertsService.php`**:
```php
public function __invoke(CheckAlertsRequest $request, User $user): CheckAlertsResponse
{
    // Si $request->accountId() es null, obtener todas las cuentas activas
    // Para cada cuenta: cargar sus AlertConfiguration (solo las enabled)
    // Para cada config: ejecutar el checker correspondiente
    // Persistir todos los Alert[] resultantes via AlertRepository::add()
}
```

Los checkers se inyectan como `iterable $checkers` (tagged service `app.alert_checker`).

#### `src/Waste/Application/AlertConfiguration/`

```
AlertConfigurationAuthorizer.php

Register/
  RegisterAlertConfigurationService.php
  RegisterAlertConfigurationRequest.php
  RegisterAlertConfigurationResponse.php

Update/
  UpdateAlertConfigurationService.php
  UpdateAlertConfigurationRequest.php
  UpdateAlertConfigurationResponse.php

List/
  ListAlertConfigurationsService.php
  ListAlertConfigurationsRequest.php
  ListAlertConfigurationsResponse.php
```

---

### 4. Infrastructure / UI

#### Console Commands

`src/Waste/Infrastructure/UI/Console/Alert/`

**`CheckAlertsCommand.php`** — `waste:alerts:check`:
```php
#[AsCommand(name: 'waste:alerts:check')]
class CheckAlertsCommand extends Command {
    // Invoca CheckAlertsService con fecha actual
    // Opción --account-id para ejecutar solo una cuenta
}
```

**`MonthlyReportCommand.php`** — `waste:alerts:monthly-report`:
```php
#[AsCommand(name: 'waste:alerts:monthly-report')]
class MonthlyReportCommand extends Command {
    // Opción --month=YYYY-MM (default: mes anterior)
    // Invoca MonthlyReportService, luego AlertMonthlyReportMailer
}
```

#### Web Controllers

**Panel de alertas**: `src/Waste/Infrastructure/UI/Web/Alert/ListController.php`
- Ruta: `/{_locale}/alerts` (nombre: `alerts`)
- Sigue patrón de `DailyWaste/ListController.php`
- Permite filtrar por: tipo, estado, propiedad, fecha desde/hasta
- Incluye botón "Marcar como leída" que llama a `AcknowledgeAlertService`

**Configuración de alertas**: `src/Waste/Infrastructure/UI/Web/AlertConfiguration/`
- `ListController.php` — ruta `/{_locale}/alert-configurations`
- `RegisterController.php` — ruta `/{_locale}/alert-configurations/register`
- `EditController.php` — ruta `/{_locale}/alert-configurations/edit/{alertConfigurationId}`

#### Notificación email

**`src/Waste/Infrastructure/Notification/AlertMonthlyReportMailer.php`**:
- NO es un subscriber de evento de dominio
- Es un servicio inyectable llamado desde `MonthlyReportCommand`
- Obtiene usuarios Owner de la cuenta via `UserRepository` + filtro por rol
- Envía un email por cada cuenta que tenga alertas en el mes
- Usa `EmailSender::send(EmailRecipient, subject, template, context)`
- Template: `@waste/notification/alerts-monthly-report.email.html.twig`

---

### 5. Templates

```
templates/waste/alerts/
└── list.html.twig              # Extiende @shared/layout/list.html.twig
                                # Columnas: Tipo | Propiedad | Residuo | Valor | Umbral | Detectada | Estado | Acción

templates/waste/alert-configurations/
├── list.html.twig              # Extiende @shared/layout/list.html.twig
├── register.html.twig          # Extiende @shared/layout/register.html.twig
└── edit.html.twig              # Extiende @shared/layout/edit.html.twig

templates/waste/notification/
└── alerts-monthly-report.email.html.twig  # Extiende @shared/layout/email.html.twig
                                           # Resumen del mes: tabla de alertas agrupadas por propiedad
```

---

### 6. Registrar tipos Doctrine y servicios tagged

En `config/packages/doctrine.yaml` (sección `dbal.types`):
```yaml
alert_id: App\Waste\Infrastructure\Persistence\Doctrine\Type\AlertIdType
alert_configuration_id: App\Waste\Infrastructure\Persistence\Doctrine\Type\AlertConfigurationIdType
```

En `src/Waste/Infrastructure/Symfony/DependencyInjection/services.yaml`:
```yaml
# Tag para los checkers
App\Waste\Application\Alert\Check\Checker\NoMeasurementsAlertChecker:
    tags: ['app.alert_checker']
App\Waste\Application\Alert\Check\Checker\AbnormallyHighValueAlertChecker:
    tags: ['app.alert_checker']
App\Waste\Application\Alert\Check\Checker\ThresholdExceededAlertChecker:
    tags: ['app.alert_checker']

App\Waste\Application\Alert\Check\CheckAlertsService:
    arguments:
        $checkers: !tagged_iterator app.alert_checker
```

---

### 7. Añadir enlace al menú de navegación

Buscar el template del menú lateral (probablemente en `templates/shared/`) y añadir:
- Enlace a `/alerts` (panel de alertas)
- Enlace a `/alert-configurations` (solo para roles Admin/Owner)

---

## Orden de implementación recomendado

> **IMPORTANTE**: Los pasos 1 y 2 deben ejecutarse usando la skill `/create-entity` del proyecto.
> Esta skill genera automáticamente todo el scaffolding (Domain, Application, Infrastructure, tests) siguiendo las convenciones exactas del proyecto.
> Invocar una vez por aggregate: primero `Alert`, luego `AlertConfiguration`.

1. **`/create-entity` para `Alert`** — indicar bounded context `Waste`, campos según la sección "Domain Layer" de este plan. La skill genera: aggregate, value objects, domain events, repository interface + Doctrine + InMemory, ORM mapping, Doctrine type, use cases CRUD base y tests.
2. **`/create-entity` para `AlertConfiguration`** — ídem para el segundo aggregate.
3. Ajustar los aggregates generados para añadir los enums `AlertType` y `AlertStatus`, y los campos específicos que la skill no cubra (ver sección "Domain Layer").
4. `make migration && make migrate`
5. Application checkers (`AlertChecker` interface + 3 implementaciones en `src/Waste/Application/Alert/Check/Checker/`)
6. Application use cases adicionales no generados por la skill: `Check`, `Acknowledge`, `MonthlyReport` para Alert
7. Console commands (`waste:alerts:check`, `waste:alerts:monthly-report`)
8. `AlertMonthlyReportMailer` + template email
9. Web controllers + templates (panel de alertas + CRUD configuraciones) — si la skill los generó, revisar y ajustar
10. Tag de servicios en `services.yaml`
11. Enlace en menú

---

## Verificación final

```bash
make phpstan                                    # sin errores estáticos
make test-unit                                  # tests unitarios
make test-acceptance                            # tests de aceptación
make sf c="waste:alerts:check"                  # ejecución manual de checkers
make sf c="waste:alerts:monthly-report --month=2026-05"  # email del mes anterior
```
