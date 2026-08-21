<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation;

use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkService;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateScope\UpdateTripCalculationLinkScopeRequest;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateScope\UpdateTripCalculationLinkScopeService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateTripCalculationLinkScopeType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Translation\TranslatableMessage;

final readonly class TripCalculationScopeForm
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        private RegisterTripCalculationLinkService $registerTripCalculationLink,
        private UpdateTripCalculationLinkScopeService $updateTripCalculationLinkScope,
    ) {}

    /** @return FormInterface<mixed> */
    public function handle(PropertyId $propertyId, User $user, Request $request): FormInterface
    {
        $tripCalculationLink = ($this->registerTripCalculationLink)(
            new RegisterTripCalculationLinkRequest($propertyId),
            $user,
        )->tripCalculationLink;

        $form = $this->formFactory->create(
            UpdateTripCalculationLinkScopeType::class,
            new UpdateTripCalculationLinkScopeRequest(
                tripCalculationLinkId: $tripCalculationLink->id(),
                scope: $tripCalculationLink->scope(),
            ),
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            ($this->updateTripCalculationLinkScope)($form->getData(), $user);

            $this->addSuccessFlash();
        }

        return $form;
    }

    private function addSuccessFlash(): void
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', new TranslatableMessage('Calculadora actualizada con éxito'));
        }
    }
}
