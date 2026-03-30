# Project Guidelines: Waste

This document outlines the core mandates and engineering standards for the Waste project. Adhere to these instructions for all tasks.

## Core Mandates

### 1. Command Execution via Makefile
Always use the provided `Makefile` for executing project-related commands. This ensures consistency across different environments (local vs. docker).

- **Testing:**
    - `make test`: Run all tests (unit + acceptance).
    - `make test-unit`: Run unit tests.
    - `make test-acceptance`: Run acceptance tests (Behat).
    - `make test-this test=path/to/test`: Run a specific test.
- **Static Analysis & Quality:**
    - `make phpstan`: Run PHPStan for static analysis.
    - `make cs`: Check coding standards (dry-run).
    - `make cs-fix`: Automatically fix coding standard violations.
    - `make rector`: Run Rector for automated refactoring.
- **Database & Migrations:**
    - `make migration`: Create a new migration.
    - `make migrate`: Execute migrations.
- **Symfony Commands:**
    - `make sf c="command:name"`: Run any Symfony console command.
- **Docker:**
    - `make up`: Start containers.
    - `make down`: Stop containers.
    - `make sh`: Access the PHP container shell.

### 2. Coding Standards & Style
The project follows a Hexagonal Architecture (Port & Adapters) with Domain-Driven Design (DDD) principles.

- **Style Precedence:** The coding style and commenting patterns found in `src/Shared` and `src/Security` Bounded Contexts (BCs) MUST be treated as the primary reference. Mirror their structure and conventions in other parts of the codebase.
- **Strict Typing:** Every PHP file must begin with `declare(strict_types=1);`.
- **PHPDoc:** Use PHPDoc comments primarily when native type hints are insufficient, particularly for specifying types within arrays (e.g., `/** @var User[] $users */`).
- **DDD Patterns:** Use Aggregate Roots, Value Objects, Domain Events, and Repositories as demonstrated in the `Shared` and `Security` BCs.
    - Entities should use the `AggregateRoot` trait from `App\Shared\Domain\Model\AggregateRoot`.
    - Prefer constructor promotion for property definition in entities and value objects.
- **PHPStan Fixes:** When resolving PHPStan errors, prioritize maintaining the existing architectural integrity and stylistic consistency of the `Shared` and `Security` contexts. Do not introduce "quick fixes" that compromise these standards.

### 3. Architecture
- **Infrastructure:** Framework-specific code (Symfony) should reside in the `Infrastructure` layer of each Bounded Context.
- **Domain:** Pure business logic, entities, and value objects must be kept in the `Domain` layer, free of infrastructure dependencies.
- **Application:** Command handlers, query handlers, and services that orchestrate the domain should be in the `Application` layer.

## Workflow
1. **Research:** Use `grep_search` to find existing patterns, especially in `Shared` or `Security`.
2. **Execution:** Use `make cs-fix` after making changes to ensure style compliance.
3. **Validation:** Always run `make phpstan` and `make test` before considering a task complete.

## PHPStan Error Resolution Workflow
When tasked with resolving PHPStan errors, you must adhere to the following strict guidelines:
1. **Preserve Code Structure:** Avoid deleting code, methods, properties, or variables to resolve errors. The ONLY exception is within the `tests/` directory, where unnecessary `assert()` statements may be removed if they are the cause of the error or are redundant.
2. **No Scripted Edits:** DO NOT create temporary bash, python, php, or any other scripts to perform automated code modifications. You must use the provided native toolset (e.g., `replace`, `write_file`) to modify files directly.
3. **Reference Implementations:** Always look at `src/Shared` and `src/Security` Bounded Contexts for the correct typing, PHPDoc, and DDD patterns before applying fixes.
4. **Validation Pipeline:** After applying a fix for a specific file or error, you MUST verify that:
    - The specific PHPStan error is resolved (`make phpstan` or checking specific files).
    - All affected tests continue to pass (e.g., `make test-unit` or `make test-this test=...`).
