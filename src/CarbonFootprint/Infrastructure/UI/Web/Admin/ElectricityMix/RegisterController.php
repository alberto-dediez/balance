<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Application\ElectricityMix\Register\RegisterElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterElectricityMixType;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-mixes/register', name: 'admin_electricity_mixes_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterElectricityMixService $registerElectricityMix, CountryRepository $countryRepository, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new ElectricityMix(ElectricityMixId::generate()));

        $form = $this->createForm(RegisterElectricityMixType::class);

        try {
            $electricityMixId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerElectricityMix, $countryRepository, $loggedUser, &$electricityMixId) {
                $registerRequest = $form->getData();

                try {
                    $response = $registerElectricityMix($registerRequest, $loggedUser());
                } catch (ElectricityMixCountryAlreadyRegisteredException) {
                    $this->addFlash('danger', new TranslatableMessage('Ya existe un mix eléctrico para ese país'));

                    return false;
                }

                $electricityMixId = $response->electricityMixId;

                $this->addFlash('success', new TranslatableMessage('Mix eléctrico creado correctamente: {name}', ['{name}' => (string) $countryRepository->find($response->countryId)?->name()]));

                return true;
            });

            if ($needsRedirect) {
                if ($electricityMixId instanceof ElectricityMixId) {
                    return $this->redirectToRoute('admin_electricity_mixes_edit', ['electricityMixId' => $electricityMixId]);
                }

                return $this->redirectToRoute('admin_electricity_mixes');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/electricity_mixes/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
