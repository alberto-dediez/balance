<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\Symfony\Form\ListPropertiesType;
use App\Waste\Application\Property\List\ListPropertiesRequest;
use App\Waste\Application\Property\List\ListPropertiesService;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/properties', name: 'properties')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListPropertiesService $listProperties, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }
        
        try {
            list($filter, $form) = $this->filter($request, function () use ($account) {
                return new ListPropertiesRequest(
                    accountId: $account->id(),
                );
            }, ListPropertiesType::class);

            $response = $listProperties($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@waste/properties/list.html.twig', [
            'properties' => $response->properties,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
