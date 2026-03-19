<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Application\Country\List\ListCountriesRequest;
use App\Property\Application\Country\List\ListCountriesService;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Infrastructure\Symfony\Form\AdminListCountriesType;
use App\Property\Infrastructure\UI\Web\Admin\Country\Bulk\RemoveCountry;
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

#[Route('/admin/countries', name: 'admin_countries')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCountriesService $listCountries, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::List->value, new Country(CountryId::generate()));

        try {
            [$filter, $form] = $this->filter($request, fn() => new ListCountriesRequest(), AdminListCountriesType::class);

            $response = $listCountries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->countries,
            'actions' => [
                new RemoveCountry(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/countries/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'countries' => $response->countries,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
