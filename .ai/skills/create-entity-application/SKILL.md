---
name: create-entity-application
description: Creates the Application layer of a new AggregateRoot entity: Authorizer, Register/Update/Remove/List use cases (services, requests, responses) and all related unit tests. Step 2 of 3 in the split create-entity workflow. Assumes the Domain layer has already been created by create-entity-domain.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make phpstan:*), Bash(ls:*)
---

# Create Entity — Application Layer (Step 2 of 3)

Creates the Application layer and its unit tests for an existing AggregateRoot entity whose Domain layer has already been scaffolded.

This skill is **Step 2** of the split workflow:
- Step 1 — `create-entity-domain` (Domain model, repositories, tests)
- **Step 2 — `create-entity-application`** ← you are here
- Step 3 — `create-entity-infrastructure` (forms, controllers, templates, acceptance tests)

## Input Format

The user must provide (or confirm from the previous step):
1. **Entity name** (PascalCase, e.g., `Allergen`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Fields** with types (same as step 1)
4. **Soft delete** (yes/no)
5. **Translations** (yes/no)
6. **Access Role** (Admin / Owner-User / Both) — determines Authorizer logic

## Naming Conventions

Given entity name `{Entity}` in context `{Context}`:

| Element | Name Pattern |
|---------|-------------|
| Authorizer | `{Entity}Authorizer` |
| Register Service | `Register{Entity}Service` |
| Update Service | `Update{Entity}Service` |
| Remove Service | `Remove{Entity}Service` |
| List Service | `List{Entities}Service` (pluralized) |
| Request DTOs | `Register{Entity}Request`, `Update{Entity}Request`, `Remove{Entity}Request`, `List{Entities}Request` |
| Response DTOs | `Register{Entity}Response`, `Update{Entity}Response`, `Remove{Entity}Response`, `List{Entities}Response` |

## Directory Structure

```
src/{Context}/
  Application/{Entity}/
    {Entity}Authorizer.php
    Register/
      Register{Entity}Request.php
      Register{Entity}Response.php
      Register{Entity}Service.php
    Update/
      Update{Entity}Request.php
      Update{Entity}Response.php
      Update{Entity}Service.php
    Remove/
      Remove{Entity}Request.php
      Remove{Entity}Response.php
      Remove{Entity}Service.php
    List/
      List{Entities}Request.php
      List{Entities}Response.php
      List{Entities}Service.php

tests/
  Unit/{Context}/
    Application/{Entity}/
      Register/Register{Entity}ServiceTest.php
      Update/Update{Entity}ServiceTest.php
      Remove/Remove{Entity}ServiceTest.php
      List/List{Entities}ServiceTest.php
```

## File Templates

### 1. Authorizer

#### {Entity}Authorizer.php

Adapt the `canList` / `canEdit` logic according to the Access Role:
- **Admin**: only SuperAdmin and Admin may access
- **Owner-User**: Owner and User may access (accountId check if needed)
- **Both**: Admin for the admin controllers; Owner/User for the user-facing controllers

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class {Entity}Authorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        // Admin access
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        // Owner/User access (only if entity belongs to an account or is user-level)
        return $user->hasRole(Role::Owner) || $user->hasRole(Role::User);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin) || $user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        // For Owner/User: check if they own the resource (e.g. same accountId)
        // return $subject->accountId()->equals($user->accountId());
        return $user->hasRole(Role::Owner) || $user->hasRole(Role::User);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof {Entity};
    }
}
```

### 2. Register Use Case

#### Register{Entity}Request.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Register;

use App\Shared\Application\CommandBus\Command;
// import field types as needed

class Register{Entity}Request implements Command
{
    public function __construct(
        // All user-editable fields with defaults
        public string $name = '',
    ) {}
}
```

#### Register{Entity}Response.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Register;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Application\CommandBus\CommandResponse;

class Register{Entity}Response implements CommandResponse
{
    public function __construct(
        public {Entity}Id ${entity}Id,
        // echo back user-visible fields
        public string $name,
    ) {}
}
```

#### Register{Entity}Service.php — WITHOUT translations

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Register;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class Register{Entity}Service implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private {Entity}Repository ${entity}Repository,
        private {Entity}Authorizer $authorizer,
    ) {}

    public function __invoke(Register{Entity}Request $request, User $user): Register{Entity}Response
    {
        $id = {Entity}Id::generate();

        $this->authorizer->denyUnlessCanDoThat(new {Entity}(id: $id), SecurityAction::List, $user);

        ${entity} = new {Entity}($id);
        ${entity}->register(
            $request->name,
        );

        $this->{entity}Repository->add(${entity});

        return new Register{Entity}Response(
            {entity}Id: ${entity}->id(),
            name: ${entity}->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof Register{Entity}Request;
    }
}
```

#### Register{Entity}Service.php — WITH translations

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Register;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class Register{Entity}Service implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private {Entity}Repository ${entity}Repository,
        private {Entity}Authorizer $authorizer,
    ) {}

    public function __invoke(Register{Entity}Request $request, User $user): Register{Entity}Response
    {
        $id = {Entity}Id::generate();

        $this->authorizer->denyUnlessCanDoThat(new {Entity}(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        ${entity} = new {Entity}($id);
        ${entity}->register(
            $request->name,
            // other fields...
            $isDefaultLocale ? null : $locale,
        );

        $this->{entity}Repository->add(${entity});

        return new Register{Entity}Response(
            {entity}Id: ${entity}->id(),
            name: ${entity}->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof Register{Entity}Request;
    }
}
```

### 3. Update Use Case

#### Update{Entity}Request.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Update;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Application\CommandBus\Command;

class Update{Entity}Request implements Command
{
    public function __construct(
        public {Entity}Id ${entity}Id,
        // All editable fields
        public string $name,
    ) {}
}
```

#### Update{Entity}Response.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Update;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Application\CommandBus\CommandResponse;

class Update{Entity}Response implements CommandResponse
{
    public function __construct(
        public {Entity}Id ${entity}Id,
        public string $name,
    ) {}
}
```

#### Update{Entity}Service.php — WITHOUT translations

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Update;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class Update{Entity}Service implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private {Entity}Repository ${entity}Repository,
        private {Entity}Authorizer $authorizer,
    ) {}

    public function __invoke(Update{Entity}Request $request, User $user): Update{Entity}Response
    {
        ${entity} = $this->{entity}Repository->find($request->{entity}Id);

        if (null == ${entity}) {
            throw new \InvalidArgumentException('{Entity} not found');
        }

        $this->authorizer->denyUnlessCanDoThat(${entity}, SecurityAction::Edit, $user);

        ${entity}->update(
            name: $request->name,
        );

        $this->{entity}Repository->add(${entity});

        return new Update{Entity}Response(
            {entity}Id: ${entity}->id(),
            name: ${entity}->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof Update{Entity}Request;
    }
}
```

#### Update{Entity}Service.php — WITH translations

Same as without, but add locale handling:

```php
        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        ${entity}->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );
```

And add `use App\Shared\Domain\Model\Locale\LocaleGetter;`

### 4. Remove Use Case

#### Remove{Entity}Request.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Remove;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Application\CommandBus\Command;

class Remove{Entity}Request implements Command
{
    public function __construct(public {Entity}Id ${entity}Id) {}
}
```

#### Remove{Entity}Response.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Remove;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Application\CommandBus\CommandResponse;

class Remove{Entity}Response implements CommandResponse
{
    public function __construct(public {Entity}Id ${entity}Id) {}
}
```

#### Remove{Entity}Service.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\Remove;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class Remove{Entity}Service implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private {Entity}Repository $repository, private {Entity}Authorizer $authorizer) {}

    public function __invoke(Remove{Entity}Request $request, User $user): Remove{Entity}Response
    {
        ${entity} = $this->repository->find($request->{entity}Id);

        if (null == ${entity}) {
            throw new \InvalidArgumentException('{Entity} not found');
        }

        $this->authorizer->denyUnlessCanDoThat(${entity}, SecurityAction::Remove, $user);

        ${entity}->remove();

        $this->repository->add(${entity});

        return new Remove{Entity}Response(
            {entity}Id: ${entity}->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof Remove{Entity}Request;
    }
}
```

### 5. List Use Case

#### List{Entities}Request.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\List;

use App\{Context}\Domain\Model\{Entity}\{Entity}Filter;

class List{Entities}Request extends {Entity}Filter {}
```

#### List{Entities}Response.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\List;

class List{Entities}Response
{
    /** @param array<\App\{Context}\Domain\Model\{Entity}\{Entity}> ${entities} */
    public function __construct(public array ${entities} = [], public int $totalResults = 0) {}
}
```

#### List{Entities}Service.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity}\List;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class List{Entities}Service
{
    public function __construct(private {Entity}Repository $repository, private {Entity}Authorizer $authorizer) {}

    public function __invoke(List{Entities}Request $request, User $user): List{Entities}Response
    {
        $this->authorizer->denyUnlessCanDoThat(new {Entity}(id: {Entity}Id::generate()), SecurityAction::List, $user);

        ${entities} = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new List{Entities}Response(${entities}, $totalResults);
    }
}
```

### 6. Unit Tests

#### Register{Entity}ServiceTest.php — WITHOUT translations

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Application\{Entity}\Register;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Application\{Entity}\Register\Register{Entity}Request;
use App\{Context}\Application\{Entity}\Register\Register{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity}Registered;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class Register{Entity}ServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = {Entity}RepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new Register{Entity}Request(
            name: '{Entity} 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->{entity}Id);
        $this->assertEquals(36, strlen((string) $result->{entity}Id));
        $this->assertEquals('{Entity} 1', $result->name);

        ${entity}Persisted = $repository->find($result->{entity}Id);

        $this->assertNotNull(${entity}Persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof {Entity}Registered);
        $this->assertEquals('{Entity} 1', $lastEvent->name());
        $this->assertTrue(${entity}Persisted->id()->equals($lastEvent->{entity}Id()));
    }

    public function testIsHandle(): void
    {
        $repository = {Entity}RepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new Register{Entity}Request(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT({Entity}Repository $repository): Register{Entity}Service
    {
        return new Register{Entity}Service(
            {entity}Repository: $repository,
            authorizer: new {Entity}Authorizer(),
        );
    }
}
```

#### Register{Entity}ServiceTest.php — WITH translations

Add `FakeLocaleSetter` handling and translation test:

```php
    // Add in setUp():
    FakeLocaleSetter::reset();

    // Add test:
    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = {Entity}RepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new Register{Entity}Request(
            name: 'changedName',
        );

        $result = $sut($request, UserMother::adminUser());

        ${entity}Persisted = $repository->find($result->{entity}Id);

        $this->assertNotNull(${entity}Persisted);
        $this->assertEquals('changedName', ${entity}Persisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof {Entity}Translated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue(${entity}Persisted->id()->equals($lastEvent->{entity}Id()));
    }

    // Modify createSUT to accept Locale:
    private function createSUT({Entity}Repository $repository, Locale $locale = Locale::Spanish): Register{Entity}Service
    {
        FakeLocaleSetter::set($locale);
        return new Register{Entity}Service(
            {entity}Repository: $repository,
            authorizer: new {Entity}Authorizer(),
        );
    }
```

#### Update{Entity}ServiceTest.php

Follow the same pattern as the Register test:
- Create an entity via `{Entity}Mother`, add it to repository
- Call the update service with an `Update{Entity}Request`
- Assert the entity is updated in the repository
- Assert the `{Entity}Updated` event was published
- For translations: assert `{Entity}Translated` event when locale is non-default

#### Remove{Entity}ServiceTest.php

Follow the same pattern:
- Create an entity via `{Entity}Mother`, add it to repository
- Call the remove service with a `Remove{Entity}Request`
- Assert the entity is no longer findable (soft delete) or is removed
- Assert the `{Entity}Removed` event was published

#### List{Entities}ServiceTest.php

Follow the same pattern:
- Add several entities via `{Entity}Mother` to repository
- Call the list service with a `List{Entities}Request`
- Assert results count and content

## Placeholder Legend

| Placeholder | Example | Description |
|-------------|---------|-------------|
| `{Entity}` | `Allergen` | PascalCase entity name |
| `{entity}` | `allergen` | camelCase entity name |
| `{Entities}` | `Allergens` | Pluralized PascalCase |
| `{entities}` | `allergens` | Pluralized camelCase |
| `{Context}` | `FoodLoss` | PascalCase bounded context |

## Execution Order

1. Create `{Entity}Authorizer.php` — adapt role checks to the Access Role requirement
2. Create Register use case: `Register{Entity}Request.php`, `Register{Entity}Response.php`, `Register{Entity}Service.php`
3. Create Update use case: `Update{Entity}Request.php`, `Update{Entity}Response.php`, `Update{Entity}Service.php`
4. Create Remove use case: `Remove{Entity}Request.php`, `Remove{Entity}Response.php`, `Remove{Entity}Service.php`
5. Create List use case: `List{Entities}Request.php`, `List{Entities}Response.php`, `List{Entities}Service.php`
6. Create unit test files: `Register{Entity}ServiceTest.php`, `Update{Entity}ServiceTest.php`, `Remove{Entity}ServiceTest.php`, `List{Entities}ServiceTest.php`
7. Run `make cs-fix`
8. Run `make phpstan`

## Important Notes

- Always use `null ==` (loose comparison) when checking for null in services (project convention)
- The `SecurityAction::List` is used for Register authorization (consistent with the existing pattern)
- The services.yaml in each context auto-wires all classes — no manual registration needed
- Remove service does soft-delete when the entity has `removed` flag; otherwise it calls the repository's `remove()` directly
