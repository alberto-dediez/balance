<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PublicLanding;

use App\CarbonFootprint\Application\PublicLanding\Register\RegisterPublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\Register\RegisterPublicLandingService;
use App\CarbonFootprint\Application\PublicLanding\Update\PublicLandingBlockData;
use App\CarbonFootprint\Application\PublicLanding\Update\UpdatePublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\Update\UpdatePublicLandingService;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingBlock;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdatePublicLandingType;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Translation\TranslatableMessage;

final readonly class PublicLandingForm
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        private RegisterPublicLandingService $registerPublicLanding,
        private UpdatePublicLandingService $updatePublicLanding,
    ) {}

    /** @return FormInterface<mixed> */
    public function handle(
        PublicLandingOwnerType $ownerType,
        string $ownerId,
        User $user,
        Request $request,
    ): FormInterface {
        $publicLanding = ($this->registerPublicLanding)(
            new RegisterPublicLandingRequest($ownerType, $ownerId),
            $user,
        )->publicLanding;

        $updateRequest = new UpdatePublicLandingRequest(
            publicLandingId: $publicLanding->id(),
            active: $publicLanding->isActive(),
            title: $publicLanding->title(),
            subtitle: $publicLanding->subtitle(),
            intro: $publicLanding->intro(),
            heroImage: $publicLanding->heroImage(),
            ctaLabel: $publicLanding->ctaLabel(),
            blocks: array_map(
                static fn(PublicLandingBlock $block): PublicLandingBlockData => new PublicLandingBlockData(
                    image: $block->image(),
                    title: $block->title(),
                    text: $block->text(),
                ),
                $publicLanding->blocks(),
            ),
        );

        $form = $this->formFactory->create(UpdatePublicLandingType::class, $updateRequest);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            ($this->updatePublicLanding)($form->getData(), $user);

            $this->addSuccessFlash();
        }

        return $form;
    }

    private function addSuccessFlash(): void
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', new TranslatableMessage('Página de bienvenida actualizada con éxito'));
        }
    }
}
