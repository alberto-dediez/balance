<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException;

use App\FoodLoss\Application\DoggyBagException\Remove\RemoveDoggyBagExceptionRequest;
use App\FoodLoss\Application\DoggyBagException\Remove\RemoveDoggyBagExceptionService;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-exceptions/remove/{doggyBagExceptionId}', name: 'doggy_bag_exceptions_remove')]
class RemoveController extends AbstractController
{
    use GetDoggyBagExceptionFromIdTrait;

    public function __invoke(
        string $doggyBagExceptionId,
        DoggyBagExceptionRepository $doggyBagExceptionRepository,
        RemoveDoggyBagExceptionService $removeDoggyBagException,
        LoggedUser $loggedUser,
    ): Response {
        $doggyBagException = $this->getDoggyBagExceptionFromId($doggyBagExceptionId, $doggyBagExceptionRepository);

        try {
            $removeDoggyBagException(new RemoveDoggyBagExceptionRequest($doggyBagException->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la excepción Doggy Bag'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la excepción Doggy Bag'));
        }

        return $this->redirectToRoute('doggy_bag_exceptions');
    }
}
