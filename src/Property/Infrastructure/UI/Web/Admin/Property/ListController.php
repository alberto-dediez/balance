<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property;

use App\Security\Application\Action;
use App\Property\Domain\Model\Property\Property;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListPropertiesType;

#[Route('/admin/properties', name: 'admin_properties')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListPropertiesService $listProperties, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Property(PropertyId::generate()));
        $this->denyAccessUnlessGranted(Action::Admin->value, new Property(PropertyId::generate()));

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListPropertiesRequest();
            }, AdminListPropertiesType::class);

            $response = $listProperties($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/properties/list.html.twig', [
            'properties' => $response->properties,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
