---
name: create-entity-domain
description: Creates the Domain layer of a new AggregateRoot entity: model classes, domain events, repository interface, Doctrine/InMemory repository implementations, ORM mappings, Doctrine type, and all related unit tests. Step 1 of 3 in the split create-entity workflow. Use when you want fine-grained control over entity scaffolding.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make phpstan:*), Bash(ls:*)
---

# Create Entity — Domain Layer (Step 1 of 3)

Creates the Domain layer and its associated infrastructure (repositories, Doctrine type, ORM mappings) plus all related unit tests for a new AggregateRoot entity.

This skill is **Step 1** of the split workflow:
- **Step 1 — `create-entity-domain`** ← you are here
- Step 2 — `create-entity-application`
- Step 3 — `create-entity-infrastructure`

## Input Format

The user must provide:
1. **Entity name** (PascalCase, e.g., `Allergen`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Fields** with types (e.g., `name: string`, `priority: Priority(enum)`, `density: string`)
4. **Soft delete** (yes/no)
5. **Translations** (yes/no) — and which fields are translatable
6. **Access Role** (Admin / Owner-User / Both) — needed to know whether to scaffold `accountId` filtering

If the user doesn't provide all inputs, ask for the missing information before proceeding.

## Naming Conventions

Given entity name `{Entity}` in context `{Context}`:

| Element | Name Pattern |
|---------|-------------|
| Aggregate Root | `{Entity}` |
| ID Value Object | `{Entity}Id` |
| Filter | `{Entity}Filter` |
| Repository Interface | `{Entity}Repository` |
| Translation Entity | `{Entity}Translation` (if translatable) |
| Domain Events | `{Entity}Registered`, `{Entity}Updated`, `{Entity}Removed`, `{Entity}Translated` |
| Doctrine Repository | `Doctrine{Entity}Repository` |
| InMemory Repository | `InMemory{Entity}Repository` |
| Doctrine Type | `{Entity}IdType` |
| Mother | `{Entity}Mother` |
| Repository Mother | `{Entity}RepositoryMother` |

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
      {Entity}Removed.php                       # if soft delete
      {Entity}Translation.php                   # if translatable
      {Entity}Translated.php                    # if translatable
  Infrastructure/
    Persistence/
      Doctrine/
        Mapping/
          {Entity}.{Entity}.orm.xml
          {Entity}.{Entity}Translation.orm.xml  # if translatable
        Repository/
          Doctrine{Entity}Repository.php
        Type/
          {Entity}IdType.php
      InMemory/
        Repository/
          InMemory{Entity}Repository.php

tests/
  Unit/{Context}/
    Domain/Model/{Entity}/
      {Entity}Mother.php
      {Entity}RepositoryMother.php
      {Entity}RepositoryTest.php
      InMemory{Entity}RepositoryTest.php
      {Entity}Test.php                          # only if translatable
    Infrastructure/
      Persistence/
        Doctrine/
          Repository/
            Doctrine{Entity}RepositoryTest.php
```

## File Templates

### 1. Domain Model

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
        // One nullable property per filterable field
        public ?string $name = null,
        // Use value objects, not primitives (e.g. AccountId, not string)
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
```

> **Important:** Always use value objects (e.g. `?AccountId`) instead of primitives (e.g. `?string`) for filter fields when a value object is available. This ensures type safety and consistency across all layers.

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

### 2. Infrastructure — Repository Implementations

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

        // For value object filters, use instanceof check (never !empty() on value objects)
        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('e.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
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

            // For value object filters, use ->equals() not string comparison
            if (
                $filter->accountId instanceof AccountId
                && (null === ${entity}->accountId()
                    || false === $filter->accountId->equals(${entity}->accountId()))
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

    <entity name="App\{Context}\Domain\Model\{Entity}\{Entity}" table="{entity_snake}s">

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

Add the translations one-to-many alongside the standard fields:

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

    <entity name="App\{Context}\Domain\Model\{Entity}\{Entity}Translation" table="{entity_snake}_translations">
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
- Table names must be **snake_case** and always **plural** (e.g., `properties`, `critical_points`)
- **Never include the bounded context prefix** in the table name
- For translation tables: `{entity_snake}_translations` (plural, no context prefix)

### 3. Unit Tests

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
    // public function testFindRecoverWithTranslation(): void { ... }
    // public function testFindByFilterWithNameTranslated(): void { ... }

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

Lives in `tests/Unit/{Context}/Infrastructure/Persistence/Doctrine/Repository/`:

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

> **Note:** This test extends the abstract `{Entity}RepositoryTest` and runs all the same tests against a real Doctrine/SQLite setup. No mock repositories.

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

### 4. Configuration

#### config/packages/doctrine.yaml

Add the new Doctrine type under `doctrine.dbal.types`:

```yaml
{entity}_id: App\{Context}\Infrastructure\Persistence\Doctrine\Type\{Entity}IdType
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
| `{Context}` | `FoodLoss` | PascalCase bounded context |
| `{context_kebab}` | `food-loss` | kebab-case context (for DomainEvent) |

## Execution Order

1. If the bounded context `DomainEvent` base class doesn't exist, create it following the existing pattern (e.g., `src/{Context}/Domain/DomainEvent.php`)
2. Create Domain model files: `{Entity}.php`, `{Entity}Id.php`, `{Entity}Filter.php`, `{Entity}Repository.php`
3. Create Domain events: `{Entity}Registered.php`, `{Entity}Updated.php`, `{Entity}Removed.php` (if soft delete), `{Entity}Translated.php` + `{Entity}Translation.php` (if translatable)
4. Create Infrastructure: `Doctrine{Entity}Repository.php`, `InMemory{Entity}Repository.php`, `{Entity}IdType.php`, ORM mapping XML files
5. Create test files: `{Entity}Mother.php`, `{Entity}RepositoryMother.php`, `{Entity}RepositoryTest.php`, `InMemory{Entity}RepositoryTest.php`, `Doctrine{Entity}RepositoryTest.php`, `{Entity}Test.php` (if translatable)
6. Update `config/packages/doctrine.yaml` with the new type
7. Run `make cs-fix`
8. Run `make phpstan`

## Important Notes

- Each bounded context has its own `DomainEvent` base class (e.g., `App\FoodLoss\Domain\DomainEvent`) that extends `App\Shared\Domain\Event\DomainEvent` and sets `$context`
- The `DomainEvent::$context` value uses kebab-case (e.g., `food-loss`, `waste`, `property`)
- Always use `null ==` (loose comparison) when checking for null in services (project convention)
- InMemory repositories use `false ==` for boolean checks (project convention)
- Doctrine repositories use `#[AsAlias]` and `#[When]` attributes for DI wiring
- InMemory repositories use `#[When(env: 'test')]`
- The services.yaml in each context auto-wires all classes — no manual registration needed
