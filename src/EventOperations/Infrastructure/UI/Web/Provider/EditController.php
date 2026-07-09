<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Provider;

use App\EventOperations\Application\Provider\Update\UpdateProviderRequest;
use App\EventOperations\Application\Provider\Update\UpdateProviderService;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\EventOperations\Infrastructure\Symfony\Form\UpdateProviderType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/providers/edit/{providerId}', name: 'providers_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetProviderFromIdTrait;

    public function __invoke(
        string $providerId,
        ProviderRepository $providerRepository,
        UpdateProviderService $updateProvider,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $provider = $this->getProviderFromId($providerId, $providerRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $provider);

        $providerRequest = new UpdateProviderRequest(
            providerId: $provider->id(),
            name: $provider->name(),
            vatNumber: $provider->vatNumber(),
            street: $provider->address()->street() ?? '',
            postalCode: $provider->address()->postalCode() ?? '',
            city: $provider->address()->city() ?? '',
            country: $provider->address()->country() ?? '',
            email: $provider->contact()->email(),
            preferredLocale: $provider->contact()->preferredLocale(),
            contactName: $provider->contact()->name(),
        );

        try {
            $form = $this->createForm(UpdateProviderType::class, $providerRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateProvider, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateProvider($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Proveedor actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('providers');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@event_operations/providers/edit.html.twig', [
            'provider' => $provider,
            'form' => $form->createView(),
        ]);
    }
}
