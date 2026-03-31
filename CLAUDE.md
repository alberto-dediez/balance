# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run through `make`. Never call `docker compose exec php ...` or `php bin/console ...` directly.

```bash
make up                        # Start Docker containers
make down                      # Stop containers
make bash / make sh            # Shell into the PHP container

make test                      # Run all tests (acceptance + unit)
make test-unit                 # PHPUnit unit tests only
make test-acceptance           # Behat acceptance tests only
make test-this test="--filter FooTest"  # Single PHPUnit test/filter

make migrate                   # Run pending migrations
make migration                 # Generate a new migration
make cc                        # Clear Symfony cache
make sf c="about"              # Any bin/console command

make phpstan                   # Static analysis
make cs                        # Check code style (dry-run)
make cs-fix                    # Fix code style
make rector                    # Automated refactoring

make translation               # Extract translations (ES + EN)
make develop-watch             # Compile SASS assets (watch mode)
```

## Architecture

Read ARCHITECTURE.md

**DDD + Hexagonal Architecture** with Symfony 7.3.

### Bounded Contexts (`src/`)

| Context | Responsibility |
|---------|---------------|
| `Shared/` | AggregateRoot trait, DomainEvent base, CommandBus, email infrastructure |
| `Security/` | User, Account aggregates; roles, authentication, SSO, audit log |
| `Waste/` | Waste, Container, Measurement, DailyWaste aggregates |
| `Property/` | Property, Category, Country, Brand, Destination aggregates |
| `DataExchange/` | Importation aggregate; spreadsheet import pipeline |
| `FoodLoss/` | Action, ServiceCategory aggregates (food loss management) |

### Layer Structure (same in every context)

```
Domain/
  Model/[Aggregate]/
    [Aggregate].php              ← Aggregate root (uses AggregateRoot trait)
    [Aggregate]Id.php            ← UUID value object
    [Aggregate]Repository.php   ← Interface (port)
    [Aggregate]Registered.php   ← Domain event
    [Aggregate]Filter.php       ← Pagination/search criteria
  Event/                        ← Domain event subscribers

Application/[Aggregate]/
  [Aggregate]Authorizer.php     ← Authorization logic for the aggregate
  [UseCase]/
    [UseCase]Service.php        ← CommandHandler, __invoke(Request, User)
    [UseCase]Request.php        ← Input DTO
    [UseCase]Response.php       ← Output DTO

Infrastructure/
  Persistence/Doctrine/         ← DoctrineXxxRepository (production)
  Persistence/InMemory/         ← InMemoryXxxRepository (tests)
  UI/Web/                       ← Symfony controllers (Twig)
  UI/Api/                       ← REST controllers (JWT, JSON)
  UI/Console/                   ← Console commands
  Symfony/DependencyInjection/services.yaml
```

### Key Patterns

**Aggregate with event sourcing** — aggregates use `AggregateRoot` trait from `Shared`:
```php
$this->recordApplyAndPublish(new WasteRegistered(...));
// State is reconstituted by: applyWasteRegistered(WasteRegistered $event)
```

**Application services** — one service per use case, always receives `User` as second argument:
```php
public function __invoke(RegisterWasteRequest $request, User $user): RegisterWasteResponse
```

**Authorization** — per-action, called at the start of every service:
```php
$this->authorizer->denyUnlessCanDoThat($subject, Action::List, $user);
// Actions: List, Register, View, Edit, Remove
```

**Repository queries** use a `XxxFilter` value object for pagination/sorting/search, never raw parameters.

### Testing Conventions

- **Unit tests** (`tests/Unit/`) use `InMemoryXxxRepository` and `XxxMother` builder classes — no Doctrine, no HTTP.
- **Acceptance tests** (`tests/Acceptance/`) are Behat `.feature` files with matching `XxxContext.php` step definitions; they run against SQLite.
- `SpyDomainEventSubscriber` is used to assert domain events were published.
- Mother objects provide named factory methods: `UserMother::adminUser()`, `WasteMother::fromNameAndId(...)`.

### Multi-language

Entities that need translations implement `Translatable` and have a companion `XxxTranslation` entity. The `Locale` enum covers ES, EN, FR, DE, IT, TR.

### User Roles

`SuperAdmin` > `Admin` > `Owner` > `User`

### Routes

Routes are split by context and type in `config/routes.yaml` (e.g. `waste-web`, `waste-api`). Controllers use PHP 8 attributes for route definitions and support `{_locale}`.
