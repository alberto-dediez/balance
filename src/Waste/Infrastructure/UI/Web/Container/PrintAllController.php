<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Security\Domain\Model\Account\Account;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerRequest;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerService;
use App\Waste\Domain\Model\Container\NotValidContainersForPrintException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/containers/print-all', name: 'containers_print_all')]
class PrintAllController extends AbstractController
{
    public function __invoke(
        PrintAllQrContainerService $printAllContainer,
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        assert($account instanceof Account);

        $containerRequest = new PrintAllQrContainerRequest(
            accountId: $account->id(),
        );

        try {
            $response = $printAllContainer($containerRequest, $loggedUser());

            if (empty($response->path)) {
                throw new \RuntimeException('Invalid response');
            }

            return $this->file($response->path, 'print_all.pdf', ResponseHeaderBag::DISPOSITION_INLINE);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (NotValidContainersForPrintException) {
            $this->addFlash('danger', new TranslatableMessage('No hay contenedores disponibles para impresión'));
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido generar los carteles de los contenedores de la cuenta'));
        }

        return $this->redirectToRoute('containers');
    }
}
