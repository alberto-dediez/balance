<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Security\Application\Action;
use App\Property\Domain\Model\Brand\Brand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Brand\List\ListBrandsRequest;
use App\Property\Application\Brand\List\ListBrandsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListBrandsType;

#[Route('/admin/brands', name: 'admin_brands')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListBrandsService $listBrands, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Brand(BrandId::generate()));

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListBrandsRequest();
            }, AdminListBrandsType::class);

            $response = $listBrands($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/brands/list.html.twig', [
            'brands' => $response->brands,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
