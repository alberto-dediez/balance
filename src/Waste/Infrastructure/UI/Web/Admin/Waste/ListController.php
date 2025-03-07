<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Waste\Application\Waste\List\ListWastesService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Infrastructure\Symfony\Form\ListWastesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/wastes', name: 'admin_wastes')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListWastesService $listWastes, Request $request): Response
    {   
        list($filter, $form) = $this->filter($request, function () {
            return  new ListWastesRequest();
        }, ListWastesType::class);

        $response = $listWastes($filter);

        return $this->render('@waste/admin/wastes/list.html.twig', [
            'wastes' => $response->wastes,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
