---
name: create-entity
description: Creates a new AggregateRoot entity with full DDD + Hexagonal Architecture scaffolding. Use when the user asks to "create a new entity", "add a new aggregate", "scaffold entity", or specifies fields for a new domain model. Generates Domain, Application, Infrastructure, and Test layers following the exact project conventions.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make phpstan:*), Bash(ls:*)
---

# Create Entity Skill

Creates a complete AggregateRoot entity following the project's DDD + Hexagonal Architecture conventions.

## Input Format

The user must provide:
1. **Entity name** (PascalCase, e.g., `Allergen`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Fields** with types (e.g., `name: string`, `priority: Priority(enum)`, `density: string`)
4. **Soft delete** (yes/no) - whether the entity supports logical deletion
5. **Translations** (yes/no) - whether it has translatable fields, and which fields are translatable
6. **Access Role** (Admin / Owner-User / Both) - Who can perform the CRUD actions:
    - **Admin**: Routes preceded by `/admin`. Can access all accounts and filter by `accountId`.
    - **Owner-User**: Routes NOT preceded by `/admin`. `accountId` is forced by code (usually from `LoggedAccount`).
    - **Both**: Separate controllers and templates for each role, following the rules above.

If the user doesn't provide all inputs, ask for the missing information before proceeding.

## Naming Conventions

Given entity name `{Entity}` in context `{Context}`:

| Element | Name Pattern                                                                                                                                            |
|---------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| Aggregate Root | `{Entity}`                                                                                                                                              |
| ID Value Object | `{Entity}Id`                                                                                                                                            |
| Filter | `{Entity}Filter`                                                                                                                                        |
| Repository Interface | `{Entity}Repository`                                                                                                                                    |
| Translation Entity | `{Entity}Translation` (if translatable)                                                                                                                 |
| Domain Events | `{Entity}Registered`, `{Entity}Updated`, `{Entity}Removed`, `{Entity}Translated`                                                                        |
| Authorizer | `{Entity}Authorizer`                                                                                                                                    |
| Register Service | `Register{Entity}Service`                                                                                                                               |
| Update Service | `Update{Entity}Service`                                                                                                                                 |
| Remove Service | `Remove{Entity}Service`                                                                                                                                 |
| List Service | `List{Entities}Service` (pluralized)                                                                                                                    |
| Request DTOs | `Register{Entity}Request`, `Update{Entity}Request`, `Remove{Entity}Request`, `List{Entities}Request`                                                    |
| Response DTOs | `Register{Entity}Response`, `Update{Entity}Response`, `Remove{Entity}Response`, `List{Entities}Response`                                                |
| Doctrine Repository | `Doctrine{Entity}Repository`                                                                                                                            |
| InMemory Repository | `InMemory{Entity}Repository`                                                                                                                            |
| Doctrine Type | `{Entity}IdType`                                                                                                                                        |
| Mother | `{Entity}Mother`                                                                                                                                        |
| Repository Mother | `{Entity}RepositoryMother`                                                                                                                              |
| Behat Context | `{Context}Context` (shared per context, add steps)                                                                                                      |
| Web Controllers | `Admin\ListController`, `Admin\RegisterController`, `ListController`, `RegisterController`, etc.                                                        |
| Form Types | `Register{Entity}Type`, `Update{Entity}Type`, `List{Entities}Type`                                                                                      |
| Bulk Action | `RemoveAction`                                                                                                                                          |
| Twig Templates | `admin/{entities_snake}/list.html.twig`, `admin/{entities_snake}/register.html.twig`, `admin/{entities_snake}/edit.html.twig`, `{entities_snake}/list.html.twig`, `{entities_snake}/register.html.twig`, `{entities_snake}/edit.html.twig` |

## Directory Structure

```
src/{Context}/
  Domain/
    Model/{Entity}/
      {Entity}.php
      {Entity}Id.php
      {Entity}Filter.php
      {Entity}Repository.php
      {Entity}Registered.php
      {Entity}Updated.php
      {Entity}Removed.php
      {Entity}Translation.php          # if translatable
      {Entity}Translated.php           # if translatable
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
  Infrastructure/
    Persistence/
      Doctrine/
        Mapping/
          {Entity}.{Entity}.orm.xml
          {Entity}.{Entity}Translation.orm.xml   # if translatable
        Repository/
          Doctrine{Entity}Repository.php
        Type/
          {Entity}IdType.php
      InMemory/
        Repository/
          InMemory{Entity}Repository.php
    Symfony/
      Form/
        Register{Entity}Type.php
        Update{Entity}Type.php
        List{Entities}Type.php
    UI/
      Web/
        Admin/{Entity}/
          ListController.php
          RegisterController.php
          EditController.php
          RemoveController.php
          Get{Entity}FromIdTrait.php
          Bulk/RemoveAction.php
        {Entity}/
          ListController.php
          RegisterController.php
          EditController.php
          RemoveController.php
          Get{Entity}FromIdTrait.php
          Bulk/RemoveAction.php

templates/{context_snake}/
  admin/{entities_kebab}/
    list.html.twig
    register.html.twig
    edit.html.twig
  user/{entities_kebab}/
    list.html.twig
    register.html.twig
    edit.html.twig


tests/
  Unit/{Context}/
    Domain/Model/{Entity}/
      {Entity}Test.php                 # if translatable
      {Entity}RepositoryTest.php
      {Entity}Mother.php
      {Entity}RepositoryMother.php
      InMemory{Entity}RepositoryTest.php
    Infrastructure/Persistence/Doctrine/Repository/
      Doctrine{Entity}RepositoryTest.php
    Application/{Entity}/
      {Entity}AuthorizerTest.php
      Register/Register{Entity}ServiceTest.php
      Update/Update{Entity}ServiceTest.php
      Remove/Remove{Entity}ServiceTest.php
      List/List{Entities}ServiceTest.php
  Acceptance/{Context}/
    {entity}-register.feature
    {entity}-list.feature
    {entity}-edit.feature
    {entity}-remove.feature
```

## File Templates

### 1. Domain Layer

#### {Entity}.php — WITHOUT translations, WITH soft delete

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\Shared\Domain\Model\AggregateRoot;

class {Entity} implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private {Entity}Id $id,
        // --- user-defined fields with defaults ---
        private string $name = '',
        // --- end user-defined fields ---
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): {Entity}Id
    {
        return $this->id;
    }

    // --- getters for each user-defined field ---
    public function name(): string
    {
        return $this->name;
    }
    // --- end getters ---

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        // user-defined fields as parameters
        string $name,
    ): void {
        $this->recordApplyAndPublish(
            new {Entity}Registered(
                {entity}Id: $this->id(),
                name: $name,
            ),
        );
    }

    public function update(
        // user-defined fields as parameters
        string $name,
    ): void {
        $this->recordApplyAndPublish(
            new {Entity}Updated(
                {entity}Id: $this->id(),
                name: $name,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new {Entity}Removed(
                {entity}Id: $this->id(),
            ),
        );
    }

    public function apply{Entity}Registered({Entity}Registered $event): void
    {
        // assign each field from event
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function apply{Entity}Updated({Entity}Updated $event): void
    {
        // assign each field from event
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function apply{Entity}Removed({Entity}Removed $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
```

#### {Entity}.php — WITH translations, WITH soft delete

When the entity has translatable fields, follow the `Action` pattern:

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class {Entity} implements Translatable
{
    use AggregateRoot;

    /** @var iterable<{Entity}Translation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private {Entity}Id $id,
        // --- user-defined fields with defaults ---
        private string $name = '',
        // For non-translatable fields:
        // private Priority $priority = Priority::Default,
        // --- end user-defined fields ---
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): {Entity}Id
    {
        return $this->id;
    }

    // --- For TRANSLATABLE fields, the getter checks translations first ---
    public function name(): string
    {
        /** @var {Entity}Translation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    // --- For NON-translatable fields, simple getter ---
    // public function priority(): Priority
    // {
    //     return $this->priority;
    // }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        // other fields...
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new {Entity}Registered(
                {entity}Id: $this->id(),
                name: $name,
                // other fields...
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        // other fields...
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new {Entity}Updated(
                {entity}Id: $this->id(),
                // For translatable fields: use original value when translating
                name: null === $locale ? $name : $this->name,
                // For non-translatable fields: always pass directly
                // priority: $priority,
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new {Entity}Translated(
                {entity}Id: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new {Entity}Removed(
                {entity}Id: $this->id(),
            ),
        );
    }

    public function apply{Entity}Registered({Entity}Registered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function apply{Entity}Updated({Entity}Updated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function apply{Entity}Translated({Entity}Translated $event): void
    {
        ${entity}Translation = new {Entity}Translation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add(${entity}Translation);
    }

    public function apply{Entity}Removed({Entity}Removed $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
```

#### {Entity}.php — WITHOUT soft delete

When no soft delete:
- Remove `$removed`, `$removedAt` properties and their getters
- Remove `isRemoved()` method
- Remove `remove()` method and `apply{Entity}Removed()` method
- Remove the `{Entity}Removed` event class
- Keep `$registeredAt` and `$updatedAt`

#### {Entity}Id.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class {Entity}Id implements AggregateRootId
{
    use AggregateRootIdTrait;
}
```

#### {Entity}Translation.php (only if translatable)

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class {Entity}Translation implements Translation
{
    public function __construct(
        // One property per translatable field
        private string $name,
        private Locale $locale,
        private {Entity} $translatable,
    ) {}

    // One getter per translatable field
    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): {Entity}
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof {Entity}Translation);

        // Update each translatable field
        $this->name = $translation->name();
    }
}
```

#### {Entity}Filter.php

**Always use value objects instead of primitives** for filter fields. In particular, `accountId` MUST be `?AccountId`, never `?string`.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class {Entity}Filter
{
    use Filter;

    public function __construct(
        // One nullable property per filterable field — use value objects, not primitives
        public ?string $name = null,
        public ?AccountId $accountId = null,   // include only for account-scoped entities
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
```

#### {Entity}Repository.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

interface {Entity}Repository
{
    public function add({Entity} ${entity}): void;

    public function remove({Entity} ${entity}): void;

    public function find({Entity}Id ${entity}Id): ?{Entity};

    /** @return array<{Entity}> */
    public function findByFilter({Entity}Filter $filter): array;

    public function totalResultsByFilter({Entity}Filter $filter): int;
}
```

#### {Entity}Registered.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class {Entity}Registered extends DomainEvent
{
    protected static string $eventName = '{entity}Registered';

    public function __construct(
        private {Entity}Id ${entity}Id,
        // All fields from register()
        private string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function {entity}Id(): {Entity}Id
    {
        return $this->{entity}Id;
    }

    // Getters for each field
    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->{entity}Id();
    }

    public function payload(): array
    {
        return [
            '{entity}Id' => (string) $this->{entity}Id,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new {Entity}Registered(
            {entity}Id: new {Entity}Id($payload['{entity}Id']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
```

#### {Entity}Updated.php

Same structure as `{Entity}Registered` but with `$eventName = '{entity}Updated'` and class name `{Entity}Updated`.

#### {Entity}Removed.php (only if soft delete)

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class {Entity}Removed extends DomainEvent
{
    protected static string $eventName = '{entity}Removed';

    public function __construct(
        private {Entity}Id ${entity}Id,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function {entity}Id(): {Entity}Id
    {
        return $this->{entity}Id;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->{entity}Id();
    }

    public function payload(): array
    {
        return [
            '{entity}Id' => (string) $this->{entity}Id,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new {Entity}Removed(
            {entity}Id: new {Entity}Id($payload['{entity}Id']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
```

#### {Entity}Translated.php (only if translatable)

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class {Entity}Translated extends DomainEvent
{
    protected static string $eventName = '{entity}Translated';

    public function __construct(
        private {Entity}Id ${entity}Id,
        // One property per translatable field
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function {entity}Id(): {Entity}Id
    {
        return $this->{entity}Id;
    }

    // Getters for each translatable field
    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->{entity}Id();
    }

    public function payload(): array
    {
        return [
            '{entity}Id' => (string) $this->{entity}Id,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new {Entity}Translated(
            {entity}Id: new {Entity}Id($payload['{entity}Id']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
```

### 2. Application Layer

#### {Entity}Authorizer.php

Choose the variant that matches the Access Role:

**Variant A — Admin only:**

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
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
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

**Variant B — Owner-User** (must verify that the user's `accountId` matches the entity's `accountId`):

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
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
        assert($subject instanceof {Entity});

        $subjectAccountId = $subject->accountId();

        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && null !== $subjectAccountId
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$user->hasRole(Role::Owner) || !$user->accountId() instanceof AccountId) {
            return false;
        }

        if (!$subject instanceof {Entity}) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        return null !== $subjectAccountId && $user->accountId()->equals($subjectAccountId);
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

> **Note (Owner-User):** The entity **must** have an `accountId(): ?AccountId` method. `canList` and `canEdit` both verify that the logged-in Owner's `accountId` equals the entity's `accountId`. This prevents cross-account data access.

**Variant C — Both** (Admin + Owner-User combined): use Variant A for Admin routes and Variant B logic in the same authorizer — `canList`/`canEdit` first check Admin, then fall through to the accountId check for Owners.

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

Same as without but add locale handling:

```php
        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        ${entity}->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );
```

And add `use App\Shared\Domain\Model\Locale\LocaleGetter;`

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

### 3. Infrastructure Layer

#### Doctrine{Entity}Repository.php — WITHOUT translations

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Persistence\Doctrine\Repository;

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Filter;
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.{entity}', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class Doctrine{Entity}Repository implements {Entity}Repository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add({Entity} ${entity}): void
    {
        $this->manager->persist(${entity});
        $this->manager->flush();
    }

    public function remove({Entity} ${entity}): void
    {
        $this->manager->remove(${entity});
        $this->manager->flush();
    }

    public function find({Entity}Id ${entity}Id): ?{Entity}
    {
        $qb = $this->createQueryBuilderFromFilter(new {Entity}Filter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) ${entity}Id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter({Entity}Filter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from({Entity}::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        // Add filter conditions for each filterable field
        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<{Entity}> */
    public function findByFilter({Entity}Filter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter({Entity}Filter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
```

Note: if entity has NO soft delete, remove the `removed` WHERE clause from `createQueryBuilderFromFilter`.

#### Doctrine{Entity}Repository.php — WITH translations

Add the trait and translation join:

```php
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;
// ...

class Doctrine{Entity}Repository implements {Entity}Repository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add({Entity} ${entity}): void
    {
        $this->getEntityManager()->persist(${entity});
        $this->addTranslations(${entity}, {Entity}Translation::class, $this->manager);
        $this->getEntityManager()->flush();
    }

    // In createQueryBuilderFromFilter:
    // Add LEFT JOIN for translations
    $qb->select('e')
        ->from({Entity}::class, 'e')
        ->leftJoin({Entity}Translation::class, 'trans', 'WITH', 'trans.translatable = e AND trans.locale = :locale')
        ->where(/* soft delete clause */)
        ->setParameter('locale', LocaleGetter::locale())
    ;

    // For translatable field filters:
    if (!empty($filter->name)) {
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->like('e.name', ':name'),
                        $qb->expr()->isNull('trans.name'),
                    ),
                    $qb->expr()->like('trans.name', ':name'),
                ),
            )
            ->setParameter('name', "%{$filter->name}%")
        ;
    }

    // Add getEntityManager helper
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
```

#### InMemory{Entity}Repository.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Persistence\InMemory\Repository;

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Filter;
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemory{Entity}Repository implements {Entity}Repository
{
    /** @param array<string, {Entity}> ${entities} */
    public function __construct(
        private array ${entities} = [],
    ) {}

    public function add({Entity} ${entity}): void
    {
        if (isset($this->{entities}[(string) ${entity}->id()])) {
            return;
        }

        $this->{entities}[(string) ${entity}->id()] = ${entity};
    }

    public function remove({Entity} ${entity}): void
    {
        if (!isset($this->{entities}[(string) ${entity}->id()])) {
            return;
        }

        unset($this->{entities}[(string) ${entity}->id()]);
    }

    public function find({Entity}Id ${entity}Id): ?{Entity}
    {
        return (isset($this->{entities}[(string) ${entity}Id]) && false == $this->{entities}[(string) ${entity}Id]->isRemoved())
            ? $this->{entities}[(string) ${entity}Id]
            : null
        ;
    }

    /** @return array<int, {Entity}> */
    public function findByFilter({Entity}Filter $filter): array
    {
        $results = [];

        if (0 === count($this->{entities})) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->{entities} as ${entity}) {
            // Filter by each filterable field
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) ${entity}->name()), strtolower($filter->name))
            ) {
                continue;
            }

            // Soft delete check (only if soft delete is enabled)
            if (true == ${entity}->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = ${entity};
        }

        return $results;
    }

    public function totalResultsByFilter({Entity}Filter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
```

Note: if entity has NO soft delete, remove the `isRemoved()` check from `find()` and `findByFilter()`.

#### {Entity}IdType.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Persistence\Doctrine\Type;

use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class {Entity}IdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return '{entity}_id';
    }

    public function getClassName(): string
    {
        return {Entity}Id::class;
    }
}
```

#### {Entity}.{Entity}.orm.xml — WITHOUT translations

```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\{Context}\Domain\Model\{Entity}\{Entity}" table="{entity_snake}">
        <id name="id" type="{entity}_id" column="id">
            <generator strategy="NONE" />
        </id>

        <!-- User-defined fields -->
        <field name="name" type="string" length="255" nullable="false"/>
        <!-- For enum fields: -->
        <!-- <field name="priority" type="string" enum-type="App\{Context}\Domain\Model\{Entity}\Priority" nullable="false"/> -->
        <!-- For bool fields: -->
        <!-- <field name="isDangerous" type="boolean" nullable="false"/> -->

        <!-- Soft delete fields (only if soft delete) -->
        <field name="removed" type="boolean" nullable="false"/>
        <field name="registeredAt" type="datetime_immutable" column="registered_at" nullable="false"/>
        <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="true"/>
        <field name="removedAt" type="datetime_immutable" column="removed_at" nullable="true"/>
    </entity>
</doctrine-mapping>
```

#### {Entity}.{Entity}.orm.xml — WITH translations

Add the translations one-to-many:

```xml
        <one-to-many field="translations"
                     target-entity="App\{Context}\Domain\Model\{Entity}\{Entity}Translation"
                     mapped-by="translatable"
                     fetch="EXTRA_LAZY">
            <cascade>
                <cascade-persist/>
                <cascade-remove/>
            </cascade>
        </one-to-many>
```

#### {Entity}.{Entity}Translation.orm.xml (only if translatable)

```xml
<?xml version="1.0" encoding="UTF-8"?>

<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\{Context}\Domain\Model\{Entity}\{Entity}Translation" table="{entity_snake}_translation">
        <id name="locale" type="locale" length="5" />
        <!-- One field per translatable property -->
        <field name="name" type="string" length="255" nullable="false" />
        <id name="translatable" association-key="true" />

        <many-to-one field="translatable" target-entity="App\{Context}\Domain\Model\{Entity}\{Entity}" inversed-by="translations">
            <join-column name="{entity_snake}_id" referenced-column-name="id" nullable="false" on-delete="CASCADE"/>
        </many-to-one>
    </entity>

</doctrine-mapping>
```

**⚠️ DATABASE TABLE NAMING CONVENTION:**
- Table names in the `table` attribute must be **snake_case** and always **plural** (e.g., `properties`, `critical_points`, `waste_containers`)
- **Never include the bounded context prefix** in the table name. For example, even in the `FoodLoss` context, the table is `critical_points`, not `food_loss_critical_points`
- For translation tables, follow the same rule: `{entity_snake}_translation` remains plural and without context prefix

### 4. Web Controllers

#### ListController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity};

use App\{Context}\Application\{Entity}\List\List{Entities}Request;
use App\{Context}\Application\{Entity}\List\List{Entities}Service;
use App\{Context}\Infrastructure\Symfony\Form\List{Entities}Type;
use App\{Context}\Infrastructure\UI\Web\Admin\{Entity}\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/{entities_kebab}', name: 'admin_{entities_snake}')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(List{Entities}Service $list{Entities}, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new List{Entities}Request();
            }, List{Entities}Type::class);

            $response = $list{Entities}($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->{entities},
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@{context_snake}/admin/{entities_kebab}/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            '{entities}' => $response->{entities},
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
```

#### RegisterController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity};

use App\{Context}\Application\{Entity}\Register\Register{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Infrastructure\Symfony\Form\Register{Entity}Type;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/{entities_kebab}/register', name: 'admin_{entities_snake}_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(Register{Entity}Service $register{Entity}, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new {Entity}({Entity}Id::generate()));

        $form = $this->createForm(Register{Entity}Type::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($register{Entity}, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $register{Entity}($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('{Entity_label} creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_{entities_snake}');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/admin/{entities_kebab}/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

#### EditController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity};

use App\{Context}\Application\{Entity}\Update\Update{Entity}Request;
use App\{Context}\Application\{Entity}\Update\Update{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\{Context}\Infrastructure\Symfony\Form\Update{Entity}Type;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/{entities_kebab}/edit/{{entity}Id}', name: 'admin_{entities_snake}_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use Get{Entity}FromIdTrait;

    public function __invoke(
        string ${entity}Id,
        {Entity}Repository ${entity}Repository,
        Update{Entity}Service $update{Entity},
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        ${entity} = $this->get{Entity}FromId(${entity}Id, ${entity}Repository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, ${entity});

        ${entity}Request = new Update{Entity}Request(
            {entity}Id: ${entity}->id(),
            // Map each field from entity to request
            name: ${entity}->name(),
        );

        try {
            $form = $this->createForm(Update{Entity}Type::class, ${entity}Request);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($update{Entity}, $loggedUser) {
                ${entity}Request = $form->getData();

                $update{Entity}(${entity}Request, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('{Entity_label} actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_{entities_snake}');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/admin/{entities_kebab}/edit.html.twig', [
            '{entity}' => ${entity},
            'form' => $form->createView(),
        ]);
    }
}
```

#### RemoveController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity};

use App\{Context}\Application\{Entity}\Remove\Remove{Entity}Request;
use App\{Context}\Application\{Entity}\Remove\Remove{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/{entities_kebab}/remove/{{entity}Id}', name: 'admin_{entities_snake}_remove')]
class RemoveController extends AbstractController
{
    use Get{Entity}FromIdTrait;

    public function __invoke(
        string ${entity}Id,
        {Entity}Repository ${entity}Repository,
        Remove{Entity}Service $remove{Entity},
        LoggedUser $loggedUser,
    ): Response {
        ${entity} = $this->get{Entity}FromId(${entity}Id, ${entity}Repository);

        try {
            $remove{Entity}(new Remove{Entity}Request(${entity}->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado {entity_label} {{entity}}', ['{{entity}}' => ${entity}->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar {entity_label} {{entity}}', ['{{entity}}' => ${entity}->name()]));
        }

        return $this->redirectToRoute('admin_{entities_snake}');
    }
}
```

#### User\ListController.php (Owner/User Access)

The `accountId` is forced **in the controller** using `LoggedAccount` before passing the request to the service.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\{Entity};

use App\{Context}\Application\{Entity}\List\List{Entities}Request;
use App\{Context}\Application\{Entity}\List\List{Entities}Service;
use App\{Context}\Infrastructure\Symfony\Form\List{Entities}Type;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{entities_kebab}', name: '{entities_snake}')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(List{Entities}Service $list{Entities}, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            $user = $loggedUser();
            $account = $loggedAccount();

            list($filter, $form) = $this->filter($request, function () use ($loggedAccount) {
                $request = new List{Entities}Request();
                // FORCE accountId from logged account — pass AccountId object, not a string
                $request->accountId = $loggedAccount()?->id();
                return $request;
            }, List{Entities}Type::class);

            $response = $list{Entities}($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/{entities_kebab}/list.html.twig', [
            '{entities}' => $response->{entities},
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
```

#### User\RegisterController.php (Owner/User Access)

The `accountId` is forced **in the controller** using `LoggedAccount` and set on the request DTO before the service is called.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\{Entity};

use App\{Context}\Application\{Entity}\Register\Register{Entity}Request;
use App\{Context}\Application\{Entity}\Register\Register{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Infrastructure\Symfony\Form\Register{Entity}Type;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/{entities_kebab}/register', name: '{entities_snake}_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(Register{Entity}Service $register{Entity}, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new {Entity}({Entity}Id::generate()));

        $form = $this->createForm(Register{Entity}Type::class, new Register{Entity}Request());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($register{Entity}, $loggedUser, $loggedAccount) {
                $registerRequest = $form->getData();

                // FORCE accountId from logged account — pass AccountId object, not a string
                $registerRequest->accountId = $loggedAccount()->id();

                $response = $register{Entity}($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('{Entity_label} creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('{entities_snake}');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/{entities_kebab}/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

#### Get{Entity}FromIdTrait.php (updated to check accountId if user is not admin)

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;

trait Get{Entity}FromIdTrait
{
    private function get{Entity}FromId(string ${entity}Id, {Entity}Repository $repository): {Entity}
    {
        ${entity} = $repository->find(new {Entity}Id(${entity}Id));

        if (null == ${entity}) {
            throw $this->createNotFoundException('{Entity_label} no encontrado');
        }

        return ${entity};
    }
}
```

#### Bulk/RemoveAction.php

Use the `RemoveAction` trait from `App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction` to provide `id()` and `name()` — never implement them manually.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\Admin\{Entity}\Bulk;

use App\{Context}\Application\{Entity}\Remove\Remove{Entity}Request;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction as RemoveActionTrait;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    use RemoveActionTrait;

    public function commands(BulkRequest $request): array
    {
        if (0 == count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as ${entity}) {
            if (!${entity} instanceof {Entity}) {
                continue;
            }

            $commands[] = new Remove{Entity}Request(
                {entity}Id: ${entity}->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof Remove{Entity}Request);

        return new TranslatableMessage('Se ha eliminado {entity_label} {id}', ['{id}' => $command->{entity}Id]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof Remove{Entity}Request);

        return new TranslatableMessage('No se ha podido eliminar {entity_label} {id}', ['{id}' => $command->{entity}Id]);
    }
}
```

### 5. Form Types

#### Register{Entity}Type.php and Update{Entity}Type.php

**Validation rules** (apply identically to both Register and Update form types):
- Required `string` fields → `NotBlank` + `Length(max: N)` where N comes from the ORM mapping (`length="N"`).
- Required enum/choice fields → `NotNull`.
- Optional `string` fields → `Length(max: N)` only (no `NotBlank`), plus `empty_data => ''` to prevent null reaching non-nullable properties.
- Optional `email` fields → `Email` + `Length(max: N)`, plus `empty_data => ''`.
- `text` (unlimited) fields → no `Length` constraint needed.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Symfony\Form;

use App\{Context}\Application\{Entity}\Register\Register{Entity}Request;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class Register{Entity}Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Required string — NotBlank + Length from ORM mapping
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('{Field_label}'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            // Required enum/choice — NotNull
            // ->add('type', {Enum}Type::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => true,
            //     'constraints' => [new Assert\NotNull()],
            // ])
            // Optional string with DB length — Length only, no NotBlank, empty_data: ''
            // ->add('code', TextType::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => false,
            //     'empty_data' => '',
            //     'constraints' => [new Assert\Length(max: 50)],
            // ])
            // Optional email — Email + Length, empty_data: ''
            // ->add('email', EmailType::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => false,
            //     'empty_data' => '',
            //     'constraints' => [
            //         new Assert\Email(),
            //         new Assert\Length(max: 255),
            //     ],
            // ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Register{Entity}Request::class,
        ]);
    }
}
```

`Update{Entity}Type` is identical but uses `Update{Entity}Request::class` as `data_class` and replaces the submit button with:

```php
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
```

#### List{Entities}Type.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Symfony\Form;

use App\{Context}\Application\{Entity}\List\List{Entities}Request;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class List{Entities}Type extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // One field per filterable property
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('{Field_label}'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => List{Entities}Request::class,
        ]);
    }
}
```

### 6. Twig Templates

#### list.html.twig

```twig
{% extends '@shared/layout/list.html.twig' %}

{% set page_title = '{Entities_label}' | trans %}

{% block table %}
    {% set columns = [
        {
            'label': '{Field_label}' | trans,
            'sortable': true,
            'sortableBy': 'name'
        }, {
            'label': 'Fecha de registro' | trans,
            'sortable': true,
            'sortableBy': 'registeredAt'
        }, {
            'label': 'Fecha de actualización' | trans,
            'sortable': true,
            'sortableBy': 'updatedAt'
        }, {
            'label': ''
        },
    ] %}

    <twig:List:Table :columns="columns" :elements="{entities}" :bulkForm="bulkForm">
        <td>{{ element.name }}</td>
        <td>{{ element.registeredAt | date('d/m/Y') }}</td>
        <td>{{ element.updatedAt | date('d/m/Y') }}</td>
        <!-- If the entity has a propertyId field, display it using the property_name filter -->
        <!-- <td>{{ element.propertyId | property_name }}</td> -->
        <td>
            {% if element | is_authorized('edit') %}
                <twig:List:Action:Edit path="{{ path('admin_{entities_snake}_edit', {'{entity}Id': element.id}) }}" />
            {% endif %}

            {% if element | is_authorized('remove') %}
                <twig:List:Action:Delete path="{{ path('admin_{entities_snake}_remove', {'{entity}Id': element.id}) }}" />
            {% endif %}
        </td>
    </twig:List:Table>
{% endblock %}

**⚠️ ACTIONS COLUMN IN LIST TABLES:**
The empty column definition `{ 'label': '' }` at the end of the columns array is **required** for the action buttons (Edit and Delete). This column has no header but renders the action buttons in each row. Do not remove or rename this column.

**⚠️ PROPERTY_ID FILTER IN TEMPLATES:**
When displaying a `propertyId` field in any Twig template (list, edit, or register), always use the `property_name` Twig filter to convert the ID to a human-readable property name. This provides better UI/UX:
```twig
{{ element.propertyId | property_name }}
```
This applies to any location where you display a property reference, not just in list tables.

{% block list_actions %}
    <twig:Link:Register path="{{ path('admin_{entities_snake}_register') }}" />
{% endblock list_actions %}
```

#### register.html.twig

```twig
{% extends '@shared/layout/register.html.twig' %}

{% set page_title = ('{Entities_label}' | trans) 
    ~ ' > '
    ~ ('Registrar' | trans)  %}

{% block top_actions %}
    <twig:TopAction:Return path="{{ path('admin_{entities_snake}') }}" />
{% endblock %}
```

#### edit.html.twig

```twig
{% extends '@shared/layout/edit.html.twig' %}

{% block top_actions %}
    <twig:TopAction:Return path="{{ path('admin_{entities_snake}') }}" />
{% endblock %}

{% set page_title = ('{Entities_label}' | trans) 
    ~ ' > '
    ~ ('Editar' | trans)
    ~ ' > '
    ~ {entity}.name %}

{% block content %}
    {{ form_start(form) }}
        <twig:Card>
            <div class="row">
                <!-- One col-12 div per field -->
                <div class="col-12">
                    {{ form_row(form.name) }}
                </div>
            </div>    

            <twig:ActionBar>
                <twig:ActionBar:Button type="outline-primary" :button="form.saveAndReturn" />
                <twig:ActionBar:Button type="primary" :button="form.save" />
            </twig:ActionBar>
        </twig:Card>
    {{ form_end(form)}}
{% endblock %}
```

#### {enum_name}.html.twig (Only if you create a new Enum)
Instead of creating `.xlf` files manually, create a dummy twig template for the translation extractor:
File: `templates/{context_snake}/translations/{enum_name}.html.twig`

**Naming rule:** `{enum_name}` = **lcfirst** of the enum class name if it already ends in `Type` (e.g. `RecipientOrganizationType` → `recipientOrganizationType`), or lcfirst of `{EnumClass}Type` if it doesn't (e.g. `Priority` → `priorityType`). The name MUST start with a lowercase letter. This same value is used as the `translation_domain` in the Form type.

```twig
{{ 'EnumCase1' | trans({}, '{enum_name}') }}
{{ 'EnumCase2' | trans({}, '{enum_name}') }}
```

Don't execute translations

#### Custom Enum Form Type (required when enum is used in forms)

Whenever you create a new enum that will be used in a form field, you **must** also create a custom form type for it. **Never use `EnumType::class` directly** in form builders — it uses the enum name as the HTML value and doesn't support proper translation domains.

Place the file at: `src/{Context}/Infrastructure/Symfony/Form/Type/{EnumClass}Type.php`

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Symfony\Form\Type;

use App\{Context}\Domain\Model\{Entity}\{EnumClass};
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class {EnumClass}Type extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ({EnumClass}::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?{EnumClass} $case) => $case?->value,
            'translation_domain' => '{enum_name}',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
```

Key rules:
- `$choices[$case->value] = $case` — key is the value string (used as label for translation), value is the enum case
- `'choice_value' => fn(?{EnumClass} $case) => $case?->value` — **required** so HTML form values match the enum's string value (not integer array indices)
- `'translation_domain'` — same name as used in the `{enum_name}.html.twig` dummy translation template (see naming rule above)
- In the form builder, use it as: `->add('field', {EnumClass}Type::class, ['required' => true/false])`

### 7. Tests

#### {Entity}Mother.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;

class {Entity}Mother
{
    public static function registered{Entity}(): {Entity}
    {
        return self::fromName('name');
    }

    public static function fromName(string $name): {Entity}
    {
        return self::from(
            name: $name,
            id: (string) {Entity}Id::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): {Entity}
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function from(
        string $name,
        string $id,
    ): {Entity} {
        ${entity} = new {Entity}(
            id: new {Entity}Id($id),
        );
        ${entity}->register($name);

        return ${entity};
    }

    public static function random(): {Entity}
    {
        return self::from(
            name: '{entity}-' . rand(100, 999),
            id: (string) {Entity}Id::generate(),
        );
    }
}
```

#### {Entity}RepositoryMother.php — WITHOUT translations

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

#### {Entity}RepositoryMother.php — WITH translations

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Infrastructure\Persistence\InMemory\Repository\InMemory{Entity}Repository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;

class {Entity}RepositoryMother
{
    public static function inMemory(Locale $locale = Locale::Spanish): InMemory{Entity}Repository
    {
        FakeLocaleSetter::set($locale);

        return new InMemory{Entity}Repository();
    }
}
```

#### {Entity}RepositoryTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity}Filter;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
// If translatable:
// use App\Shared\Domain\Model\Locale\Locale;
// use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class {Entity}RepositoryTest extends KernelTestCase
{
    // If translatable:
    // public function setUp(): void
    // {
    //     FakeLocaleSetter::reset();
    //     parent::setUp();
    // }

    public function testAdd{Entity}ToRepository(): void
    {
        $repository = $this->createSUT();

        ${entity} = {Entity}Mother::registered{Entity}();

        $this->assertNull($repository->find(${entity}->id()));

        $repository->add(${entity});

        $recover{Entity} = $repository->find(${entity}->id());

        $this->assertNotNull($recover{Entity});
        $this->assertEquals(${entity}->id(), $recover{Entity}->id());
    }

    public function testRemove{Entity}ToRepository(): void
    {
        $repository = $this->createSUT();

        ${entity} = {Entity}Mother::registered{Entity}();

        $repository->add(${entity});

        $recover{Entity} = $repository->find(${entity}->id());

        $this->assertNotNull($recover{Entity});
        $this->assertEquals(${entity}->id(), $recover{Entity}->id());

        $repository->remove(${entity});

        $this->assertNull($repository->find(${entity}->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::fromName('Nombre específico'));

        $filter = new {Entity}Filter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new {Entity}Filter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::random());
        $repository->add({Entity}Mother::fromName('Nombre específico'));

        $filter = new {Entity}Filter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new {Entity}Filter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    // Only if soft delete:
    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        ${entity} = {Entity}Mother::registered{Entity}();

        $repository->add(${entity});

        $recover{Entity} = $repository->find(${entity}->id());

        $this->assertNotNull($recover{Entity});

        $recover{Entity}->remove();

        $repository->add(${entity});

        $this->assertNull($repository->find(${entity}->id()));
    }

    // Only if translatable:
    // public function testFindRecoverWithTranslation(): void
    // {
    //     $repository = $this->createSUT(Locale::English);
    //
    //     ${entity} = {Entity}Mother::registered{Entity}();
    //
    //     $this->assertNull($repository->find(${entity}->id()));
    //
    //     ${entity}->translate('translated-name', Locale::English);
    //
    //     $repository->add(${entity});
    //
    //     $recover{Entity} = $repository->find(${entity}->id());
    //
    //     $this->assertNotNull($recover{Entity});
    //     $this->assertEquals(${entity}->id(), $recover{Entity}->id());
    //
    //     $this->assertEquals('translated-name', $recover{Entity}->name());
    // }
    //
    // public function testFindByFilterWithNameTranslated(): void
    // {
    //     $repository = $this->createSUT(Locale::English);
    //
    //     ${entity} = {Entity}Mother::registered{Entity}();
    //
    //     $this->assertNull($repository->find(${entity}->id()));
    //
    //     ${entity}->translate('translated-name', Locale::English);
    //
    //     $repository->add(${entity});
    //
    //     $recover{Entities} = $repository->findByFilter(new {Entity}Filter(name: 'translated-name'));
    //
    //     $this->assertCount(1, $recover{Entities});
    //     $this->assertEquals(${entity}->id(), $recover{Entities}[0]->id());
    //
    //     $this->assertEquals('translated-name', $recover{Entities}[0]->name());
    // }

    // abstract protected function createSUT(Locale $locale = Locale::Spanish): {Entity}Repository;
    abstract protected function createSUT(): {Entity}Repository;
}
```

#### InMemory{Entity}RepositoryTest.php

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
// If translatable:
// use App\Shared\Domain\Model\Locale\Locale;

class InMemory{Entity}RepositoryTest extends {Entity}RepositoryTest
{
    // If translatable:
    // protected function createSUT(Locale $locale = Locale::Spanish): {Entity}Repository
    // {
    //     return {Entity}RepositoryMother::inMemory($locale);
    // }

    protected function createSUT(): {Entity}Repository
    {
        return {Entity}RepositoryMother::inMemory();
    }
}
```

#### Doctrine{Entity}RepositoryTest.php

Located at `tests/Unit/{Context}/Infrastructure/Persistence/Doctrine/Repository/Doctrine{Entity}RepositoryTest.php`.

For **non-translatable** entities:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Infrastructure\Persistence\Doctrine\Repository;

use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\{Context}\Infrastructure\Persistence\Doctrine\Repository\Doctrine{Entity}Repository;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class Doctrine{Entity}RepositoryTest extends {Entity}RepositoryTest
{
    protected function createSUT(): {Entity}Repository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new Doctrine{Entity}Repository($manager);
    }
}
```

For **translatable** entities, inject locale via `FakeLocaleSetter`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Infrastructure\Persistence\Doctrine\Repository;

use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\{Context}\Infrastructure\Persistence\Doctrine\Repository\Doctrine{Entity}Repository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}RepositoryTest;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Doctrine\ORM\EntityManagerInterface;

class Doctrine{Entity}RepositoryTest extends {Entity}RepositoryTest
{
    public function createSUT(Locale $locale = Locale::Spanish): {Entity}Repository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        FakeLocaleSetter::set($locale);

        return new Doctrine{Entity}Repository($manager);
    }
}
```

#### {Entity}Test.php (only if translatable)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Domain\Model\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class {Entity}Test extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        ${entity} = new {Entity}(
            id: {Entity}Id::generate(),
            name: '{entity}-name',
        );

        $this->assertCount(0, ${entity}->translations());
        ${entity}->translate('translated-name', Locale::English);
        $this->assertCount(1, ${entity}->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSetted(): void
    {
        ${entity} = new {Entity}(
            id: {Entity}Id::generate(),
            name: '{entity}-name',
        );
        ${entity}->translate('translated-name-en', Locale::English);
        ${entity}->translate('translated-name-it', Locale::Italian);
        ${entity}->translate('translated-name-ge', Locale::German);

        $this->assertEquals('{entity}-name', ${entity}->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        ${entity} = new {Entity}(
            id: {Entity}Id::generate(),
            name: '{entity}-name',
        );
        ${entity}->translate('translated-name-en', Locale::English);
        ${entity}->translate('translated-name-it', Locale::Italian);
        ${entity}->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', ${entity}->name());
    }
}
```

#### {Entity}AuthorizerTest.php

Located at `tests/Unit/{Context}/Application/{Entity}/{Entity}AuthorizerTest.php`.

Test each permission rule defined in the authorizer. Cover at minimum: the allowed roles can perform each action, disallowed roles cannot, and account ownership is enforced for edit/remove when applicable.

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
    // --- For Owner-User entities (accountId-scoped): ---

    public function testOwnerCanList{Entities}(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new {Entity}({Entity}Id::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegister{Entities}(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new {Entity}({Entity}Id::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEdit{Entity}FromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = {Entity}Mother::from(name: 'Test', id: (string) {Entity}Id::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEdit{Entity}FromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = {Entity}Mother::from(name: 'Test', id: (string) {Entity}Id::generate(), accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemove{Entity}FromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = {Entity}Mother::from(name: 'Test', id: (string) {Entity}Id::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::Owner);
        $subject = new {Entity}({Entity}Id::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new {Entity}({Entity}Id::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    // --- For Admin-only entities, replace with: ---
    // public function testAdminCanList{Entities}(): void { ... Action::List ... assertTrue }
    // public function testOwnerCannotList{Entities}(): void { ... Action::List ... assertFalse }

    private function createSUT(): {Entity}Authorizer
    {
        return new {Entity}Authorizer();
    }
}
```

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
        // ...
    }
```

#### Update, Remove, and List service tests follow the same patterns as shown in the Action tests.

### 8. Acceptance Tests

#### {entity}-register.feature

```gherkin
Feature:
    I want to make sure that the software allow to register a {entity_label}

    Scenario: As an admin, I can access to register new {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities_kebab}/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities_kebab}/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities_kebab}/register"
        When I submit a form with "name:Test Name" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{Entity_label} creado correctamente: Test Name"
```

#### {entity}-list.feature

```gherkin
Feature:
    Ability to list {entities_label} in web

    Scenario: As admin I can access a list of all {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity_label} "Item 1" with id "1111" exists
        Given {entity_label} "Item 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities_kebab}"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given {entity_label} "Item 1" with id "1111" exists
        Given {entity_label} "Item 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities_kebab}"
        Then the response status should be 403
```

#### {entity}-edit.feature

```gherkin
Feature:
    I want to make sure that the system is allow to access the details of a {entity_label}

    Scenario: As an admin, I can access to edit {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity_label} "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities_kebab}/edit/333"
        When I submit a form with "name:A new name" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{Entity_label} actualizado con éxito"

    Scenario: As an owner, I can't access to edit {entities_label}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity_label} "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/{entities_kebab}/edit/333"
        Then the response status should be 403
```

#### {entity}-remove.feature

```gherkin
Feature:
    I want to make sure that the system is allow to remove a {entity_label}

    Scenario: As an admin, I can access to remove {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity_label} "Item 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities_kebab}/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado {entity_label} Item 1"

    Scenario: As normal user I can't remove {entities_label}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given {entity_label} "Item 1" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities_kebab}/remove/333"
        Then the response status should be 403
```

#### {Context}Context.php — Add steps for new entity

Add to the existing context class in `tests/Acceptance/{Context}/{Context}Context.php`:

```php
    public function get{Entity}Repository(): {Entity}Repository
    {
        return $this->client->getContainer()->get({Entity}Repository::class);
    }

    #[Given('{entity_label} :name with id :id exists')]
    public function {entity}WithIdExists(string $name, string $id): void
    {
        ${entity} = {Entity}Mother::fromNameAndId($name, $id);

        $this->get{Entity}Repository()->add(${entity});
    }
```

### 9. Configuration Updates

After creating all files, update the following configurations:

#### config/packages/doctrine.yaml

Add the new Doctrine type under `doctrine.dbal.types`:

```yaml
{entity}_id: App\{Context}\Infrastructure\Persistence\Doctrine\Type\{Entity}IdType
```

#### config/routes.yaml

If creating a NEW bounded context (not adding to an existing one), add route groups:

```yaml
{context_kebab}-web:
    resource:
        path: ../src/{Context}/Infrastructure/UI/Web/
        namespace: App\{Context}\Infrastructure\UI\Web
    type: attribute
    prefix: /{_locale}
    requirements:
        _locale: '%app.locale_list%'
    defaults:
        _locale: '%app.locale_default%'
```

## Placeholder Legend

| Placeholder | Example | Description |
|-------------|---------|-------------|
| `{Entity}` | `Allergen` | PascalCase entity name |
| `{entity}` | `allergen` | camelCase entity name |
| `{Entities}` | `Allergens` | Pluralized PascalCase |
| `{entities}` | `allergens` | Pluralized camelCase |
| `{entity_snake}` | `allergen` | snake_case entity name |
| `{entities_snake}` | `allergens` | Pluralized snake_case |
| `{entities_kebab}` | `allergens` | kebab-case for URLs |
| `{Context}` | `FoodLoss` | PascalCase bounded context |
| `{context_snake}` | `food_loss` | snake_case context (for templates) |
| `{context_kebab}` | `food-loss` | kebab-case context (for DomainEvent) |
| `{Entity_label}` | `Alérgeno` | Human-readable label (Spanish) |
| `{entity_label}` | `alérgeno` | Lowercase human-readable label |
| `{Entities_label}` | `Alérgenos` | Pluralized human-readable label |
| `{entities_label}` | `alérgenos` | Pluralized lowercase label |
| `{Field_label}` | `Nombre` | Field label in Spanish |

## Execution Order

1. Create Domain layer files (Entity, Id, Events, Filter, Repository interface, Translation if needed)
2. Create Application layer files (Authorizer, Register, Update, Remove, List use cases)
3. Create Infrastructure layer files (Doctrine repo, InMemory repo, Doctrine type, ORM mappings)
4. Create Form types
5. Create Web controllers and templates
6. Create test files (Mother, RepositoryMother, RepositoryTest, InMemoryTest, EntityTest if translatable, service tests)
7. Create acceptance test files (.feature files, update Context class)
8. Update config files (doctrine.yaml types, routes.yaml if new context)
9. **Create event detail templates** for each new domain event (see Important Notes below)
10. Run `make cs-fix` to fix code style
11. Run `make phpstan` to check for static analysis issues

## Important Notes

- **Never create `.xlf` translation files manually.** When creating an enum, create a dummy Twig template (`templates/{context_snake}/translations/{enum_name}.html.twig`) using the `trans` filter so the translation extraction tool captures them. The `{enum_name}` (and its matching `translation_domain` in the Form type) MUST start with a **lowercase** letter: lcfirst of the enum class name if it ends in `Type` (e.g. `RecipientOrganizationType` → `recipientOrganizationType`), or lcfirst of `{EnumClass}Type` otherwise (e.g. `Priority` → `priorityType`). Don't execute translations.

- **Role-based Routing & Authorization:**
    - **Admin:** Routes MUST be prefixed with `/admin`. Controllers use the `Admin` sub-namespace. They can access all accounts and filter by `accountId`.
    - **Owner-User:** Routes MUST NOT have any prefix (direct path to `{entities_kebab}`). Controllers do not use a sub-namespace. The `accountId` MUST be forced **in the controller** (never in the service) before calling the service. Use `LoggedAccount` (not `LoggedUser`) to obtain the account, then set `$request->accountId` from `$account->id()`. The service receives the request already populated with the correct data and must not resolve the account itself.
    - **Acceptance Tests:** Features must have scenarios for both roles if applicable, ensuring correct status codes (200 vs 403) and access to correct paths.
- Each bounded context has its own `DomainEvent` base class (e.g., `App\FoodLoss\Domain\DomainEvent`) that extends `App\Shared\Domain\Event\DomainEvent` and sets `$context`
- The `DomainEvent::$context` value uses kebab-case (e.g., `food-loss`, `waste`, `property`)
- Always use `null ==` (loose comparison) when checking for null in services (project convention)
- InMemory repositories use `false ==` for boolean checks (project convention)
- Doctrine repositories use `#[AsAlias]` and `#[When]` attributes for DI wiring
- InMemory repositories use `#[When(env: 'test')]`
- The services.yaml in each context auto-wires all classes — no manual registration needed
- If the context DomainEvent base class doesn't exist yet, create it following the existing pattern
- Acceptance test steps use the `#[Given]` attribute for step definitions
- The BulkTrait handles batch operations through the CommandBusDispatcher
- When using the Acceptance Test step `When I submit a form with "data" and button "text"`, the data fields MUST be separated by a pipe (`|`), not a comma. For example: `When I submit a form with "name:Test Name|priority:High" and button "Registrar"`.
- **Acceptance Tests Structure**: ALWAYS create four separate `.feature` files (`{entity}-list.feature`, `{entity}-register.feature`, `{entity}-edit.feature`, `{entity}-remove.feature`). Do NOT combine them into a single file.
- **Twig Form Layout**: In `edit.html.twig`, ALWAYS use a single column layout (`<div class="col-12">`) for ALL form fields to ensure visual consistency with the default single-column rendering of `register.html.twig`.
- **Event detail templates**: For every domain event generated (`{Entity}Registered`, `{Entity}Updated`, `{Entity}Removed`, etc.), create a Twig template in `templates/security/admin/events/detail/` so that the audit log displays a human-readable description. The file must be named `{context_kebab}.{eventName}.html.twig` (e.g. `food-loss.actionRegistered.html.twig`). Use `{% trans with {...} %}` with the relevant payload fields and always include `'{user}': element.userId | user_name` to show the actor. If the event's payload already uses `{user}` for a target user (e.g. role grants), use `{actor}` instead for `element.userId | user_name`.
