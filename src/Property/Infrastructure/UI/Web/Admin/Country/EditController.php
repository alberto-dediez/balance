<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Application\Country\Update\UpdateCountryRequest;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateCountryType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/countries/edit/{countryId}', name: 'admin_countries_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCountryFromIdTrait;

    public function __invoke(
        string $countryId,
        CountryRepository $countryRepository,
        UpdateCountryService $updateCountry,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $country = $this->getCountryFromId($countryId, $countryRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $country);

        $countryRequest = new UpdateCountryRequest(
            countryId: $country->id(),
            name: $country->name(),
            iso: $country->iso(),
            externalId: $country->externalId(),
            vatRate: $country->vatRate(),
            europeanUnion: $country->isEuropeanUnion(),
        );

        $form = $this->createForm(AdminUpdateCountryType::class, $countryRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCountry, $loggedUser): void {
                $countryRequest = $form->getData();

                $updateCountry($countryRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('País actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_countries');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/countries/edit.html.twig', [
            'country' => $country,
            'form' => $form->createView(),
        ]);
    }
}
