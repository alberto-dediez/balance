<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;
use App\Waste\Infrastructure\Symfony\Form\ListMeasurementsType;
use App\Waste\Infrastructure\UI\Web\Measurement\Bulk\RemoveMeasurement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/measurements', name: 'measurements')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        ListMeasurementsService $listMeasurements,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        [$filter, $form] = $this->filter($request, function () use ($account, $user): ListMeasurementsRequest {
            $userId = $this->isGranted(Role::Owner->value)
                ? null
                : $user->id()
            ;

            return  new ListMeasurementsRequest(
                accountId: $account?->id(),
                userId: $userId,
            );
        }, ListMeasurementsType::class);

        try {
            $response = $listMeasurements($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->measurements,
            'actions' => [
                new RemoveMeasurement(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@waste/measurements/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'measurements' => $response->measurements,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
