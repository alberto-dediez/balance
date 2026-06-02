<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\AlertConfiguration\List\ListAlertConfigurationsRequest;
use App\Waste\Application\AlertConfiguration\List\ListAlertConfigurationsService;
use App\Waste\Infrastructure\Symfony\Form\ListAlertConfigurationsType;
use App\Waste\Infrastructure\UI\Web\Admin\AlertConfiguration\Bulk\RemoveAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/alert-configurations', name: 'admin_alert_configurations')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListAlertConfigurationsService $listAlertConfigurations, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn() => new ListAlertConfigurationsRequest(), ListAlertConfigurationsType::class);

            $response = $listAlertConfigurations($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->alertConfigurations,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@waste/admin/alert-configurations/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'alertConfigurations' => $response->alertConfigurations,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
