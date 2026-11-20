<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution;

use App\Balance\Application\ContactRequest\ContactRequestNotAvailableException;
use App\Balance\Application\ContactRequest\Register\RegisterContactRequestRequest;
use App\Balance\Application\ContactRequest\Register\RegisterContactRequestService;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyExistsException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Infrastructure\Symfony\Form\RegisterContactRequestFormData;
use App\Balance\Infrastructure\Symfony\Form\RegisterContactRequestType;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContributionContactHandler
{
    public function __construct(
        private ListContributionOptionsService $listContributionOptions,
        private RegisterContactRequestService $registerContactRequest,
        private ContributionCountries $contributionCountries,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function handle(ContributionContext $context, ContributionRoutes $routes, Request $request): ContributionContactResult
    {
        $options = ($this->listContributionOptions)(new ListContributionOptionsRequest(
            accountId: $context->accountId,
            co2eqKg: $context->co2eqKg,
            allowSharedCredit: $context->allowSharedCredit,
            propertyId: $context->propertyId,
        ));

        if (!$options->offersContact()) {
            return ContributionContactResult::redirect($this->urlGenerator->generate(
                $options->compensationEnabled ? $routes->choose : $routes->result,
                $options->compensationEnabled ? $routes->routeParams : $routes->resultRouteParams,
            ));
        }

        $data = new RegisterContactRequestFormData();
        $form = $this->formFactory->create(RegisterContactRequestType::class, $data, [
            'countries' => $this->contributionCountries->countries(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->registerContactRequest)(new RegisterContactRequestRequest(
                    contactRequestId: ContactRequestId::generate(),
                    accountId: $context->accountId,
                    propertyId: $context->propertyId,
                    origin: $context->origin,
                    originId: $context->originId,
                    originName: $context->originName,
                    co2eqKg: $context->co2eqKg,
                    contactName: trim((string) $data->name),
                    contactEmail: trim((string) $data->email),
                    contactPhone: $this->nullableString($data->phone),
                    contactCountry: (string) $data->country,
                    contactIsCompany: $data->isCompany,
                    contactCompanyName: $data->isCompany ? $this->nullableString($data->companyName) : null,
                    contactVatNumber: $data->isCompany ? $this->nullableString($data->vatNumber) : null,
                    comment: $this->nullableString($data->comment),
                    locale: LocaleGetter::locale(),
                ));
            } catch (ContactRequestNotAvailableException) {
                return ContributionContactResult::redirect($this->urlGenerator->generate($routes->choose, $routes->routeParams));
            } catch (ContactRequestAlreadyExistsException) {
                DomainEventPublisher::instance()->clearPending();

                return $this->thanks($routes);
            }

            return $this->thanks($routes);
        }

        return ContributionContactResult::render([
            'form' => $form->createView(),
            'routes' => $routes,
            'co2eqKg' => $context->co2eqKg,
            'token' => $context->token,
        ]);
    }

    private function thanks(ContributionRoutes $routes): ContributionContactResult
    {
        return ContributionContactResult::redirect($this->urlGenerator->generate((string) $routes->contactThanks, $routes->routeParams));
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
