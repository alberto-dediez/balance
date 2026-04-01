---
name: phpstan
description: This skill should be used when the user asks to "fix phpstan errors", "run phpstan", "correct static analysis errors", "fix phpstan level 8", or mentions phpstan compliance. Provides a systematic workflow for fixing all PHPStan level 8 errors in this project (DDD + Hexagonal Architecture, Symfony 7.3, PHP 8.2+).
allowed-tools: Bash(make phpstan:*), Read, Edit, Glob, Grep
---

# PHPStan Level 8 Compliance

Systematic workflow for fixing PHPStan level 8 errors in this project.

## Workflow

### 1. Generate the report

```bash
make phpstan c="analyse --error-format=json > phpstan-report.json" 2>&1
```

### 2. Group errors by `identifier`

Always fix **one identifier type at a time** — cascading fixes are common.

```bash
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

### 3. List all occurrences of a given identifier

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

## Recommended Fix Order

Fix error types in this order to minimize rework from cascades:

1. **`staleIgnored`** — clean up dead suppression rules first
2. **`missingType.parameter`** — add type hints to untyped parameters
3. **`missingType.iterableValue`** — add `@param array<T>` annotations
4. **`missingType.generics`** — add generic type params to `@var`/`@param`
5. **`varTag.nativeType`** — fix `@var MockType` → `@var MockType&MockObject`
6. **`argument.type` in `rebuildFromPayload()`** — fix `::tryFrom()` chains; causes cascades
7. **`nullCoalesce.expr`** — run after step 6; remove now-redundant `??` fallbacks
8. **`argument.type` in tests** — assertNotNull / assert for nullable getters
9. **`method.nonObject`** — assert before method calls on nullable types
10. **Infra-specific** (`DOMNode`, `openssl_*`, `sscanf()`) — fix last, they are isolated

---

## Error Types and Fix Patterns

### `argument.type` — Wrong or nullable type passed to a function

**Symptom:** `Parameter #1 $foo of type Bar expects Bar, Bar|null given.`

| Where | Fix |
|-------|-----|
| PHPUnit `TestCase` subclass | `self::assertNotNull($var)` before use |
| Behat context (no `TestCase`) | `assert($var !== null)` before use |
| Production code | Extract to variable + `assert($var !== null)` |

```php
// After (PHPUnit)
$prop = $obj->getNullableProperty();
self::assertNotNull($prop);
$service->doSomething($prop);

// After (Behat / production)
$prop = $obj->getNullableProperty();
assert($prop !== null);
$service->doSomething($prop);
```

---

### `nullCoalesce.expr` — Left side of `??` is never null

**Symptom:** `Left side of ?? is not nullable.`

**Cause:** The getter has been tightened to non-nullable (often after fixing `rebuildFromPayload()` with `assert()`), but old `??` fallbacks remain in `apply*` methods.

```php
// Before
$this->purpose = $event->purpose() ?? Purpose::Waste;

// After
$this->purpose = $event->purpose();
```

**Cascade chain:**
1. Fix `rebuildFromPayload()` with `assert($var !== null)` → event getter becomes non-nullable
2. PHPStan reports `nullCoalesce.expr` in aggregate `apply*` methods
3. Remove the `??` fallbacks there too

---

### `varTag.nativeType` — `@var` annotation incompatible with native type

**Symptom:** `PHPDoc tag @var with type Foo is not subtype of native type MockObject.`

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

Note: The `@var` annotation must come **after** the assignment.

---

### `missingType.parameter` — Method parameter has no type declaration

**Symptom:** `Method Foo::bar() has parameter $baz with no typehint specified.`

```php
// Before
public function iCreateAWaste($name, $unit)

// After
public function iCreateAWaste(string $name, string $unit)
```

For Behat step methods: `$context`, `$name`, `$value`, `$id` → `string`. Table params → `TableNode`.

---

### `missingType.iterableValue` — Array without value type annotation

**Symptom:** `Method Foo::bar() has parameter $items with no value type specified in iterable type array.`

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

```php
// Before
$result->propertyId->equals($other);

// After (PHPUnit)
self::assertNotNull($result->propertyId);
$result->propertyId->equals($other);
```

---

### `rebuildFromPayload` pattern — `::tryFrom()` returns `T|null`

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

**Symptom:** `DOMNode does not have property $tagName.`

```php
// Before
if ($element->tagName === 'input') { ... }

// After
if ($element instanceof \DOMElement && $element->tagName === 'input') { ... }
```

---

### `openssl_*` functions returning `T|false`

**Symptom:** `Parameter #1 expects OpenSSLAsymmetricKey, OpenSSLAsymmetricKey|false given.`

```php
// In PHPUnit tests
$res = openssl_pkey_new($config);
self::assertNotFalse($res);

$details = openssl_pkey_get_details($res);
self::assertNotFalse($details);

// For string results:
$keyContents = $details['key'];
self::assertNotEmpty($keyContents);
```

---

### `sscanf()` / array return types — `@var` override

```php
$color = sscanf($hex, '#%02x%02x%02x');
assert($color !== null);
/** @var array<int,int> $color */
```

---

### `staleIgnored` — Ignored error pattern no longer matches

**Symptom:** `Ignored error pattern #some regex# was not matched in reported errors.`

**Fix:** Remove the stale `ignoreErrors` entry from `phpstan.dist.neon`.

---

## Project-Specific Notes

### Aggregate event sourcing cascade

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

Mother classes often lack return types. PHPStan infers `mixed`. Fix: add explicit return types.

```php
// Before
public static function fromName(string $name)

// After
public static function fromName(string $name): Brand
```

### Code Style Warning

After editing files, `make cs-fix` may strip inline PHPDoc comments. Place `/** @var ... */` immediately before the variable usage. Re-run `make cs` after any PHPStan fix session.
