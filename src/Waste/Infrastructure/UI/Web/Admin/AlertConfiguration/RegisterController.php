<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationResponse;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationAlreadyExistsForAccountException;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
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

        $form = $this->createForm(RegisterAlertConfigurationType::class, [
            'accountId' => null,
        ]);

        $registerResponse = null;

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAlertConfiguration, $loggedUser, &$registerResponse) {
                /** @var array{accountId: \App\Security\Domain\Model\Account\AccountId} $data */
                $data = $form->getData();

                try {
                    $registerResponse = $registerAlertConfiguration(new RegisterAlertConfigurationRequest(
                        accountId: $data['accountId'],
                    ), $loggedUser());

                    $this->addFlash('success', new TranslatableMessage('Configuración de alerta creada correctamente'));

                    return true;
                } catch (AlertConfigurationAlreadyExistsForAccountException) {
                    $this->addFlash('danger', new TranslatableMessage('Ya existe una configuración de alertas para esta cuenta.'));

                    return false;
                }
            });

            if ($needsRedirect && $registerResponse instanceof RegisterAlertConfigurationResponse) {
                return $this->redirectToRoute('admin_alert_configurations_edit', [
                    'alertConfigurationId' => (string) $registerResponse->alertConfigurationId,
                ]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/alert-configurations/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
