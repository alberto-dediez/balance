<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Property\Application\Brand\List\ListBrandsRequest;
use App\Property\Application\Brand\List\ListBrandsService;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Infrastructure\Symfony\Form\AdminListBrandsType;
use App\Property\Infrastructure\UI\Web\Admin\Brand\Bulk\RemoveBrand;
use App\Security\Application\Action;
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

#[Route('/admin/brands', name: 'admin_brands')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListBrandsService $listBrands, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::List->value, new Brand(BrandId::generate()));

        try {
            [$filter, $form] = $this->filter($request, fn(): ListBrandsRequest => new ListBrandsRequest(), AdminListBrandsType::class);

            $response = $listBrands($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->brands,
            'actions' => [
                new RemoveBrand(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/brands/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'brands' => $response->brands,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
