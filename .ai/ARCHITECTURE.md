# Waste Management Application — Arquitectura y Convenciones

## Stack Principal
- **PHP 8.2+** con `declare(strict_types=1)` en todos los ficheros
- **Symfony 7.3** (framework, console, security, messenger, forms, twig, mailer)
- **Doctrine ORM 3.x** para persistencia
- **FrankenPHP** como runtime (contenedor Docker)
- **Bootstrap 5 + Stimulus.js** en el frontend (Symfony AssetMapper + SASS Bundle)
- **Symfony UX Live Components / Twig Components**

## Arquitectura: DDD + Hexagonal

El proyecto sigue **Domain-Driven Design con Arquitectura Hexagonal** estricta.

### Bounded Contexts (módulos bajo `src/`)
| Contexto | Propósito |
|----------|-----------|
| `Shared/` | Infraestructura compartida, base de aggregates, event bus, email |
| `Security/` | Usuarios, cuentas, autenticación, roles, auditoría, SSO |
| `Waste/` | Gestión de residuos, contenedores, mediciones, diarios |
| `Property/` | Propiedades/instalaciones, categorías, países, marcas, destinos |
| `DataExchange/` | Importación de datos desde hojas de cálculo |
| `FoodLoss/` | Acciones y categorías de servicio (gestión de pérdida de alimentos) |

### Estructura por Contexto
```
[Context]/
├── Domain/           # Lógica de negocio pura (sin dependencias externas)
│   ├── Model/        # Aggregates, Value Objects, Interfaces de repositorio
│   └── Event/        # Domain Events y suscriptores
├── Application/      # Casos de uso (servicios de aplicación)
│   └── [UseCase]/
│       ├── [UseCase]Service.php    # Handler (implementa CommandHandler)
│       ├── [UseCase]Request.php    # Command/Query DTO
│       └── [UseCase]Response.php  # Response DTO
└── Infrastructure/   # Adaptadores (implementaciones concretas)
    ├── Persistence/Doctrine/       # Repositorios Doctrine
    ├── Persistence/InMemory/       # Repositorios in-memory (para tests)
    ├── UI/Web/                     # Controladores Symfony (Twig)
    ├── UI/Api/                     # Controladores REST (JSON, JWT)
    ├── UI/Console/                 # Comandos de consola
    └── Symfony/DependencyInjection/services.yaml

```
La capa `Application/` se organiza por aggregate, no directamente por use case:
```
Application/
  [Aggregate]/
    [Aggregate]Authorizer.php       # Lógica de autorización del aggregate
    [UseCase]/
      [UseCase]Service.php
      [UseCase]Request.php
      [UseCase]Response.php
```

## Patrones de Diseño Clave

### Event Sourcing en Aggregates
```php
// El aggregate usa el trait AggregateRoot de Shared
$this->recordApplyAndPublish(new WasteRegistered(...));

// El trait proporciona: record(), apply(), publish()
// Los eventos se aplican mediante métodos: apply[EventName](Event $event)
```

### Command/Query (CQRS light)
- **Request**: objeto valor con los datos de entrada (Command/Query)
- **Response**: DTO de salida
- **Service**: handler que implementa `CommandHandler` con `__invoke(Request, User)`
- **CommandBus**: bus que enruta comandos a handlers

### Repository Pattern (Puertos y Adaptadores)
- **Puerto**: interfaz en `Domain/Model/[Aggregate]/[Aggregate]Repository.php`
- **Adaptadores**: `DoctrineXxxRepository` e `InMemoryXxxRepository`

### Autorización por Acción
```php
// Authorizer por aggregate con enum Action (List, Register, View, Edit, Remove)
$this->authorizer->denyUnlessCanDoThat($subject, Action::List, $user);
```

### Filter Objects
- Objetos `XxxFilter` encapsulan paginación, orden y criterios de búsqueda
- Se pasan a los repositorios en `findByFilter(XxxFilter $filter)`

### Multi-idioma
- Enum `Locale` (ES, EN, FR, DE, IT, TR)
- Interfaz `Translatable` + entidades de traducción `XxxTranslation`
- `LocaleGetter` obtiene el locale del contexto actual

## Testing

### Herramientas
- **PHPUnit** — Tests unitarios en `tests/Unit/`
- **Behat** — Tests de aceptación BDD en `tests/Acceptance/`
- **DAMA DoctrineTestBundle** — Aislamiento por transacción en tests de integración

### Patrones de Test
- **Mother Objects** (`XxxMother.php`) — Builders de datos de test
- **In-Memory Repositories** — Para tests unitarios aislados
- **Spy Subscribers** — `SpyDomainEventSubscriber` para verificar eventos de dominio
- **Behat Contexts**: `WebContext`, `ApiContext`, `SecurityContext`, `WasteContext`, etc.

### Entorno de Test
```
APP_ENV=test
DATABASE_URL="sqlite:///./var/data.db"
SYMFONY_DEPRECATIONS_HELPER="disabled=1"
```

## Comandos: siempre vía Makefile

**TODOS los comandos se lanzan usando `make`**, nunca directamente.

| Comando                     | Acción                                       |
|-----------------------------|----------------------------------------------|
| `make up`                   | Levantar contenedores Docker                 |
| `make down`                 | Parar contenedores                           |
| `make bash` / `make sh`     | Conectar al contenedor PHP                   |
| `make test`                 | Ejecutar todos los tests (acceptance + unit) |
| `make test-unit`            | Solo tests unitarios (PHPUnit)               |
| `make test-acceptance`      | Solo tests de aceptación (Behat)             |
| `make test-this test=<...>` | Ejecutar un test concreto                    |
| `make migrate`              | Ejecutar migraciones                         |
| `make migration`            | Crear nueva migración                        |
| `make phpstan`              | Análisis estático                            |
| `make phpstan c=<param>`    | Análisis estático con parametros             |
| `make cs`                   | Verificar estilo de código (dry-run)         |
| `make cs-fix`               | Corregir estilo de código                    |
| `make rector`               | Modernización de código                      |
| `make sf c=<cmd>`           | Ejecutar comando Symfony                     |
| `make cc`                   | Limpiar caché                                |
| `make translation`          | Extraer traducciones (ES + EN)               |
| `make develop`              | Compilar assets (SASS)                       |
| `make develop-watch`        | Compilar assets en modo watch                |
| `make entity`               | Crear entidad con maker bundle               |
| `make queues`               | Consumir mensajes async                      |

## Roles de Usuario
`SuperAdmin` > `Admin` > `Owner` > `User`

## Dependencias Notables
- `endroid/qr-code` — Generación de QR para contenedores
- `openspout/openspout` — Lectura/escritura Excel/CSV (DataExchange)
- `tecnickcom/tcpdf` + `iio/libmergepdf` — Generación y merge de PDFs
- `lexik/jwt-authentication-bundle` — Autenticación JWT para API
- `symfony/sendgrid-mailer` — Envío de emails vía SendGrid
- `nelmio/api-doc-bundle` — Documentación Swagger/OpenAPI
