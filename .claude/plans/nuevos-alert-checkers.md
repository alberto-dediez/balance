# Plan: Nuevos AlertCheckers

## Contexto

La app es Symfony 7.3, DDD + Hexagonal, bounded context `src/Waste/`.

El sistema de alertas ya funciona con 3 `AlertChecker` implementados:
- `NoMeasurementsAlertChecker` — propiedad sin registros DailyWaste en X días
- `ThresholdExceededAlertChecker` — valor de ayer > media histórica 30d × porcentaje
- `AbnormallyHighValueAlertChecker` — valor de ayer > media + N × desviación estándar

El orquestador es `CheckAlertsService` (Application/Alert/Check/), que recorre las cuentas, consulta su `AlertConfiguration` y ejecuta cada checker registrado como servicio etiquetado `app.alert_checker`.

---

## Piezas clave del sistema (leer antes de implementar)

- **Interfaz:** `src/Waste/Application/Alert/Check/Checker/AlertChecker.php`
- **AlertType enum:** `src/Waste/Domain/Model/Alert/AlertType.php`
- **Alert model:** `src/Waste/Domain/Model/Alert/Alert.php` — campos relevantes: `type`, `propertyId`, `wasteId`, `accountId`, `status`, `detectedAt`, `periodFrom`, `periodTo`, `value`, `threshold`
- **AlertConfiguration:** `src/Waste/Domain/Model/AlertConfiguration/AlertConfiguration.php` — método `isTypeEnabled(AlertType)` y `items()->getByType(AlertType)`
- **AlertConfigurationItem:** parámetros en array `parameters['clave']`
- **Checkers existentes de referencia:** `src/Waste/Application/Alert/Check/Checker/AbnormallyHighValueAlertChecker.php` y `NoMeasurementsAlertChecker.php`
- **Form types de parámetros:** `src/Waste/Infrastructure/UI/Web/AlertConfiguration/Form/Parameters/`
- **Registro DI:** `src/Waste/Infrastructure/Symfony/DependencyInjection/services.yaml` — tag `app.alert_checker`

---

## Evaluación de la lista de deseos

Estos ya están **cubiertos por checkers existentes** — no implementar:
- "Sin registros (propiedad)" → `NoMeasurementsAlertChecker`
- "Pico anómalo — media histórica / estadístico" → `ThresholdExceededAlertChecker` + `AbnormallyHighValueAlertChecker`
- "Desviación por tipo de residuo" → los checkers existentes ya agrupan por `wasteId` y emiten una alerta por tipo de residuo

Estos están **descartados** porque no encajan con el patrón de checker programado (batch diario):
- "Error de unidad / dato incoherente" → es validación de entrada; el checker llegaría tarde (día siguiente). "Kg imposibles" requiere límites de dominio no modelados.
- "Objetivo de reducción incumplido" → requiere un concepto de dominio nuevo ("meta de reducción") que no existe. Feature mayor separada.

---

## Nuevos checkers a implementar (4)

### 1. `FixedThresholdAlertChecker` — Umbral fijo absoluto

**AlertType:** `FixedThreshold`

**Lógica:**
- Para cada propiedad de la cuenta, obtener DailyWaste de ayer agrupados por `wasteId`
- Si el total de ayer para un `wasteId` supera `thresholdKg` → emitir alerta

**Parámetro:** `thresholdKg` (float, default: 500.0)

**Por qué es distinto a los existentes:** No necesita historial. Útil cuando el cliente conoce su límite de capacidad real en kg absolutos.

**Repositorios:** `DailyWasteRepository`, `PropertyRepository`, `AlertRepository`

---

### 2. `AbnormallyLowValueAlertChecker` — Registro anormalmente bajo

**AlertType:** `AbnormallyLow`

**Lógica:**
- Igual que `AbnormallyHighValueAlertChecker` pero invirtiendo la comparación:
  - Calcular media y stddev de los 30 días previos (excluyendo ayer) por `wasteId`
  - Si valor de ayer < (media − multiplier × stddev) **Y** valor de ayer > 0 → alerta
  - Excluir cero porque ya lo cubre `NoMeasurementsAlertChecker`

**Parámetro:** `deviationMultiplier` (float, default: 2.0)
- Puede reutilizarse el form type `AbnormallyHighParametersType` o crear un alias; misma estructura

**Repositorios:** `DailyWasteRepository`, `PropertyRepository`, `AlertRepository`

---

### 3. `UnusedContainerAlertChecker` — Contenedor sin uso

**AlertType:** `UnusedContainer`

**Lógica:**
- Para cada contenedor activo (`removed = false`) de la cuenta:
  - Consultar `MeasurementRepository` con `containerId` y rango de fechas `[hoy − unusedDays, hoy]`
  - Si no hay ninguna medición → emitir alerta con `propertyId` y `wasteId` del contenedor
- Verificar que no exista ya una alerta `New` para esa propiedad+waste+tipo antes de crear otra (patrón del resto de checkers)

**Parámetro:** `unusedDays` (int, default: 7)

**Repositorios:** `ContainerRepository`, `MeasurementRepository`, `AlertRepository`

**Nota:** `Alert` no tiene campo `containerId`; la alerta se identifica por `propertyId` + `wasteId` + `AlertType::UnusedContainer`. Si hay varios contenedores del mismo tipo en la misma propiedad, se emite una sola alerta (comportamiento aceptable).

---

### 4. `InactiveUserAlertChecker` — Usuario inactivo

**AlertType:** `InactiveUser`

**Lógica:**
- Obtener los usuarios operativos de la cuenta (buscar cómo se accede a `UserRepository` desde Security en el contexto Waste — ya se usa `AccountRepository` de Security en `CheckAlertsService`)
- Para cada usuario: consultar `MeasurementRepository` con `userId` y rango `[hoy − inactiveDays, hoy]`
- Si no ha registrado ninguna medición → emitir alerta a nivel de cuenta (`propertyId = null`, `wasteId = null`)
- Verificar duplicados: no emitir si ya hay alerta `New` para esa cuenta+tipo

**Parámetro:** `inactiveDays` (int, default: 7)

**Repositorios:** `UserRepository` (Security context), `MeasurementRepository`, `AlertRepository`

**Nota:** `MeasurementFilter` ya tiene campo `userId`. Ver cómo se inyecta `UserRepository` en otros servicios del bounded context Waste (si existe un precedente, seguirlo).

---

## Archivos a crear por checker (patrón × 4)

```
src/Waste/Application/Alert/Check/Checker/{Type}AlertChecker.php
src/Waste/Infrastructure/UI/Web/AlertConfiguration/Form/Parameters/{Type}ParametersType.php
tests/Unit/Waste/Application/Alert/Check/Checker/{Type}AlertCheckerTest.php
```

## Archivos compartidos a modificar

| Archivo | Cambio |
|---|---|
| `src/Waste/Domain/Model/Alert/AlertType.php` | Añadir 4 casos: `FixedThreshold`, `AbnormallyLow`, `UnusedContainer`, `InactiveUser` + sus `parametersFormType()` |
| `src/Waste/Infrastructure/Symfony/DependencyInjection/services.yaml` | Tag `app.alert_checker` para cada nuevo checker |

---

## Patrón de implementación (copiar de `AbnormallyHighValueAlertChecker`)

```php
public function type(): AlertType
{
    return AlertType::YourNewType;
}

public function check(AccountId $accountId, AlertConfiguration $config, \DateTimeImmutable $checkDate): array
{
    $item = $config->items()->getByType(AlertType::YourNewType);
    $param = $item?->parameters()['yourParam'] ?? self::DEFAULT_PARAM;

    $alerts = [];
    // 1. Obtener properties del account
    // 2. Para cada property, obtener datos (DailyWaste u otros)
    // 3. Calcular/comparar
    // 4. Verificar no hay alerta previa New: $this->alertRepository->findByFilter(new AlertFilter(...))
    // 5. $alert = new Alert(AlertId::generate()); $alert->detect(...); $alerts[] = $alert;

    return $alerts;
}
```

---

## Verificación

```bash
make test-unit                                       # Tests unitarios nuevos checkers
make phpstan                                         # Sin errores estáticos nivel 8
make cs-fix                                          # Estilo de código
make test                                            # Todos los tests
# Verificar manualmente en UI: los 4 nuevos tipos deben aparecer en el formulario de AlertConfiguration
```
