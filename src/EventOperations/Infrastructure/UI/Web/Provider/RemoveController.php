<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Provider;

use App\EventOperations\Application\Provider\Remove\RemoveProviderRequest;
use App\EventOperations\Application\Provider\Remove\RemoveProviderService;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/providers/remove/{providerId}', name: 'providers_remove')]
class RemoveController extends AbstractController
{
    use GetProviderFromIdTrait;

    public function __invoke(
        string $providerId,
        ProviderRepository $providerRepository,
        RemoveProviderService $removeProvider,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $provider = $this->getProviderFromId($providerId, $providerRepository);

        try {
            $removeProvider(new RemoveProviderRequest($provider->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el proveedor {provider}', ['{provider}' => $provider->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el proveedor {provider}', ['{provider}' => $provider->name()]));
        }

        return $this->redirectToRoute('providers');
    }
}
