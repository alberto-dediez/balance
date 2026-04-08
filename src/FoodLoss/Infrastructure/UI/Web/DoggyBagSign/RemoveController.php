<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Application\DoggyBagSign\Remove\RemoveDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Remove\RemoveDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-signs/remove/{doggyBagSignId}', name: 'doggy_bag_signs_remove')]
class RemoveController extends AbstractController
{
    use GetDoggyBagSignFromIdTrait;

    public function __invoke(
        string $doggyBagSignId,
        DoggyBagSignRepository $doggyBagSignRepository,
        RemoveDoggyBagSignService $removeDoggyBagSign,
        LoggedUser $loggedUser,
    ): Response {
        $doggyBagSign = $this->getDoggyBagSignFromId($doggyBagSignId, $doggyBagSignRepository);

        $this->denyAccessUnlessGranted(Action::Remove->value, $doggyBagSign);

        try {
            $removeDoggyBagSign(new RemoveDoggyBagSignRequest($doggyBagSign->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el cartel {name}', ['{name}' => $doggyBagSign->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el cartel {name}', ['{name}' => $doggyBagSign->name()]));
        }

        return $this->redirectToRoute('doggy_bag_signs');
    }
}
