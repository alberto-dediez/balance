<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\AlertConfiguration\Remove\RemoveAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Remove\RemoveAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/alert-configurations/remove/{alertConfigurationId}', name: 'admin_alert_configurations_remove')]
class RemoveController extends AbstractController
{
    use GetAlertConfigurationFromIdTrait;

    public function __invoke(
        string $alertConfigurationId,
        AlertConfigurationRepository $alertConfigurationRepository,
        RemoveAlertConfigurationService $removeAlertConfiguration,
        LoggedUser $loggedUser,
    ): Response {
        $config = $this->getAlertConfigurationFromId($alertConfigurationId, $alertConfigurationRepository);

        try {
            $removeAlertConfiguration(new RemoveAlertConfigurationRequest($config->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la configuración de alerta {config}', ['{config}' => $config]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la configuración de alerta {config}', ['{config}' => $config]));
        }

        return $this->redirectToRoute('admin_alert_configurations');
    }
}
