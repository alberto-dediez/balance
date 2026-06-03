<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Application\AlertConfiguration\Update\UpdateAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Update\UpdateAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemData;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemType;
use App\Waste\Infrastructure\Symfony\Form\UpdateAlertConfigurationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/alert-configurations/edit/{alertConfigurationId}', name: 'admin_alert_configurations_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetAlertConfigurationFromIdTrait;

    public function __invoke(
        string $alertConfigurationId,
        AlertConfigurationRepository $alertConfigurationRepository,
        UpdateAlertConfigurationService $updateAlertConfiguration,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $config = $this->getAlertConfigurationFromId($alertConfigurationId, $alertConfigurationRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $config);

        $items = [];
        foreach ($config->items() as $item) {
            $parameters = AlertConfigurationItemType::parameterDefaults();
            $parameters[$item->type()->value] = $item->parameters();

            $items[] = new AlertConfigurationItemData(
                type: $item->type()->value,
                enabled: $item->enabled(),
                propertyId: $item->propertyId(),
                wasteId: $item->wasteId(),
                parameters: $parameters,
            );
        }

        try {
            $form = $this->createForm(UpdateAlertConfigurationType::class, ['items' => $items]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateAlertConfiguration, $loggedUser, $config) {
                /** @var array{items: list<AlertConfigurationItemData>} $data */
                $data = $form->getData();
                $updateAlertConfiguration(new UpdateAlertConfigurationRequest(
                    alertConfigurationId: $config->id(),
                    items: self::convertItemsToList($data['items']),
                ), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Configuración de alerta actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_alert_configurations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/admin/alert-configurations/edit.html.twig', [
            'alertConfiguration' => $config,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param list<AlertConfigurationItemData> $items
     *
     * @return list<AlertConfigurationItemDto>
     */
    private static function convertItemsToList(array $items): array
    {
        $list = [];
        foreach ($items as $item) {
            $type = (string) $item->type;
            $list[] = new AlertConfigurationItemDto(
                type: $type,
                enabled: $item->enabled,
                propertyId: $item->propertyId,
                wasteId: $item->wasteId,
                parameters: $item->parameters[$type] ?? [],
            );
        }

        return $list;
    }
}
