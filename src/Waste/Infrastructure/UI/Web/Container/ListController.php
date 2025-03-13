<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Waste\Infrastructure\Symfony\Form\ListContainersType;
use App\Waste\Application\Container\List\ListContainersRequest;
use App\Waste\Application\Container\List\ListContainersService;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/containers', name: 'containers')]
#[IsGranted("ROLE_OWNER")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        ListContainersService $listContainers, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }

        list($filter, $form) = $this->filter($request, function () use ($account) {
            return  new ListContainersRequest(
                account: $account,
            );
        }, ListContainersType::class);

        $response = $listContainers($filter);

        return $this->render('@waste/containers/list.html.twig', [
            'containers' => $response->containers,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
