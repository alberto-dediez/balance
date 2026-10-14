<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Application\Country\Remove\RemoveCountryRequest;
use App\Property\Application\Country\Remove\RemoveCountryService;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/countries/remove/{countryId}', name: 'admin_countries_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetCountryFromIdTrait;

    public function __invoke(
        string $countryId,
        CountryRepository $countryRepository,
        RemoveCountryService $removeCountry,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new Country(CountryId::generate()));

        $country = $this->getCountryFromId($countryId, $countryRepository);

        try {
            $removeCountry(new RemoveCountryRequest($country->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el país {country}', ['{country}' => $country->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el país {country}', ['{country}' => $country->name()]));
        }

        return $this->redirectToRoute('admin_countries');
    }
}
