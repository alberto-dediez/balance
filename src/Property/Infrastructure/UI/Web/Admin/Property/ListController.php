<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Property;

use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Form\AdminBulkPropertyType;
use App\Property\Infrastructure\Symfony\Form\AdminListPropertiesType;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\BulkPropertyRequest;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/properties', name: 'admin_properties')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListPropertiesService $listProperties, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::List->value, new Property(PropertyId::generate()));
        $this->denyAccessUnlessGranted(Action::Admin->value, new Property(PropertyId::generate()));

        try {
            [$filter, $form] = $this->filter($request, fn() => new ListPropertiesRequest(), AdminListPropertiesType::class);

            $response = $listProperties($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(AdminBulkPropertyType::class, new BulkPropertyRequest(), [
            'elements' => $response->properties,
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/properties/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'properties' => $response->properties,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
