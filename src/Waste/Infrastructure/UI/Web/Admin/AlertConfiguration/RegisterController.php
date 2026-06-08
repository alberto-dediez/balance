<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemData;
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
            'items' => [],
        ]);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerAlertConfiguration, $loggedUser) {
                /** @var array{accountId: \App\Security\Domain\Model\Account\AccountId, items: list<AlertConfigurationItemData>} $data */
                $data = $form->getData();
                $registerAlertConfiguration(new RegisterAlertConfigurationRequest(
                    accountId: $data['accountId'],
                    items: self::convertItemsToList($data['items']),
                ), $loggedUser());

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
                priority: $item->priority,
            );
        }

        return $list;
    }
}
