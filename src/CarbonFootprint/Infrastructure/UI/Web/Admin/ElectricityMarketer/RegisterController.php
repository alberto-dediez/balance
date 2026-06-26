<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer;

use App\CarbonFootprint\Application\ElectricityMarketer\Register\RegisterElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterElectricityMarketerType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/electricity-marketers/register', name: 'admin_electricity_marketers_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterElectricityMarketerService $registerElectricityMarketer, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new ElectricityMarketer(ElectricityMarketerId::generate()));

        $form = $this->createForm(RegisterElectricityMarketerType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerElectricityMarketer, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerElectricityMarketer($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Comercializadora eléctrica creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_electricity_marketers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/electricity_marketers/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
