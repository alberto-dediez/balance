<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign;

use App\FoodLoss\Application\DoggyBagSign\Register\RegisterDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Register\RegisterDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterDoggyBagSignType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/doggy-bag-signs/register', name: 'doggy_bag_signs_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterDoggyBagSignService $registerDoggyBagSign,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new DoggyBagSign(
            id: DoggyBagSignId::generate(),
            accountId: $account?->id(),
        ));

        $form = $this->createForm(RegisterDoggyBagSignType::class, new RegisterDoggyBagSignRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerDoggyBagSign, $user, $account): true {
                /** @var RegisterDoggyBagSignRequest $registerRequest */
                $registerRequest = $form->getData();

                $registerRequest->accountId = $account?->id();

                /** @var UploadedFile $file */
                $file = $form->get('pdfFile')->getData();

                $registerRequest->temporaryFilePath = $file->getPathname();
                $registerRequest->fileExtension = (string) $file->guessExtension();

                $registerDoggyBagSign($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Cartel Doggy Bag creado correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('doggy_bag_signs');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/doggy-bag-signs/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
