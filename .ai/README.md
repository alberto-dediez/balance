# Project AI Guidelines: Waste

This document outlines the core mandates, engineering standards, and operational guidelines for AI assistants (Claude, Gemini, etc.) working on the Waste project.

## Core Mandates

### 1. Command Execution via Makefile
Always use the provided `Makefile` for executing project-related commands. This ensures consistency across different environments (local vs. docker). **Never** call `docker compose exec php ...` or `php bin/console ...` directly.

| Category | Command | Description |
|----------|---------|-------------|
| **Docker** | `make up` / `make down` | Start / Stop containers |
| | `make sh` / `make bash` | Access PHP container shell |
| **Testing** | `make test` | Run all tests (unit + acceptance) |
| | `make test-unit` | Run unit tests (PHPUnit) |
| | `make test-acceptance` | Run acceptance tests (Behat) |
| | `make test-this test=path/to/test` | Run a specific test |
| **Quality** | `make phpstan` | Run PHPStan for static analysis |
| | `make cs` | Check coding standards (dry-run) |
| | `make cs-fix` | Automatically fix coding standard violations |
| | `make rector` | Run Rector for automated refactoring |
| **Database** | `make migration` | Create a new migration |
| | `make migrate` | Execute migrations |
| **Symfony** | `make sf c="command:name"` | Run any Symfony console command |
| | `make cc` | Clear Symfony cache |
| **Other** | `make translation` | Extract translations (ES + EN) |
| | `make develop-watch` | Compile SASS assets (watch mode) |

### 2. Coding Standards & Style
The project follows a Hexagonal Architecture (Port & Adapters) with Domain-Driven Design (DDD) principles.

- **Style Precedence:** The coding style and commenting patterns found in `src/Shared` and `src/Security` Bounded Contexts (BCs) MUST be treated as the primary reference. Mirror their structure and conventions in other parts of the codebase.
- **Strict Typing:** Every PHP file must begin with `declare(strict_types=1);`.
- **Import Classes:** Always import classes with `use` statements. Never use fully qualified class names inline (e.g. `new App\Foo\Bar()` or `App\Foo\Bar::CONST`). Exception: global namespace classes (`\DateTimeImmutable`, `\Exception`, `\Throwable`, etc.) may keep the leading backslash without a `use` statement.
- **PHPDoc:** Use PHPDoc comments primarily when native type hints are insufficient, particularly for specifying types within arrays (e.g., `/** @var User[] $users */`).
- **DDD Patterns:** Use Aggregate Roots, Value Objects, Domain Events, and Repositories as demonstrated in the `Shared` and `Security` BCs.
    - Entities should use the `AggregateRoot` trait from `App\Shared\Domain\Model\AggregateRoot`.
    - Prefer constructor promotion for property definition in entities and value objects.

### 3. Architecture
The project follows **Domain-Driven Design with Arquitectura Hexagonal** estricta using **Symfony 7.3**.

#### Bounded Contexts (`src/`)
| Context | Purpose / Responsibility |
|---------|---------------------------|
| `Shared/` | Shared infrastructure, AggregateRoot base, EventBus, Email, etc. |
| `Security/` | Users, Accounts, Authentication, Roles, Audit log, SSO. |
| `Waste/` | Waste management, Containers, Measurements, Diaries. |
| `Property/` | Properties, Categories, Countries, Brands, Destinations. |
| `DataExchange/` | Data importation pipeline from spreadsheets. |
| `FoodLoss/` | Food loss management: Actions, Service categories. |

#### Layer Structure (Standard per context)
```
Domain/
  Model/[Aggregate]/
    [Aggregate].php              ← Aggregate root (uses AggregateRoot trait)
    [Aggregate]Id.php            ← UUID value object
    [Aggregate]Repository.php    ← Interface (port)
    [Aggregate]Registered.php    ← Domain event
    [Aggregate]Filter.php        ← Pagination/search criteria
  Event/                        ← Domain event subscribers

Application/[Aggregate]/
  [Aggregate]Authorizer.php      ← Authorization logic for the aggregate
  [UseCase]/
    [UseCase]Service.php         ← CommandHandler, __invoke(Request, User)
    [UseCase]Request.php         ← Input DTO
    [UseCase]Response.php        ← Output DTO

Infrastructure/
  Persistence/Doctrine/          ← DoctrineXxxRepository (production)
  Persistence/InMemory/          ← InMemoryXxxRepository (tests)
  UI/Web/                        ← Symfony controllers (Twig)
  UI/Api/                        ← REST controllers (JWT, JSON)
  UI/Console/                    ← Console commands
  Symfony/DependencyInjection/services.yaml
```

### 4. Key Patterns & Guidelines
- **Aggregate Event Sourcing:** Aggregates use the `AggregateRoot` trait. State is recorded with `recordApplyAndPublish(new Event(...))` and reconstituted via `apply[EventName](Event $event)`.
- **Authorization:** Per-action authorization is enforced at the start of every service using the `[Aggregate]Authorizer`. Actions: `List`, `Register`, `View`, `Edit`, `Remove`.
- **Repository Queries:** Always use a `XxxFilter` value object for search, sorting, and pagination.
- **Multi-language:** Implement `Translatable` and `XxxTranslation` entities for translatable fields. Supported locales: ES, EN, FR, DE, IT, TR. Use `LocaleGetter`.
- **User Roles:** `SuperAdmin` > `Admin` > `Owner` > `User`.

## Workflow & Verification

### General Workflow
1. **Research:** Use `grep_search` to find existing patterns, especially in `Shared` or `Security`.
2. **Strategy:** Formulate a plan following DDD and Hexagonal patterns.
3. **Execution:** Apply targeted changes. Use `make cs-fix` after changes.
4. **Validation:** Always run `make phpstan` and `make test` before considering a task complete.

### Testing Conventions
- **Unit tests** (`tests/Unit/`) use `InMemoryXxxRepository` and `XxxMother` builder classes — no Doctrine, no HTTP.
- **Acceptance tests** (`tests/Acceptance/`) are Behat `.feature` files with matching `XxxContext.php` step definitions; they run against SQLite.
- `SpyDomainEventSubscriber` is used to assert domain events were published.
- Mother objects provide named factory methods: `UserMother::adminUser()`, `WasteMother::fromNameAndId(...)`.

### PHPStan Error Resolution Workflow
When tasked with resolving PHPStan errors (aiming for Level 8), adhere to these strict guidelines:
1. **Preserve Code Structure:** Avoid deleting code, methods, properties, or variables to resolve errors. (Exception: unnecessary `assert()` in `tests/`).
2. **No Scripted Edits:** Do NOT use external scripts to perform automated code modifications. Use native tools only.
3. **Reference Implementations:** Mirror patterns in `src/Shared` and `src/Security`.
4. **Validation Pipeline:** Verify each fix with `make phpstan` and ensure all affected tests pass.

---
*For more detailed architectural notes, see [.ai/ARCHITECTURE.md](ARCHITECTURE.md).*
