<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Infrastructure\Symfony\Form\ListMeasurementsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;

#[Route('/measurements', name: 'measurements')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        ListMeasurementsService $listMeasurements, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();
        $user = $loggedUser();

        list($filter, $form) = $this->filter($request, function () use ($account, $user) {
            $userId = $this->isGranted(Role::Owner->value)
                ? null
                : $user->id()
            ;

            return  new ListMeasurementsRequest(
                accountId: $account?->id(),
                userId: $userId
            );
        }, ListMeasurementsType::class);

        try {
            $response = $listMeasurements($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/measurements/list.html.twig', [
            'measurements' => $response->measurements,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
