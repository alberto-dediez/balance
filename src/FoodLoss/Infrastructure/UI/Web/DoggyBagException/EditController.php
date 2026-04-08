<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException;

use App\FoodLoss\Application\DoggyBagException\Update\UpdateDoggyBagExceptionRequest;
use App\FoodLoss\Application\DoggyBagException\Update\UpdateDoggyBagExceptionService;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateDoggyBagExceptionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-exceptions/edit/{doggyBagExceptionId}', name: 'doggy_bag_exceptions_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetDoggyBagExceptionFromIdTrait;

    public function __invoke(
        string $doggyBagExceptionId,
        DoggyBagExceptionRepository $doggyBagExceptionRepository,
        UpdateDoggyBagExceptionService $updateDoggyBagException,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $doggyBagException = $this->getDoggyBagExceptionFromId($doggyBagExceptionId, $doggyBagExceptionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $doggyBagException);

        $doggyBagExceptionRequest = new UpdateDoggyBagExceptionRequest(
            doggyBagExceptionId: $doggyBagException->id(),
            description: $doggyBagException->description(),
            criticalPointId: $doggyBagException->criticalPointId(),
        );

        try {
            $form = $this->createForm(UpdateDoggyBagExceptionType::class, $doggyBagExceptionRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDoggyBagException, $loggedUser) {
                $doggyBagExceptionRequest = $form->getData();

                $updateDoggyBagException($doggyBagExceptionRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Excepción Doggy Bag actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('doggy_bag_exceptions');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/doggy-bag-exceptions/edit.html.twig', [
            'doggyBagException' => $doggyBagException,
            'form' => $form->createView(),
        ]);
    }
}
