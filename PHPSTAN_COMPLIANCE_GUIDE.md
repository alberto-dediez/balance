# PHPStan Level 8 Compliance Guide

This guide documents the systematic process for fixing PHPStan level 8 errors in this project (DDD + Hexagonal Architecture, Symfony 7.3, PHP 8.2+). It is intended to be reusable: any AI can follow this workflow on a fresh run of PHPStan errors.

---

## Workflow Overview

### 1. Generate the report

```bash
make phpstan c="analyse --error-format=json > phpstan-report.json" 2>&1
```

### 2. Group errors by `identifier`

Each error in `phpstan-report.json` has an `identifier` field (e.g., `argument.type`, `nullCoalesce.expr`). Always group and fix **one identifier type at a time** — cascading fixes are common and fixing one type can eliminate or create errors of another type.

```bash
# List all unique identifiers and their counts
cat phpstan-report.json | php -r "
  \$data = json_decode(file_get_contents('php://stdin'), true);
  \$ids = [];
  foreach (\$data['files'] as \$file) {
    foreach (\$file['messages'] as \$msg) {
      \$id = \$msg['identifier'] ?? 'unknown';
      \$ids[\$id] = (\$ids[\$id] ?? 0) + 1;
    }
  }
  arsort(\$ids);
  foreach (\$ids as \$id => \$count) echo \"\$count\t\$id\n\";
"
```

### 3. Per-identifier, list all occurrences

```bash
cat phpstan-report.json | php -r "
  \$data = json_decode(file_get_contents('php://stdin'), true);
  \$target = 'argument.type'; // <-- change this
  foreach (\$data['files'] as \$path => \$file) {
    foreach (\$file['messages'] as \$msg) {
      if ((\$msg['identifier'] ?? '') === \$target) {
        echo \$path . ':' . \$msg['line'] . ' -> ' . \$msg['message'] . \"\n\";
      }
    }
  }
"
```

### 4. Fix by pattern (see below), re-run PHPStan, repeat

---

## Error Types and Fix Patterns

### `argument.type` — Wrong or nullable type passed to a function

**Symptom:** `Parameter #1 $foo of type Bar expects Bar, Bar|null given.`

**Context matters:**

| Where | Fix |
|-------|-----|
| PHPUnit `TestCase` subclass | `self::assertNotNull($var)` before use |
| Behat context (no `TestCase`) | `assert($var !== null)` before use |
| Production code | Extract to variable + `assert($var !== null)` |

**Pattern — extract and assert:**
```php
// Before
$service->doSomething($obj->getNullableProperty());

// After (PHPUnit)
$prop = $obj->getNullableProperty();
self::assertNotNull($prop);
$service->doSomething($prop);

// After (Behat / production)
$prop = $obj->getNullableProperty();
assert($prop !== null);
$service->doSomething($prop);
```

**Important:** PHPStan only narrows types from `self::assertNotNull()` when the call is inside a `TestCase` subclass. In Behat step definitions (which extend no TestCase), use `assert()`.

---

### `nullCoalesce.expr` — Left side of `??` is never null

**Symptom:** `Left side of ?? is not nullable.`

**Cause:** The getter/expression has been tightened to return a non-nullable type (often after fixing `rebuildFromPayload()` with `assert()`), but old defensive `??` fallbacks remain in `apply*` methods.

**Fix:** Remove the `??` fallback entirely.

```php
// Before
$this->purpose = $event->purpose() ?? Purpose::Waste;

// After
$this->purpose = $event->purpose();
```

**Cascade chain to watch for:**
1. Fix `rebuildFromPayload()` to use `assert($var !== null)` → event getter becomes non-nullable
2. PHPStan now reports `nullCoalesce.expr` in aggregate `apply*` methods
3. Remove the `??` fallbacks there too

---

### `varTag.nativeType` — `@var` annotation is incompatible with native type

**Symptom:** `PHPDoc tag @var with type Foo is not subtype of native type PHPUnit\Framework\MockObject\MockObject.`

**Cause:** A mock variable is annotated with just the interface/class type, but it is actually a `MockObject` intersection.

**Fix:** Add `&MockObject` to the `@var` annotation and ensure `use PHPUnit\Framework\MockObject\MockObject` is imported.

```php
// Before
/** @var RegisterBrandService $registerService */
$registerService = $this->createMock(RegisterBrandService::class);

// After
use PHPUnit\Framework\MockObject\MockObject;
// ...
$registerService = $this->createMock(RegisterBrandService::class);
/** @var RegisterBrandService&MockObject $registerService */
```

**Note:** The `@var` annotation must come **after** the assignment (on the mock variable line), not before.

---

### `missingType.parameter` — Method parameter has no type declaration

**Symptom:** `Method Foo::bar() has parameter $baz with no typehint specified.`

**Fix:** Add PHP type declarations. For Behat step methods, all `$context`, `$name`, `$value`, `$id` params are typically `string`. For step table params use `TableNode`.

```php
// Before
public function iCreateAWaste($name, $unit)

// After
public function iCreateAWaste(string $name, string $unit)
```

---

### `missingType.iterableValue` — Array without value type annotation

**Symptom:** `Method Foo::bar() has parameter $items with no value type specified in iterable type array.`

**Fix:** Add `@param array<Type>` or `@param array<KeyType, ValueType>` PHPDoc.

```php
// Before
public function doSomething(array $items): void

// After
/** @param array<string, int> $items */
public function doSomething(array $items): void
```

For mixed/unknown content, `array<mixed>` is acceptable.

---

### `missingType.generics` — Generic class used without type parameters

**Symptom:** `Class UserProviderInterface used in @var tag does not specify its template types.`

**Fix:** Add the generic type parameter to the annotation.

```php
// Before
/** @var UserProviderInterface&MockObject $provider */

// After
/** @var UserProviderInterface<UserInterface>&MockObject $provider */
```

Check the interface's `@template` annotation to know what type parameter to supply.

---

### `method.nonObject` — Calling method on potentially null value

**Symptom:** `Cannot call method foo() on Foo|null.`

**Fix:** Add `assert($var !== null)` before the method call. In tests, prefer `self::assertNotNull($var)`.

```php
// Before
$result->propertyId->equals($other);

// After
self::assertNotNull($result->propertyId);
$result->propertyId->equals($other);
```

---

### `rebuildFromPayload` pattern — `::tryFrom()` returns `T|null`

This is a recurring pattern in domain event `rebuildFromPayload()` static methods.

**Symptom (argument.type):** `Parameter #1 $purpose expects Purpose, Purpose|null given.`

**Source:** `Purpose::tryFrom($payload['purpose'])` returns `Purpose|null`.

**Fix:** Extract to variable + `assert`:

```php
// Before
return new self(
    purpose: Purpose::tryFrom($payload['purpose']),
);

// After
$purpose = Purpose::tryFrom($payload['purpose']);
assert($purpose !== null);
return new self(
    purpose: $purpose,
);
```

Apply the same pattern to all backed enum `::tryFrom()` calls in `rebuildFromPayload()` methods.

---

### `DOMNode` vs `DOMElement` — `tagName` property

**Symptom:** `Access to property $tagName on an unknown class DOMNode.` or `DOMNode does not have property $tagName.`

**Cause:** DomCrawler returns `DOMNode`, but `$tagName` is only defined on `DOMElement`.

**Fix:** Add an `instanceof` check before accessing `$tagName`.

```php
// Before
if ($element->tagName === 'input') { ... }

// After
if ($element instanceof \DOMElement && $element->tagName === 'input') { ... }
```

---

### `openssl_*` functions returning `T|false`

**Symptom:** `Parameter #1 expects OpenSSLAsymmetricKey, OpenSSLAsymmetricKey|false given.`

**Fix:** Use `assertNotFalse()` in tests, or `assert($result !== false)` in production.

```php
// In PHPUnit tests
$res = openssl_pkey_new($config);
self::assertNotFalse($res);

$details = openssl_pkey_get_details($res);
self::assertNotFalse($details);
```

For string results (e.g., `openssl_pkey_get_details()['key']`), use `assertNotEmpty()`:
```php
$keyContents = $details['key'];
self::assertNotEmpty($keyContents);
```

---

### `sscanf()` / array return types — `@var` override

**Symptom:** `Function sscanf() return type ... does not match @return array<int,int>.`

**Fix:** Use `assert()` + `/** @var */` cast to override PHPStan's inference.

```php
$color = sscanf($hex, '#%02x%02x%02x');
assert($color !== null);
/** @var array<int,int> $color */
```

---

### `staleIgnored` — Ignored error pattern no longer matches

**Symptom (in phpstan.dist.neon):** `Ignored error pattern #some regex# was not matched in reported errors.`

**Fix:** Remove the stale `ignoreErrors` entry from `phpstan.dist.neon`. The underlying code was already fixed; the suppression is now dead weight.

```yaml
# Remove entries like:
parameters:
  ignoreErrors:
    - '#DoctrineTranslatableRepositoryTrait#'  # <-- delete if no longer needed
```

---

## Recommended Fix Order

Fix error types in this order to minimize rework from cascades:

1. **`staleIgnored`** — clean up dead suppression rules first
2. **`missingType.parameter`** — add type hints to untyped parameters (Behat steps etc.)
3. **`missingType.iterableValue`** — add `@param array<T>` annotations
4. **`missingType.generics`** — add generic type params to `@var`/`@param`
5. **`varTag.nativeType`** — fix `@var MockType` → `@var MockType&MockObject`
6. **`argument.type` in `rebuildFromPayload()`** — fix `::tryFrom()` chains; this causes cascades
7. **`nullCoalesce.expr`** — run after step 6; remove now-redundant `??` fallbacks
8. **`argument.type` in tests** — assertNotNull / assert for nullable getters
9. **`method.nonObject`** — assert before method calls on nullable types
10. **Infra-specific** (`DOMNode`, `openssl_*`, `sscanf()`) — fix last, they are isolated

---

## Project-Specific Notes

### Aggregate event sourcing cascade

In this project, aggregates use `recordApplyAndPublish()` + `apply*` methods. Domain events store enum values from `rebuildFromPayload()`. The cascade is:

```
rebuildFromPayload()        → uses ::tryFrom() → returns T|null
                              → fix: assert($var !== null)
  ↓ event getter             → now returns T (non-nullable)
    ↓ aggregate apply*()     → old: $this->field = $event->field() ?? Default::Value
                               → fix: remove ?? Default::Value (it's now unreachable)
```

### PHPUnit vs Behat context

| File location | Base class | Type narrowing |
|---------------|------------|----------------|
| `tests/Unit/**` | `PHPUnit\Framework\TestCase` | `self::assertNotNull($x)` |
| `tests/Acceptance/**` | none / Behat context | `assert($x !== null)` |

### Mother classes

Mother classes (`BrandMother`, `CategoryMother`, etc.) often lack return types. PHPStan infers `mixed` from them. Fix: add `: Brand`, `: Category`, etc. return types to all factory methods.

```php
// Before
public static function fromName(string $name)

// After
public static function fromName(string $name): Brand
```

---

## Code Style Warning

After editing files, the CS fixer (`make cs-fix`) may strip inline PHPDoc comments (`/** @var ... */`) if they are not adjacent to a declaration. To preserve them:

- Place `/** @var array<int,int> $color */` on the line immediately before the variable usage, not as a standalone block.
- Re-run `make cs` after any PHPStan fix session to verify nothing was silently reformatted.
