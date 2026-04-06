---
name: create-unit-tests
description: Creates unit tests for an existing AggregateRoot entity following DDD + Hexagonal Architecture patterns. Generates Mother objects, RepositoryMother, RepositoryTest (abstract + InMemory), AuthorizerTest, and all use-case service tests (Register, Update, Remove, List). Use when the user asks to "create unit tests", "add tests for entity", "generate tests", "write tests for [Entity]", or when unit tests are missing for an existing aggregate.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make test-unit:*), Bash(make phpstan:*), Bash(ls:*)
---

# Create Unit Tests

Generates all unit tests for an existing AggregateRoot entity following the project's DDD + Hexagonal Architecture conventions.

## Input Format

The user must provide:
1. **Entity name** (PascalCase, e.g., `DonationAgreement`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Use cases to test** — defaults to all: `Register`, `Update`, `Remove`, `List`
4. **Access role** — `Admin`, `Owner`, `User`, or `Owner-User` (affects which roles appear in Authorizer tests)

If not provided, ask before proceeding.

## What Gets Generated

```
tests/Unit/{Context}/
  Domain/Model/{Entity}/
    {Entity}Mother.php                          ← Factory builder for test data
    {Entity}RepositoryMother.php               ← Wraps InMemory repository
    {Entity}RepositoryTest.php                 ← Abstract repository contract tests
    InMemory{Entity}RepositoryTest.php         ← Concrete InMemory implementation test
  Application/{Entity}/
    {Entity}AuthorizerTest.php                 ← Authorization rules tests
    Register/Register{Entity}ServiceTest.php   ← Register use-case test
    Update/Update{Entity}ServiceTest.php       ← Update use-case test
    Remove/Remove{Entity}ServiceTest.php       ← Remove use-case test
    List/List{Entity}sServiceTest.php          ← List use-case test
```

## Step 0 — Research Phase

Before generating any file, read the existing source code to understand field names, types, constructor signatures, domain events, and authorizer rules:

```
src/{Context}/Domain/Model/{Entity}/{Entity}.php
src/{Context}/Domain/Model/{Entity}/{Entity}Id.php
src/{Context}/Domain/Model/{Entity}/{Entity}Filter.php
src/{Context}/Domain/Model/{Entity}/{Entity}Repository.php
src/{Context}/Domain/Model/{Entity}/{Entity}Registered.php
src/{Context}/Domain/Model/{Entity}/{Entity}Updated.php
src/{Context}/Domain/Model/{Entity}/{Entity}Removed.php
src/{Context}/Application/{Entity}/{Entity}Authorizer.php
src/{Context}/Application/{Entity}/Register/Register{Entity}Request.php
src/{Context}/Application/{Entity}/Register/Register{Entity}Service.php
src/{Context}/Application/{Entity}/Update/Update{Entity}Request.php
src/{Context}/Application/{Entity}/Update/Update{Entity}Service.php
src/{Context}/Application/{Entity}/Remove/Remove{Entity}Request.php
src/{Context}/Application/{Entity}/Remove/Remove{Entity}Service.php
src/{Context}/Application/{Entity}/List/List{Entity}sRequest.php
src/{Context}/Application/{Entity}/List/List{Entity}sService.php
src/{Context}/Infrastructure/Persistence/InMemory/Repository/InMemory{Entity}Repository.php
```

Also read a recent, analogous entity's tests as reference (e.g., `DeliveryLog`, `DonationAgreement`) to understand the pattern in context.

---

## File Templates

### 1. {Entity}Mother.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
// ... import other value objects used by the entity

class {Entity}Mother
{
    public static function registered{Entity}(): {Entity}
    {
        return self::from(
            id: (string) {Entity}Id::generate(),
            // ... sensible defaults for all required fields
        );
    }

    public static function from(
        string $id,
        // ... other fields with defaults where possible
    ): {Entity} {
        $entity = new {Entity}(
            id: new {Entity}Id($id),
            // ... pass constructor-only fields
        );
        $entity->register(
            // ... pass registration fields
        );

        return $entity;
    }

    public static function random(): {Entity}
    {
        return self::from(
            id: (string) {Entity}Id::generate(),
            // ... randomized values using rand() / uniqid()
        );
    }

    // Add domain-specific factory methods as needed, e.g.:
    // public static function forAccount(string $accountId): {Entity}
    // public static function withPropertyId(PropertyId $propertyId): {Entity}
}
```

**Rules:**
- `registered{Entity}()` uses fixed, deterministic values. Good for basic add/find tests.
- `random()` uses `rand()` / `uniqid()` / `{Entity}Id::generate()` to avoid collision in multi-entity repository tests.
- Add named factories only for associations tested in subscriber or filter tests.
- Derive constructor arguments from reading `{Entity}.php` and `{Entity}Id.php`.

---

### 2. {Entity}RepositoryMother.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Infrastructure\Persistence\InMemory\Repository\InMemory{Entity}Repository;

class {Entity}RepositoryMother
{
    public static function inMemory(): InMemory{Entity}Repository
    {
        return new InMemory{Entity}Repository();
    }
}
```

---

### 3. {Entity}RepositoryTest.php (abstract)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity}Filter;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class {Entity}RepositoryTest extends KernelTestCase
{
    public function testAdd{Entity}ToRepository(): void
    {
        $repository = $this->createSUT();

        $entity = {Entity}Mother::registered{Entity}();

        $this->assertNull($repository->find($entity->id()));

        $repository->add($entity);

        $recovered = $repository->find($entity->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($entity->id(), $recovered->id());
    }

    public function testRemove{Entity}ToRepository(): void
    {
        $repository = $this->createSUT();

        $entity = {Entity}Mother::registered{Entity}();

        $repository->add($entity);

        $recovered = $repository->find($entity->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($entity->id(), $recovered->id());

        $repository->remove($entity);

        $this->assertNull($repository->find($entity->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());

        // Add a specific target to assert filter precision
        // Adapt the filter field to the entity's {Entity}Filter fields
        $target = {Entity}Mother::random();
        $repository->add($target);

        $filter = new {Entity}Filter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(4, $resultUnfiltered);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $entity = {Entity}Mother::registered{Entity}();

        $repository->add($entity);

        $recovered = $repository->find($entity->id());

        $this->assertNotNull($recovered);

        $recovered->remove();

        $repository->add($entity);

        $this->assertNull($repository->find($entity->id()));
    }

    abstract protected function createSUT(): {Entity}Repository;
}
```

**Notes:**
- `testFindByFilter` must be adapted to the actual filterable fields in `{Entity}Filter`. Read the filter class to determine which criteria are supported. Add a test for each meaningful filter (e.g., `accountId`, `propertyId`, `productType`).
- Only include `testFindNotRecoverRemoved` if the entity supports soft delete (has a `remove()` method that marks it as removed).

---

### 4. InMemory{Entity}RepositoryTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;

class InMemory{Entity}RepositoryTest extends {Entity}RepositoryTest
{
    protected function createSUT(): {Entity}Repository
    {
        return {Entity}RepositoryMother::inMemory();
    }
}
```

---

### 5. {Entity}AuthorizerTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Application\{Entity};

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}Mother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class {Entity}AuthorizerTest extends TestCase
{
    // Adapt these tests by reading {Entity}Authorizer::canDoThat() logic

    public function testOwnerCanList{Entity}s(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new {Entity}(id: {Entity}Id::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testNormalUserCannotList{Entity}s(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = new {Entity}(id: {Entity}Id::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanEdit{Entity}FromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = {Entity}Mother::forAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEdit{Entity}FromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = {Entity}Mother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCannotDoAnything(): void
    {
        $user = UserMother::adminUser();
        $subject = {Entity}Mother::random();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): {Entity}Authorizer
    {
        return new {Entity}Authorizer();
    }
}
```

**Adaptation rules:**
- Read `{Entity}Authorizer::canDoThat()` to discover the actual authorization logic.
- Add/remove test methods to match the real rules (e.g., if `SuperAdmin` can do everything, add a test for it).
- If the entity has no `accountId`, simplify the `testOwnerCanEdit*` pair.
- Only add `forAccount()` to the Mother if the Authorizer tests need it.

---

### 6. Register{Entity}ServiceTest.php

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

        // Build a user with the appropriate role (Owner, Admin, etc.)
        $user = UserMother::adminUser(); // adapt to the authorized role

        $request = new Register{Entity}Request(
            // ... fill in all required request fields
        );

        $result = $sut($request, $user);

        // Assert response fields
        $this->assertNotEmpty((string) $result->{entityId});

        // Assert persistence
        $persisted = $repository->find($result->{entityId});
        $this->assertNotNull($persisted);

        // Assert domain event
        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf({Entity}Registered::class, $lastEvent);
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT({Entity}RepositoryMother::inMemory());

        $result = $sut->isHandled(new Register{Entity}Request(
            // ... minimal valid request
        ));

        $this->assertTrue($result);
    }

    private function createSUT({Entity}Repository $repository): Register{Entity}Service
    {
        return new Register{Entity}Service(
            // ... named constructor args matching the service's __construct
            authorizer: new {Entity}Authorizer(),
        );
    }
}
```

---

### 7. Update{Entity}ServiceTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Application\{Entity}\Update;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Application\{Entity}\Update\Update{Entity}Request;
use App\{Context}\Application\{Entity}\Update\Update{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}Mother;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class Update{Entity}ServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = {Entity}RepositoryMother::inMemory();

        $user = UserMother::adminUser(); // adapt to authorized role

        $entity = {Entity}Mother::registered{Entity}();
        $repository->add($entity);

        $sut = $this->createSUT($repository);

        $request = new Update{Entity}Request(
            {entityId}: $entity->id(),
            // ... all updatable fields with new values
        );

        $result = $sut($request, $user);

        // Assert response reflects the update
        // $this->assertEquals('new value', $result->someField);

        $persisted = $repository->find($result->{entityId});
        $this->assertNotNull($persisted);
        // Assert the entity was actually mutated
        // $this->assertEquals('new value', $persisted->someField());
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT({Entity}RepositoryMother::inMemory());

        $entity = {Entity}Mother::registered{Entity}();

        $result = $sut->isHandled(new Update{Entity}Request(
            {entityId}: $entity->id(),
            // ... minimal valid fields
        ));

        $this->assertTrue($result);
    }

    private function createSUT({Entity}Repository $repository): Update{Entity}Service
    {
        return new Update{Entity}Service(
            // ... named constructor args
            authorizer: new {Entity}Authorizer(),
        );
    }
}
```

---

### 8. Remove{Entity}ServiceTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Application\{Entity}\Remove;

use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Application\{Entity}\Remove\Remove{Entity}Request;
use App\{Context}\Application\{Entity}\Remove\Remove{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}Mother;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class Remove{Entity}ServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = {Entity}RepositoryMother::inMemory();

        $user = UserMother::adminUser(); // adapt to authorized role

        $entity = {Entity}Mother::registered{Entity}();
        $repository->add($entity);

        $sut = $this->createSUT($repository);

        $result = $sut(new Remove{Entity}Request($entity->id()), $user);

        $this->assertTrue($entity->id()->equals($result->{entityId}));

        $this->assertNull($repository->find($entity->id()));
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT({Entity}RepositoryMother::inMemory());

        $entity = {Entity}Mother::registered{Entity}();

        $result = $sut->isHandled(new Remove{Entity}Request(
            {entityId}: $entity->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT({Entity}Repository $repository): Remove{Entity}Service
    {
        return new Remove{Entity}Service(
            // ... named constructor args
            authorizer: new {Entity}Authorizer(),
        );
    }
}
```

---

### 9. List{Entity}sServiceTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Application\{Entity}\List;

use App\{Context}\Application\{Entity}\List\List{Entity}sRequest;
use App\{Context}\Application\{Entity}\List\List{Entity}sService;
use App\{Context}\Application\{Entity}\{Entity}Authorizer;
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}Mother;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class List{Entity}sServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = {Entity}RepositoryMother::inMemory();

        $user = UserMother::adminUser(); // adapt to authorized role

        // Add a mix: some matching, some not
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new List{Entity}sRequest(
            // ... filter parameters
        ), $user);

        $this->assertEquals(2, $result->totalResults);
        $this->assertCount(2, $result->{entityPluralCamel});
    }

    private function createSUT({Entity}Repository $repository): List{Entity}sService
    {
        return new List{Entity}sService(
            // ... named constructor args
            authorizer: new {Entity}Authorizer(),
        );
    }
}
```

---

## Execution Checklist

Work through files in this order. Mark each complete before moving to the next.

1. **Research** — read all source files listed in Step 0
2. **{Entity}Mother.php** — derive fields and defaults from the aggregate root
3. **{Entity}RepositoryMother.php** — trivial wrapper
4. **{Entity}RepositoryTest.php** (abstract) — adapt filter tests to actual `{Entity}Filter` fields
5. **InMemory{Entity}RepositoryTest.php** — delegates to abstract base
6. **{Entity}AuthorizerTest.php** — mirror the actual `canDoThat()` logic in the tests
7. **Register{Entity}ServiceTest.php** — use `SpyDomainEventSubscriber`
8. **Update{Entity}ServiceTest.php**
9. **Remove{Entity}ServiceTest.php**
10. **List{Entity}sServiceTest.php**
11. Run `make cs-fix` to fix coding style
12. Run `make test-unit` — fix any failures before finishing

## Key Rules

- **Every PHP file** must start with `declare(strict_types=1);`
- **No mocking** — use InMemory repositories, never PHPUnit mocks for repositories
- **`setUp()` always resets** `DomainEventPublisher::instance()->reset()`; add `new SpyDomainEventSubscriber()` only when events need asserting (Register tests)
- **`testIsHandle()`** — include in every use-case service test; it validates `isHandled()` returns `true`
- **Mother `from()` method** must accept all entity fields as named parameters with sensible defaults
- **Named constructor arguments** — always use named args in `new Request(...)` and service construction
- **Adapt, don't copy blindly** — read the actual source files; field names, constructor signatures, and constructor-injection vary per entity
- **Do not generate** Doctrine repository tests unless explicitly requested (they require kernel boot and are slower)
