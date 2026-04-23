---
name: create-acceptance-tests
description: Creates Behat acceptance tests (.feature files + Context steps) for an existing AggregateRoot entity following DDD + Hexagonal Architecture patterns. Generates one .feature file per CRUD operation (register, list, edit, remove) and adds the required Given steps to the existing {Context}Context.php. Use when the user asks to "create acceptance tests", "add behat tests", "write feature files", or "add acceptance tests for [Entity]".
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make test-acceptance:*), Bash(make test-this:*), Bash(ls:*)
---

# Create Acceptance Tests

Generates Behat acceptance tests for an existing AggregateRoot entity. Produces one `.feature` file per CRUD operation and adds the matching `Given` step definitions to the bounded context's `XxxContext.php`.

## Input Format

The user must provide:
1. **Entity name** (PascalCase, e.g., `DonationAgreement`, `Action`, `CriticalPoint`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Access role** — who can perform CRUD:
   - **Admin**: routes are `/es/admin/{entities-kebab}/...`, unauthorized roles get 403
   - **Owner**: routes are `/es/{entities-kebab}/...`, scoped by `accountId`, `ROLE_USER` gets 403
   - **Owner-User**: same routes as Owner but both Owner and User can access
4. **Form fields** — which fields appear in the register/edit form (name + sample value)
5. **Success messages** (Spanish) — one per operation, or ask for them
6. **Entity has `accountId`** (yes/no) — affects list isolation and edit/remove cross-account tests

If not provided, ask before proceeding.

---

## Step 0 — Research Phase

Before generating any file, read:

```
src/{Context}/Domain/Model/{Entity}/{Entity}.php          ← constructor signature, fields
src/{Context}/Domain/Model/{Entity}/{Entity}Id.php        ← ID type
src/{Context}/Application/{Entity}/{Entity}Authorizer.php ← who can do what
src/{Context}/Infrastructure/UI/Web/{Entity}/             ← controller routes (route names, paths)
tests/Acceptance/{Context}/{Context}Context.php           ← existing Given steps (to avoid duplicates)
tests/Unit/{Context}/Domain/Model/{Entity}/{Entity}Mother.php ← factory methods available
```

Also read 1–2 analogous entities' feature files in `tests/Acceptance/{Context}/` to understand the exact wording and URL patterns used in this bounded context.

Read the authorizer to determine:
- Which roles CAN do each action (Register, List, Edit, Remove)
- Whether the entity is scoped to an `accountId`

Read the controllers to confirm:
- URL paths (e.g., `/es/admin/actions`, `/es/donation-agreements`)
- Button labels (`Registrar`, `Guardar`)
- Flash message keys → wording

---

## Step 1 — Determine URL Patterns

| Access Role | List URL | Register URL | Edit URL | Remove URL |
|-------------|----------|--------------|----------|------------|
| Admin       | `/es/admin/{entities-kebab}` | `/es/admin/{entities-kebab}/register` | `/es/admin/{entities-kebab}/edit/{id}` | `/es/admin/{entities-kebab}/remove/{id}` |
| Owner/Owner-User | `/es/{entities-kebab}` | `/es/{entities-kebab}/register` | `/es/{entities-kebab}/edit/{id}` | `/es/{entities-kebab}/remove/{id}` |

The locale prefix is always `/es/`. Entity slug is kebab-case plural (e.g., `donation-agreements`, `critical-points`, `actions`).

---

## Step 2 — Feature File Templates

### Available Steps (DO NOT invent new phrases)

These are the **only** phrases you can use. Do not create new When/Then steps — they do not exist.

#### Given (setup — defined in Context files)
```gherkin
Given user "{email}" with role "{ROLE}" exists
Given user "{email}" with role "{ROLE}" of account "{accountId}" exists
Given account "{name}" with id "{id}" exists
Given {entity} "{name/description}" with id "{id}" exists
Given {entity} "{name}" with id "{id}" of account "{accountId}" exists
Given {entity} "{name}" with id "{id}" of account "{accountId}" and property "{propertyId}" exists
Given a dummy PDF file exists in "{path}"
```

#### When (actions — defined in WebContext)
```gherkin
When I am logged as "{email}"
When I go to "{path}"
When I submit a form with "{field1}:{value1}|{field2}:{value2}" and button "{ButtonLabel}"
When I follow redirect
When I attach the file "{path}" to "{field}"
```

#### Then (assertions — defined in WebContext)
```gherkin
Then the response status should be {code}
Then I see a form
Then I see a form field "{name}"
Then I see a button with name "{name}"
Then I see a link with name "{name}"
Then I see a table with {n} rows
Then I see a text "{text}"
Then I am redirected to "{path}"
```

---

### 2a. `{entity-kebab}-register.feature`

#### Admin access pattern:

```gherkin
Feature:
    I want to make sure that the software allow to register a {entity name}

    Scenario: As an admin, I can access to register new {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities-kebab}/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities-kebab}/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities-kebab}/register"
        When I submit a form with "{field1}:{value1}|{field2}:{value2}" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
```

#### Owner/Owner-User access pattern:

```gherkin
Feature:
    I want to make sure that the software allow to register a {entity name}

    Scenario: As an admin, I can't access to register new {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/{entities-kebab}/register"
        Then the response status should be 403
    
    Scenario: As an owner, I can register a new {entity}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        # ... other prerequisites (related entities) ...
        When I am logged as "owner@diedez.software"
        When I go to "/es/{entities-kebab}/register"
        Then the response status should be 200
        When I submit a form with "{field1}:{value1}|{field2}:{value2}" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
    
    Scenario: As normal user I can't register new {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/{entities-kebab}/register"
        Then the response status should be 403
```

---

### 2b. `{entity-kebab}-list.feature`

#### Admin access pattern:

```gherkin
Feature:
    Ability to list {entity plural} in web

    Scenario: As admin I can access a list of all {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity} "{name1}" with id "1111" exists
        Given {entity} "{name2}" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities-kebab}"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities-kebab}"
        Then the response status should be 403
```

> **Note on row count:** The table always has 1 header row + 1 row per entity. So 2 entities = 3 rows, etc. If there is also a footer row, add 1 more.

#### Owner/Owner-User access pattern:

```gherkin
Feature:
    Ability to list {entity plural} in web

    Scenario: As owner I can access a list of my {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} with id "1111" of account "mycompany" exists
        Given {entity} with id "2222" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As owner I cannot see {entity plural} from other account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} with id "1111" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}"
        Then the response status should be 200
        Then I see a table with 1 rows

    Scenario: As normal user I can't access a list of {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/{entities-kebab}"
        Then the response status should be 403
```

---

### 2c. `{entity-kebab}-edit.feature`

#### Admin access pattern:

```gherkin
Feature:
    I want to make sure that the system is allow to access the details of a {entity name}

    Scenario: As an admin, I can access to edit {entity plural} of my account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity} "{name}" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities-kebab}/edit/333"
        When I submit a form with "{field1}:{value1}|{field2}:{value2}" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
    
    Scenario: As an owner, I can't access to edit {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} "{name}" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/{entities-kebab}/edit/333"
        Then the response status should be 403
```

#### Owner/Owner-User access pattern:

```gherkin
Feature:
    I want to make sure that the system is allow to access the details of a {entity name}

    Scenario: As an admin, I can't access to edit {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity} with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/{entities-kebab}/edit/333"
        Then the response status should be 403
    
    Scenario: As an owner, I can edit my {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        # ... other prerequisites ...
        Given {entity} with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}/edit/333"
        Then the response status should be 200
        When I submit a form with "{field1}:{value1}|{field2}:{value2}" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
    
    Scenario: As an owner, I cannot access to edit {entity plural} of other account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} with id "333" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}/edit/333"
        Then the response status should be 403
    
    Scenario: As normal user I can't access to edit {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given {entity} with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/{entities-kebab}/edit/333"
        Then the response status should be 403
```

---

### 2d. `{entity-kebab}-remove.feature`

#### Admin access pattern:

```gherkin
Feature:
    I want to make sure that the system is allow to remove a {entity name}

    Scenario: As an admin, I can access to remove {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity} "{name}" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/{entities-kebab}/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
    
    Scenario: As normal user I can't remove {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given {entity} "{name}" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/{entities-kebab}/remove/333"
        Then the response status should be 403
```

#### Owner/Owner-User access pattern:

```gherkin
Feature:
    I want to make sure that the system is allow to remove a {entity name}

    Scenario: As an admin, I can't access to remove {entity plural}
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given {entity} with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/{entities-kebab}/remove/333"
        Then the response status should be 403
    
    Scenario: As an owner, I can remove my {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "{success message}"
    
    Scenario: As an owner, I cannot remove {entity plural} of other account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given {entity} with id "333" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/{entities-kebab}/remove/333"
        Then the response status should be 403
    
    Scenario: As normal user I can't remove {entity plural}
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given {entity} with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/{entities-kebab}/remove/333"
        Then the response status should be 403
```

---

## Step 3 — Update {Context}Context.php

**Always edit the existing** `tests/Acceptance/{Context}/{Context}Context.php`. Never create a new Context file.

### When to add new Given steps

Add a new `#[Given(...)]` method for each step used in the feature files that doesn't already exist in the Context file.

Check the existing file first — if `donation agreement with id :id exists` already exists, **don't add a duplicate**.

### Step definition patterns

For a simple entity (no `accountId`):

```php
#[Given('{entity singular} :name with id :id exists')]
public function {entityCamel}WithIdExists(string $name, string $id): void
{
    $entity = {Entity}Mother::fromNameAndId($name, $id);
    $this->get{Entity}Repository()->add($entity);
}
```

For an entity with `accountId`:

```php
#[Given('{entity singular} with id :id of account :accountId exists')]
public function {entityCamel}WithIdOfAccountExists(string $id, string $accountId): void
{
    $entity = {Entity}Mother::from(
        id: $id,
        accountId: $accountId,
        // ... sensible defaults for other required fields
    );
    $this->get{Entity}Repository()->add($entity);
}
```

For an entity without a name field (only `id` and `accountId`):

```php
#[Given('{entity singular} with id :id exists')]
public function {entityCamel}WithIdExists(string $id): void
{
    $entity = {Entity}Mother::from(
        id: $id,
        // ... sensible defaults
    );
    $this->get{Entity}Repository()->add($entity);
}
```

### Add the repository getter if missing

```php
public function get{Entity}Repository(): {Entity}Repository
{
    return $this->client->getContainer()->get({Entity}Repository::class);
}
```

### Add the import if missing

Add at the top of the Context file:
```php
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\Tests\Unit\{Context}\Domain\Model\{Entity}\{Entity}Mother;
```

---

## Step 4 — Form Data Syntax Reference

The `When I submit a form with "..." and button "..."` step uses pipe-separated `field:value` pairs:

| Value type | Syntax | Example |
|------------|--------|---------|
| Simple string | `field:value` | `description:Test Action` |
| Integer | `field:0` | `priority:0` |
| Date (ISO) | `field:2023-01-01` | `startDate:2023-01-01` |
| Select/enum | `field:0` | `supplyChainStage:0` |
| Array (multi-select) | `field:[val1,val2]` | `serviceCategoryIds:[cat1,cat2]` |
| Related entity ID | `field:id` | `recipientOrganizationId:org1` |

Button labels:
- **Register form**: `"Registrar"`
- **Edit form**: `"Guardar"`

---

## Step 5 — Validation

After writing all files:

1. Run `make cs-fix` to fix coding style in the Context PHP file
2. Run `make test-this test=tests/Acceptance/{Context}/{entity-kebab}-register.feature` for a quick smoke test
3. If tests fail, read the error and fix — common issues:
   - Missing Given step in Context (step not found → add it)
   - Wrong URL (check controller `#[Route]` annotations)
   - Wrong button label (check Twig template)
   - Missing Mother factory method (check `{Entity}Mother.php` and add the method if needed)
   - Wrong row count in `I see a table with N rows` (count header + data + footer rows)

---

## Execution Checklist

1. **Research** — read source files listed in Step 0
2. **Determine URLs** — confirm paths from controller routes
3. **Determine success messages** — read flash message keys from controllers/templates
4. **Write `{entity-kebab}-register.feature`**
5. **Write `{entity-kebab}-list.feature`**
6. **Write `{entity-kebab}-edit.feature`**
7. **Write `{entity-kebab}-remove.feature`**
8. **Edit `{Context}Context.php`** — add Given steps for new entity (check for existing steps first)
9. Run `make cs-fix`
10. Run `make test-this` for each feature file — fix failures before finishing

---

## Key Rules

- **Every PHP file** must start with `declare(strict_types=1);`
- **Never invent new step phrases** — only use the exact When/Then phrases listed in Step 2. If a new Given step is needed for entity setup, add it to the Context PHP file.
- **Edit the existing Context file** — never create a new `{Context}Context.php`; the bounded context has one shared file
- **Check for duplicate steps** — read the existing Context before adding methods
- **Use realistic test IDs** like `"333"`, `"1111"`, `"2222"` — short strings, not UUIDs
- **Use realistic test emails** — `admin@dediez.software`, `owner@dediez.software`, `user1@dediez.software`
- **Account ID** — use `"mycompany"` and `"othercompany"` as test account IDs
- **Row count** in list tests: 1 header row + N data rows (+ 1 footer row if present). Add entities equal to N.
- **Table with 1 rows** = empty table (only header). This is valid Gherkin and matches the step definition.
- **Locale prefix** — all web URLs begin with `/es/`
- **Adapt to actual authorization rules** — read the Authorizer; if SuperAdmin can also act, add a SuperAdmin scenario; if User can act in Owner-User mode, add a User success scenario
