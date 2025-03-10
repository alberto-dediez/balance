<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Application\Property\List\ListPropertiesRequest;
use App\Waste\Application\Property\List\ListPropertiesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\Symfony\Form\AdminListPropertiesType;

#[Route('/admin/properties', name: 'admin_properties')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListPropertiesService $listProperties, Request $request): Response
    {   
        list($filter, $form) = $this->filter($request, function () {
            return  new ListPropertiesRequest();
        }, AdminListPropertiesType::class);

        $response = $listProperties($filter);

        return $this->render('@waste/admin/properties/list.html.twig', [
            'properties' => $response->properties,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
