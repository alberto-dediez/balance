---
name: create-entity-infrastructure
description: Creates the Infrastructure layer of a new AggregateRoot entity: form types, web controllers (Admin and/or User), Twig templates, acceptance tests (.feature files + Context steps), and config updates (routes.yaml if new context). Step 3 of 3 in the split create-entity workflow. Assumes Domain and Application layers have already been created.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(make cs-fix:*), Bash(make phpstan:*), Bash(ls:*)
---

# Create Entity — Infrastructure Layer (Step 3 of 3)

Creates the UI/Infrastructure layer for an existing entity whose Domain and Application layers have already been scaffolded.

This skill is **Step 3** of the split workflow:
- Step 1 — `create-entity-domain` (Domain model, repositories, tests)
- Step 2 — `create-entity-application` (use cases, authorizer, tests)
- **Step 3 — `create-entity-infrastructure`** ← you are here

## Input Format

The user must provide (or confirm from previous steps):
1. **Entity name** (PascalCase, e.g., `Allergen`)
2. **Bounded context** (e.g., `FoodLoss`, `Waste`, `Property`)
3. **Fields** — which fields appear in forms/templates
4. **Soft delete** (yes/no) — affects RemoveController and acceptance tests
5. **Translations** (yes/no) — no impact on controllers, but noted for context
6. **Access Role** (Admin / Owner-User / Both):
   - **Admin**: routes prefixed `/admin`, controllers in `Admin\` sub-namespace, can filter by `accountId`
   - **Owner-User**: routes without prefix, controllers with no sub-namespace, `accountId` forced from `LoggedAccount`
   - **Both**: generate both Admin and User controllers/templates

## Naming Conventions

| Element | Name Pattern |
|---------|-------------|
| Form Types | `Register{Entity}Type`, `Update{Entity}Type`, `List{Entities}Type` |
| Bulk Action | `RemoveAction` |
| Web Controllers | `Admin\{Entity}\ListController`, `Admin\{Entity}\RegisterController`, etc. |
| Twig Templates | `admin/{entities_kebab}/list.html.twig`, etc. |
| Behat Context | `{Context}Context` (shared per context — add steps to existing file) |

## Directory Structure

```
src/{Context}/
  Infrastructure/
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
        {Entity}/                          # only if Owner-User or Both
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
  user/{entities_kebab}/                   # only if Owner-User or Both
    list.html.twig
    register.html.twig
    edit.html.twig

tests/
  Acceptance/{Context}/
    {entity}-register.feature
    {entity}-list.feature
    {entity}-edit.feature
    {entity}-remove.feature
    # Update existing {Context}Context.php with new steps
```

## File Templates

### 1. Form Types

#### Register{Entity}Type.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Symfony\Form;

use App\{Context}\Application\{Entity}\Register\Register{Entity}Request;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            // Required string field — always NotBlank + Length
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('{Field_label}'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            // Enum field — always NotNull
            // ->add('type', {Enum}Type::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => true,
            //     'constraints' => [new Assert\NotNull()],
            // ])
            // Optional string field — always empty_data + Length
            // ->add('description', TextareaType::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => false,
            //     'empty_data' => '',
            // ])
            // Optional short string field
            // ->add('phone', TextType::class, [
            //     'label' => new TranslatableMessage('{Field_label}'),
            //     'required' => false,
            //     'empty_data' => '',
            //     'constraints' => [new Assert\Length(max: 50)],
            // ])
            // Optional email field
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

> **Validation rules:**
> - **Required string** (`name`, etc.): `NotBlank` + `Length(max: N)` matching the ORM column length
> - **Required enum**: `NotNull` constraint
> - **Optional string**: `'required' => false`, `'empty_data' => ''`, and `Length(max: N)` — the `empty_data` prevents Symfony's PropertyAccessor from throwing when mapping null to a non-nullable string property
> - **Optional email**: `Email()` + `Length(max: 255)` + `empty_data => ''`
> - **Textarea**: same as optional string but use `TextareaType::class`

#### Update{Entity}Type.php

Same structure as Register but:
- `data_class` = `Update{Entity}Request::class`
- Replace the `register` button with `save` and `saveAndReturn`:
- Same constraints must be applied to every field — do not skip them on the Update form

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

### 2. Admin Web Controllers

#### Admin\{Entity}\ListController.php

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

#### Admin\{Entity}\RegisterController.php

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

#### Admin\{Entity}\EditController.php

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

#### Admin\{Entity}\RemoveController.php

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

#### Admin\{Entity}\Get{Entity}FromIdTrait.php

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

#### Admin\{Entity}\Bulk\RemoveAction.php

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

        return new TranslatableMessage('Se ha eliminado {entity_label} {id}', ['{id}' => (string) $command->{entity}Id]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof Remove{Entity}Request);

        return new TranslatableMessage('No se ha podido eliminar {entity_label} {id}', ['{id}' => (string) $command->{entity}Id]);
    }
}
```

> **Note:** Use `RemoveActionTrait` from `App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction` — it provides the `id()` and `name()` methods. The guard in `commands()` must check `instanceof {Entity}` (not `null === ${entity}->id()`). Same pattern applies to User-facing `Bulk/RemoveAction.php`.

### 3. User Web Controllers (Owner-User or Both)

#### {Entity}\ListController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\User\{Entity};

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

#[Route('/{entities_kebab}', name: 'user_{entities_snake}')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        List{Entities}Service $list{Entities},
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        try {
            list($filter, $form) = $this->filter($request, function () use ($loggedAccount) {
                $request = new List{Entities}Request();
                // FORCE accountId from logged account — pass AccountId value object, not string
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, List{Entities}Type::class);

            $response = $list{Entities}($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/user/{entities_kebab}/list.html.twig', [
            '{entities}' => $response->{entities},
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
```

> **accountId pattern:** Always use `LoggedAccount` (not `LoggedUser`) to resolve the `accountId`. Call `$loggedAccount()?->id()` to get an `AccountId` value object (or `null` for users without an account). Assign it directly to the filter's `?AccountId $accountId` field — never cast to string.

#### {Entity}\RegisterController.php

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\UI\Web\User\{Entity};

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

#[Route('/{entities_kebab}/register', name: 'user_{entities_snake}_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        Register{Entity}Service $register{Entity},
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new {Entity}({Entity}Id::generate()));

        $form = $this->createForm(Register{Entity}Type::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($register{Entity}, $loggedUser, $loggedAccount) {
                $registerRequest = $form->getData();
                // FORCE accountId from logged account
                $registerRequest->accountId = $loggedAccount()?->id();

                $response = $register{Entity}($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('{Entity_label} creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('user_{entities_snake}');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@{context_snake}/user/{entities_kebab}/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

For User-facing `EditController`, `RemoveController`, and `Get{Entity}FromIdTrait`: follow the same structure as the Admin versions but:
- Namespace: `App\{Context}\Infrastructure\UI\Web\User\{Entity}`
- Routes without `/admin` prefix: `/{entities_kebab}/edit/{{entity}Id}`, `/{entities_kebab}/remove/{{entity}Id}`
- Route names: `user_{entities_snake}_edit`, `user_{entities_snake}_remove`, etc.
- Template paths: `@{context_snake}/user/{entities_kebab}/...`
- Inject `LoggedAccount` and force `accountId` from `$loggedAccount()?->id()` wherever needed

### 4. Twig Templates

#### admin/{entities_kebab}/list.html.twig

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

{% block list_actions %}
    <twig:Link:Register path="{{ path('admin_{entities_snake}_register') }}" />
{% endblock list_actions %}
```

**⚠️ ACTIONS COLUMN:** The empty column `{ 'label': '' }` at the end of the columns array is **required** for the action buttons. Do not remove it.

**⚠️ PROPERTY_ID FILTER:** When displaying a `propertyId` field in any template, always use the `property_name` Twig filter: `{{ element.propertyId | property_name }}`

#### admin/{entities_kebab}/register.html.twig

```twig
{% extends '@shared/layout/register.html.twig' %}

{% set page_title = ('{Entities_label}' | trans) 
    ~ ' > '
    ~ ('Registrar' | trans)  %}

{% block top_actions %}
    <twig:TopAction:Return path="{{ path('admin_{entities_snake}') }}" />
{% endblock %}
```

#### admin/{entities_kebab}/edit.html.twig

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

For user-facing templates (`user/{entities_kebab}/`): same structure but route names use `user_{entities_snake}` prefix.

#### {enum_name}.html.twig (only if you create a new Enum)

Instead of creating `.xlf` files manually, create a dummy Twig template for the translation extractor.

File path: `templates/{context_snake}/translations/{enum_name}.html.twig`

> **Naming rule:** The enum name used as translation domain and as filename must start with **lowercase** (`lcfirst`). E.g. for `RecipientOrganizationType`: domain = `recipientOrganizationType`, file = `templates/food_loss/translations/recipientOrganizationType.html.twig`.

```twig
{{ 'EnumCase1' | trans({}, '{enum_name}') }}
{{ 'EnumCase2' | trans({}, '{enum_name}') }}
```

Also set `'translation_domain' => '{enum_name}'` (lcfirst) in the Symfony Form Type that uses the enum.

Do NOT execute translations (`make translation`).

### 5. Acceptance Tests

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

Add to the **existing** context class in `tests/Acceptance/{Context}/{Context}Context.php`:

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

### 6. Configuration

#### config/routes.yaml

Only needed when adding a **new bounded context** (not adding to an existing one). Add route groups:

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
| `{context_kebab}` | `food-loss` | kebab-case context |
| `{Entity_label}` | `Alérgeno` | Human-readable label (Spanish) |
| `{entity_label}` | `alérgeno` | Lowercase human-readable label |
| `{Entities_label}` | `Alérgenos` | Pluralized human-readable label |
| `{Field_label}` | `Nombre` | Field label in Spanish |

## Execution Order

1. Create Form types: `Register{Entity}Type.php`, `Update{Entity}Type.php`, `List{Entities}Type.php`
2. Create Admin controllers: `Get{Entity}FromIdTrait.php`, `ListController.php`, `RegisterController.php`, `EditController.php`, `RemoveController.php`, `Bulk/RemoveAction.php`
3. If Owner-User or Both: create User controllers (same files, different namespace/routes/templates)
4. Create Twig templates: `list.html.twig`, `register.html.twig`, `edit.html.twig` (for each role variant)
5. If a new Enum was created: add a dummy translation template (do NOT run `make translation`)
6. Create acceptance test `.feature` files
7. Update `tests/Acceptance/{Context}/{Context}Context.php` with new steps
8. If this is a new bounded context: update `config/routes.yaml`
9. Run `make cs-fix`
10. Run `make phpstan`

## Important Notes

- **Role-based Routing:**
  - **Admin**: routes MUST be prefixed `/admin`. Controllers in `Admin\` sub-namespace.
  - **Owner-User**: routes without prefix. Controllers in no sub-namespace. Force `accountId` from `LoggedAccount`.
  - **Both**: generate both sets of controllers/templates. Admin ones for managing all; User ones for self-service.
- **accountId in Owner-User controllers:** Always force `accountId` in the **controller**, not in the service. The service receives an already-complete request. Use `LoggedAccount` (returns `?Account`) and call `$loggedAccount()?->id()` to get an `AccountId` value object. Assign it directly to `$request->accountId` — never cast to string.
- **Enum translation domain names:** Always start with **lowercase** (`lcfirst`). E.g. `RecipientOrganizationType` → domain = `recipientOrganizationType`. Set this in the Form Type's `'translation_domain'` option. Translation template files go in `templates/{context_snake}/translations/{enum_name}.html.twig`.
- **Form validation constraints:** Always add validation constraints matching ORM column lengths. Required fields: `NotBlank` + `Length(max: N)`. Required enums: `NotNull`. Optional fields: `'empty_data' => ''` + `Length(max: N)`. The `empty_data` prevents a TypeError when Symfony maps null to a non-nullable string property. Apply the same constraints to both Register and Update form types.
- **Bulk RemoveAction:** Always use `RemoveActionTrait` from `App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction`. Guard elements with `instanceof {Entity}`, not `null === ${entity}->id()`.
- **Acceptance test form data**: fields MUST be separated by a pipe (`|`), not a comma. E.g.: `When I submit a form with "name:Test Name|priority:High" and button "Registrar"`.
- **Acceptance Tests Structure**: ALWAYS create four separate `.feature` files (`{entity}-list.feature`, `{entity}-register.feature`, `{entity}-edit.feature`, `{entity}-remove.feature`). Do NOT combine them into a single file.
- **Twig Form Layout**: In `edit.html.twig`, ALWAYS use a single column layout (`<div class="col-12">`) for ALL form fields to ensure visual consistency with the default single-column rendering of `register.html.twig`.
- **Never create `.xlf` translation files manually.** Use the dummy Twig template approach for enums.
- The `BulkTrait` handles batch operations through the `CommandBusDispatcher`
- The services.yaml in each context auto-wires all classes — no manual registration needed
