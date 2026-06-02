<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemType;
use App\Waste\Infrastructure\Symfony\Form\RegisterAlertConfigurationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/alert-configurations/register', name: 'admin_alert_configurations_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterAlertConfigurationService $registerAlertConfiguration, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new AlertConfiguration(AlertConfigurationId::generate()));

        $registerRequest = new RegisterAlertConfigurationRequest(
            items: AlertConfigurationItemType::allDefaults(),
        );

        $form = $this->createForm(RegisterAlertConfigurationType::class, $registerRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAlertConfiguration, $loggedUser) {
                $registerAlertConfiguration($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Configuración de alerta creada correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_alert_configurations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/alert-configurations/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
