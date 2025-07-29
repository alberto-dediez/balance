<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Security\Application\Action;
use App\Property\Domain\Model\Country\Country;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Country\List\ListCountriesRequest;
use App\Property\Application\Country\List\ListCountriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListCountriesType;

#[Route('/admin/countries', name: 'admin_countries')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListCountriesService $listCountries, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Country(CountryId::generate()));

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListCountriesRequest();
            }, AdminListCountriesType::class);

            $response = $listCountries($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/countries/list.html.twig', [
            'countries' => $response->countries,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
