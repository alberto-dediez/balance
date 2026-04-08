<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException;

use App\FoodLoss\Application\DoggyBagException\Register\RegisterDoggyBagExceptionRequest;
use App\FoodLoss\Application\DoggyBagException\Register\RegisterDoggyBagExceptionService;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterDoggyBagExceptionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-exceptions/register', name: 'doggy_bag_exceptions_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterDoggyBagExceptionService $registerDoggyBagException, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new DoggyBagException(DoggyBagExceptionId::generate(), accountId: $loggedAccount()?->id()));

        $form = $this->createForm(RegisterDoggyBagExceptionType::class, new RegisterDoggyBagExceptionRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerDoggyBagException, $loggedUser, $loggedAccount) {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $loggedAccount()?->id();

                $response = $registerDoggyBagException($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Excepción Doggy Bag creada correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('doggy_bag_exceptions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/doggy-bag-exceptions/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
