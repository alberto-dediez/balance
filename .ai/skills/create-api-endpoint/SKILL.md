---
name: create-api-endpoint
description: Creates a new REST API endpoint (Controller, Request DTO, Response DTO, ResponseGenerator) for an existing AggregateRoot entity following DDD + Hexagonal Architecture patterns, and adds or extends the API acceptance test scenarios in the relevant .feature file. Use when the user asks to "add an API endpoint", "create an API route", "expose via API", or "add REST endpoint for [Entity]".
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make test-acceptance:*), Bash(make test-this:*), Bash(ls:*)
---

# Create API Endpoint

Generates all Infrastructure/UI/Api files for a new REST endpoint on an existing aggregate, and integrates the corresponding acceptance test scenarios into the entity's `.feature` file.

## Input Format

The user must provide:
1. **Entity name** (PascalCase, e.g., `Measurement`, `Container`, `Action`)
2. **Bounded context** (e.g., `Waste`, `FoodLoss`, `Property`)
3. **Operation type** — one of:
   - **View** — GET `/api/{locale}/{entities-kebab}/{id}` — returns one resource
   - **List** — GET `/api/{locale}/{entities-kebab}` — returns a collection
   - **Register** — POST `/api/{locale}/{entities-kebab}` — creates a resource
   - **Edit** — PUT `/api/{locale}/{entities-kebab}/{id}` — updates a resource
   - **Remove** — DELETE `/api/{locale}/{entities-kebab}/{id}` — deletes a resource
   - **Custom** — any other verb/path combination
4. **Request fields** (for Register/Edit): field name + PHP type + OA example value
5. **Response fields**: field name + PHP type + OA example value
6. **Access role** — who can call this endpoint:
   - **Owner-User**: `ROLE_OWNER` and `ROLE_USER` (scoped to their `accountId`)
   - **Owner**: only `ROLE_OWNER`
   - **Admin**: only `ROLE_ADMIN`
   - **Public**: no authentication required
7. **OA tag** — the OpenAPI tag string (e.g., `'Waste'`, `'FoodLoss'`)
8. **OA areas** — NelmioApiDoc areas array (default: `['mobile', 'admin']`)

If not provided, ask before proceeding.

---

## Step 0 — Research Phase

Before generating any file, read:

```
src/{Context}/Domain/Model/{Entity}/{Entity}.php              ← aggregate fields
src/{Context}/Domain/Model/{Entity}/{Entity}Id.php           ← ID type
src/{Context}/Application/{Entity}/{Entity}Authorizer.php    ← authorization rules
src/{Context}/Application/{Entity}/{UseCase}/{UseCase}Service.php ← service to call
src/{Context}/Application/{Entity}/{UseCase}/{UseCase}Request.php ← service input DTO
src/{Context}/Application/{Entity}/{UseCase}/{UseCase}Response.php ← service output DTO
src/{Context}/Infrastructure/UI/Api/                         ← existing API controllers (conventions)
tests/Acceptance/{Context}/{entity-kebab}-{operation}.feature ← existing feature file (if any)
tests/Acceptance/{Context}/{Context}Context.php              ← existing Given steps
tests/Unit/{Context}/Domain/Model/{Entity}/{Entity}Mother.php ← factory methods
```

Also read one existing API controller in the same bounded context (or `Waste` as reference) to confirm:
- `#[Areas([...])]` values used in this context
- OA attribute style
- How `$user = $this->getUser()` is cast
- Whether `LoggedUser` / `LoggedAccount` helpers are used for list endpoints

---

## Step 1 — URL Pattern

All API routes are registered under the prefix `/api/{_locale}` (see `config/routes.yaml`).
The locale in tests is always `es`.

| Operation | HTTP Method | Controller `#[Route]` | Full test URL |
|-----------|-------------|----------------------|---------------|
| View      | GET         | `/{entities-kebab}/{entityId}` | `/api/es/{entities-kebab}/{id}` |
| List      | GET         | `/{entities-kebab}` | `/api/es/{entities-kebab}` |
| Register  | POST        | `/{entities-kebab}` | `/api/es/{entities-kebab}` |
| Edit      | PUT         | `/{entities-kebab}/{entityId}` | `/api/es/{entities-kebab}/{id}` |
| Remove    | DELETE      | `/{entities-kebab}/{entityId}` | `/api/es/{entities-kebab}/{id}` |

Route name convention: `api_{entities_snake}_{operation}` (e.g., `api_measurements_register`, `api_containers_view`).

To register the new context API routes, add an entry to `config/routes.yaml` if the context has no `{context}-api:` block yet:

```yaml
{context-kebab}-api:
    resource:
        path: ../src/{Context}/Infrastructure/UI/Api/
        namespace: App\{Context}\Infrastructure\UI\Api
    type: attribute
    prefix: /api/{_locale}
    requirements:
        _locale: '%app.locale_list%'
    defaults:
        _locale: '%app.locale_default%'
```

---

## Step 2 — Files to Generate

### Directory layout

```
src/{Context}/Infrastructure/UI/Api/
  {Entity}/                          ← shared Response + ResponseGenerator (reused across operations)
    {Entity}Response.php
    {Entity}ResponseGenerator.php
  {Entity}{Operation}/               ← one folder per operation
    {Entity}{Operation}Controller.php
    {Entity}{Operation}Request.php   ← only for Register and Edit
```

---

### 2a. `{Entity}Response.php`

A plain PHP class with constructor-promoted properties annotated with `#[OA\Property]`. One property per response field.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Api\{Entity};

use OpenApi\Attributes as OA;

class {Entity}Response
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'example-value',
        )]
        public string ${fieldName},
        // ... more fields
    ) {}
}
```

**OA type mapping:**

| PHP type | `type` value |
|----------|-------------|
| `string` | `'string'` |
| `int`    | `'integer'` |
| `float`  | `'number'` |
| `bool`   | `'boolean'` |
| `array`  | `'array'` (add `items:` attribute) |

For array fields of typed objects:

```php
#[OA\Property(
    type: 'array',
    items: new OA\Items(ref: new Model(type: {Item}Response::class)),
)]
public array ${fieldName},
```

---

### 2b. `{Entity}ResponseGenerator.php`

A Symfony service that maps the domain `{Entity}` to `{Entity}Response`. Inject any Twig extension, translator, or repository needed to denormalize related IDs into display values.

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Api\{Entity};

use App\{Context}\Domain\Model\{Entity}\{Entity} as Model{Entity};
use App\{Context}\Infrastructure\UI\Api\{Entity}\{Entity}Response as Api{Entity};

class {Entity}ResponseGenerator
{
    public function __construct(
        // inject dependencies needed to resolve IDs → display values
    ) {}

    public function generate(Model{Entity} $model): Api{Entity}
    {
        return new Api{Entity}(
            // map domain fields to response fields
        );
    }
}
```

---

### 2c. `{Entity}{Operation}Request.php` (Register / Edit only)

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Api\{Entity}{Operation};

use OpenApi\Attributes as OA;

class {Entity}{Operation}Request
{
    public function __construct(
        #[OA\Property(
            type: 'string',
            example: 'example',
        )]
        public string ${fieldName},
        // ...
    ) {}
}
```

Field names use **camelCase** in PHP. The Symfony `MapRequestPayload` serializer expects **snake_case** JSON keys by default (e.g., `containerId` ↔ `container_id`).

---

### 2d. `{Entity}{Operation}Controller.php`

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Api\{Entity}{Operation};

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\{Context}\Application\{Entity}\{Operation}\{Operation}{Entity}Request;
use App\{Context}\Application\{Entity}\{Operation}\{Operation}{Entity}Service;
use App\{Context}\Domain\Model\{Entity}\{Entity};
use App\{Context}\Domain\Model\{Entity}\{Entity}Id;
use App\{Context}\Domain\Model\{Entity}\{Entity}Repository;
use App\{Context}\Infrastructure\UI\Api\{Entity}\{Entity}Response;
use App\{Context}\Infrastructure\UI\Api\{Entity}\{Entity}ResponseGenerator;
use App\{Context}\Infrastructure\UI\Api\{Entity}{Operation}\{Entity}{Operation}Request as ApiRequest;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{entities-kebab}', name: 'api_{entities_snake}_{operation}', methods: ['{HTTP_METHOD}'])]
#[Areas(['{area1}', '{area2}'])]
#[OA\{HttpVerb}(description: '{Description}', summary: '{Summary}', tags: ['{OaTag}'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of {entity}',
    content: new Model(type: {Entity}Response::class),
)]
class {Entity}{Operation}Controller extends AbstractController
{
    public function __invoke(
        // path parameter (View/Edit/Remove only):
        string ${entityId},
        // request body (Register/Edit only):
        #[MapRequestPayload()]
        ApiRequest $request,
        // application service:
        {Operation}{Entity}Service ${serviceVar},
        // repository (if re-fetching after write):
        {Entity}Repository ${repositoryVar},
        // response generator:
        {Entity}ResponseGenerator ${generatorVar},
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        // For write operations: call service, re-fetch, return response
        $response = ${serviceVar}(
            new {Operation}{Entity}Request(
                // map ApiRequest fields to service Request
            ),
            $user,
        );

        $entity = ${repositoryVar}->find($response->{entityId});
        assert($entity instanceof {Entity});

        return $this->json(
            ${generatorVar}->generate($entity),
        );
    }
}
```

**Patterns by operation type:**

#### View / Read-only (no service call, use authorizer directly):

```php
public function __invoke(
    string ${entityId},
    {Entity}Authorizer ${entity}Authorizer,
    {Entity}Repository ${entity}Repository,
    {Entity}ResponseGenerator ${entity}Generator,
): JsonResponse {
    /** @var SecurityUser $user */
    $user = $this->getUser();

    $entity = ${entity}Repository->find(new {Entity}Id(${entityId}));

    ${entity}Authorizer->denyUnlessCanDoThat($entity, Action::View, $user);

    assert($entity instanceof {Entity});

    return $this->json(${entity}Generator->generate($entity));
}
```

#### List (use `LoggedUser` / `LoggedAccount` helpers):

```php
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;

public function __invoke(
    List{Entity}Service $list{Entity},
    {Entity}ResponseGenerator ${entity}Generator,
    LoggedUser $loggedUser,
    LoggedAccount $loggedAccount,
): JsonResponse {
    $user = $loggedUser();

    $response = $list{Entity}(
        new List{Entity}Request(
            accountId: $loggedAccount()?->id(),
            // other filter params
        ),
        $user,
    );

    $items = [];
    foreach ($response->{entities} as $entity) {
        $items[] = ${entity}Generator->generate($entity);
    }

    return $this->json($items);
}
```

#### Remove (no response body needed, return empty JSON or 204):

```php
public function __invoke(
    string ${entityId},
    Remove{Entity}Service $remove{Entity},
): JsonResponse {
    /** @var SecurityUser $user */
    $user = $this->getUser();

    $remove{Entity}(
        new Remove{Entity}Request(new {Entity}Id(${entityId})),
        $user,
    );

    return $this->json([]);
}
```

---

## Step 3 — Acceptance Tests

API acceptance test scenarios live **inside the existing feature file** for that entity operation:

```
tests/Acceptance/{Context}/{entity-kebab}-{operation}.feature
```

If the file doesn't exist yet, create it following the web acceptance test conventions from the `create-acceptance-tests` skill, then append API scenarios at the end.

### Available API steps (from `ApiContext.php` and `WebContext.php`)

#### Given / When / Then steps for API:

```gherkin
# Auth
When I am authenticated in API as "{email}"

# Unauthenticated (omit the auth step; the request itself will get 403)

# Requests — no body
When I send a GET request to "/api/es/{path}"
When I send a DELETE request to "/api/es/{path}"

# Requests — with JSON body (note: use PyString block)
When I send a POST request to "/api/es/{path}" with body:
"""
{
    "field_name": "value"
}
"""
When I send a PUT request to "/api/es/{path}" with body:
"""
{
    "field_name": "value"
}
"""

# Assertions
Then the status code should be {code}
And the response JSON should be equal to:
"""
{ ... exact JSON ... }
"""
And the response JSON should be similar to:
"""
{ ... partial JSON — only keys listed are checked ... }
"""

# CORS / headers
When I make a preflight CORS request to "{path}" from "{origin}"
Then the response header "{header}" should be "{value}"
Then the response header "{header}" should exist
```

#### Notes:
- `should be equal to` — strict full-object equality after JSON normalisation
- `should be similar to` — checks only the listed keys exist and match; extra keys in response are ignored
- Use `similar to` when the response contains auto-generated IDs or timestamps that differ per run, or when you only want to assert a subset of fields
- JSON request keys are **snake_case** (PHP's `MapRequestPayload` deserialises from snake_case to camelCase)
- JSON response keys are **snake_case** (Symfony's `$this->json()` serialises camelCase properties to snake_case by default)

---

### 3a. Scenario templates

#### Register (POST)

```gherkin
Scenario: As an authenticated user, I can register a new {entity} via API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    # ... prerequisite entities ...
    When I am authenticated in API as "user@dediez.software"
    And I send a POST request to "/api/es/{entities-kebab}" with body:
    """
    {
        "field_one": "value1",
        "field_two": "value2"
    }
    """
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
        "entity_id": "any-id",
        "field_one": "value1"
    }
    """

Scenario: Not authenticated user cannot register a {entity} via API
    When I send a POST request to "/api/es/{entities-kebab}" with body:
    """
    {
        "field_one": "value1",
        "field_two": "value2"
    }
    """
    Then the status code should be 403
```

#### View (GET with id)

```gherkin
Scenario: As an authenticated user, I can view my {entity} via API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    # ... prerequisite entities ...
    Given {entity} with id "111" of account "mycompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a GET request to "/api/es/{entities-kebab}/111"
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
        "entity_id": "111",
        "field_one": "expected value"
    }
    """

Scenario: As an authenticated user, I cannot view {entity plural} from other account via API
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given {entity} with id "111" of account "othercompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a GET request to "/api/es/{entities-kebab}/111"
    Then the status code should be 403

Scenario: Not authenticated user cannot view {entity} via API
    Given {entity} with id "111" exists
    When I send a GET request to "/api/es/{entities-kebab}/111"
    Then the status code should be 403
```

#### List (GET collection)

```gherkin
Scenario: As an authenticated user, I can list my {entity plural} via API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given {entity} with id "111" of account "mycompany" exists
    Given {entity} with id "222" of account "mycompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a GET request to "/api/es/{entities-kebab}"
    Then the status code should be 200

Scenario: Not authenticated user cannot list {entity plural} via API
    When I send a GET request to "/api/es/{entities-kebab}"
    Then the status code should be 403
```

#### Edit (PUT)

```gherkin
Scenario: As an authenticated user, I can edit my {entity} via API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    # ... prerequisite entities ...
    Given {entity} with id "111" of account "mycompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a PUT request to "/api/es/{entities-kebab}/111" with body:
    """
    {
        "field_one": "updated value"
    }
    """
    Then the status code should be 200
    And the response JSON should be similar to:
    """
    {
        "entity_id": "111",
        "field_one": "updated value"
    }
    """

Scenario: As an authenticated user, I cannot edit {entity plural} from other account via API
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given {entity} with id "111" of account "othercompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a PUT request to "/api/es/{entities-kebab}/111" with body:
    """
    { "field_one": "value" }
    """
    Then the status code should be 403

Scenario: Not authenticated user cannot edit {entity} via API
    When I send a PUT request to "/api/es/{entities-kebab}/111" with body:
    """
    { "field_one": "value" }
    """
    Then the status code should be 403
```

#### Remove (DELETE)

```gherkin
Scenario: As an authenticated user, I can remove my {entity} via API
    Given account "MyCompany" with id "mycompany" exists
    Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
    Given {entity} with id "111" of account "mycompany" exists
    When I am authenticated in API as "user@dediez.software"
    And I send a DELETE request to "/api/es/{entities-kebab}/111"
    Then the status code should be 200

Scenario: Not authenticated user cannot remove {entity} via API
    When I send a DELETE request to "/api/es/{entities-kebab}/111"
    Then the status code should be 403
```

---

### 3b. Given step for user with a known id

When API tests need a specific user id (so measurements or other entities can be linked to that user), use the full step:

```gherkin
Given user "user@dediez.software" with role "ROLE_USER" with id "user" of account "mycompany" exists
```

This is defined in `SecurityContext` and sets the user's `UserId` to the given string.

---

## Step 4 — Validation

After writing all files:

1. Run `make cs-fix` on any new PHP file in `src/` and `tests/`
2. Run `make test-this test=tests/Acceptance/{Context}/{entity-kebab}-{operation}.feature`
3. If tests fail, common causes:
   - **503 / serialization error** — check that `{Entity}Response` property names match what the serializer outputs (camelCase in PHP → snake_case in JSON)
   - **Missing route** — check `config/routes.yaml` has a `{context-kebab}-api:` entry pointing to the new context's `UI/Api/` directory
   - **403 on authenticated request** — check `#[MapRequestPayload]` is correctly deserialising the body; check the Authorizer allows the role
   - **Step not found** — check the `{Context}Context.php` has the required `Given` step for the entity; add it if missing (see `create-acceptance-tests` skill)
   - **Wrong JSON key** — Symfony serialises `camelCase` to `snake_case`; verify field names in `{Entity}Response` match the expected JSON

---

## Execution Checklist

1. **Research** — read entity, authorizer, application service, existing API controllers
2. **Confirm route URL** — check `config/routes.yaml` for the context; add entry if missing
3. **Write `{Entity}Response.php`** (skip if already exists for this entity)
4. **Write `{Entity}ResponseGenerator.php`** (skip if already exists)
5. **Write `{Entity}{Operation}Request.php`** (Register/Edit only)
6. **Write `{Entity}{Operation}Controller.php`**
7. **Append API scenarios** to `tests/Acceptance/{Context}/{entity-kebab}-{operation}.feature`
8. **Add any missing `Given` steps** to `{Context}Context.php`
9. Run `make cs-fix`
10. Run `make test-this` for the feature file — fix failures before finishing

---

## Key Rules

- **Every PHP file** must start with `declare(strict_types=1);`
- **Never invent new When/Then step phrases** — only use those listed in Step 3 and in `ApiContext.php`
- **JSON request body** uses **snake_case** keys; PHP `MapRequestPayload` converts to camelCase
- **JSON response** uses **snake_case** keys automatically (Symfony serialiser)
- **Route name** must be unique across the application (`api_{entities_snake}_{operation}`)
- **`#[Areas]`** must match the context's existing convention (default: `['mobile', 'admin']`)
- **`LoggedUser` / `LoggedAccount`** helpers are for list endpoints needing the calling user's context without a path param
- **`Action::View`** is from `App\Security\Application\Action` — use for read-only authorizer checks
- **Re-fetch after write**: after a Register or Edit service call, re-fetch the entity from the repository to populate the response with fresh data
- **Unauthenticated test**: omit the `When I am authenticated in API as` step; the request will return 403 because the `/api` firewall requires `IS_AUTHENTICATED_FULLY`
- **Test emails**: `user@dediez.software`, `owner@dediez.software`, `admin@dediez.software`
- **Test IDs**: short strings like `"111"`, `"222"`, `"mycompany"`, `"othercompany"`
