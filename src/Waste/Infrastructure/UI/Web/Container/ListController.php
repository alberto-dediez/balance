<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\ListContainersType;
use App\Waste\Application\Container\List\ListContainersRequest;
use App\Waste\Application\Container\List\ListContainersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/containers', name: 'containers')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        ListContainersService $listContainers, 
        Request $request
    ): Response
    {   
        $user = $loggedUser();
        $accountId = $user->accountId();

        list($filter, $form) = $this->filter($request, function () use ($accountId) {
            return  new ListContainersRequest(
                accountId: $accountId,
            );
        }, ListContainersType::class);

        try {
            $response = $listContainers($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/containers/list.html.twig', [
            'containers' => $response->containers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
