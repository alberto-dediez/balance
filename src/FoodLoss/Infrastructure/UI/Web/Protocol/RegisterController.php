<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Application\Protocol\Register\RegisterProtocolRequest;
use App\FoodLoss\Application\Protocol\Register\RegisterProtocolService;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterProtocolType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/protocols/register', name: 'protocols_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterProtocolService $registerProtocol,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(
            SecurityAction::Register->value,
            new Protocol(id: ProtocolId::generate(), accountId: $account?->id()),
        );

        assert($account instanceof Account);

        $form = $this->createForm(RegisterProtocolType::class, new RegisterProtocolRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerProtocol, $user, $account): true {
                /** @var RegisterProtocolRequest $registerRequest */
                $registerRequest = $form->getData();

                $registerRequest->accountId = $account->id();

                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();

                $registerRequest->temporaryFilePath = $file->getPathname();
                $registerRequest->fileExtension = (string) $file->guessExtension();

                $response = $registerProtocol($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Protocolo creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('protocols');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/protocol/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
