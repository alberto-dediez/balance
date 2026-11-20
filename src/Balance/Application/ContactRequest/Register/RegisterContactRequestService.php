<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\Register;

use App\Balance\Application\ContactRequest\ContactRequestNotAvailableException;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;

final class RegisterContactRequestService
{
    public function __construct(
        private ContactRequestRepository $contactRequestRepository,
        private ListContributionOptionsService $listContributionOptions,
    ) {}

    public function __invoke(RegisterContactRequestRequest $request): RegisterContactRequestResponse
    {
        $options = ($this->listContributionOptions)(new ListContributionOptionsRequest(
            accountId: $request->accountId,
            co2eqKg: $request->co2eqKg,
            allowSharedCredit: true,
            propertyId: $request->propertyId,
            now: $request->now,
        ));

        if (!$options->offersContact()) {
            throw new ContactRequestNotAvailableException();
        }

        $existing = $this->contactRequestRepository->findOneByOrigin($request->origin, $request->originId);

        if ($existing instanceof ContactRequest) {
            if ($existing->isPending()) {
                $existing->updateContactData(
                    contactName: $request->contactName,
                    contactEmail: $request->contactEmail,
                    contactPhone: $request->contactPhone,
                    contactCountry: $request->contactCountry,
                    contactIsCompany: $request->contactIsCompany,
                    contactCompanyName: $request->contactCompanyName,
                    contactVatNumber: $request->contactVatNumber,
                    comment: $request->comment,
                    locale: $request->locale,
                );

                $this->contactRequestRepository->add($existing);
            }

            return new RegisterContactRequestResponse($existing, created: false);
        }

        $contactRequest = new ContactRequest(id: $request->contactRequestId);
        $contactRequest->register(
            accountId: $request->accountId,
            propertyId: $request->propertyId,
            origin: $request->origin,
            originId: $request->originId,
            originName: $request->originName,
            co2eqKg: $request->co2eqKg,
            contactName: $request->contactName,
            contactEmail: $request->contactEmail,
            contactPhone: $request->contactPhone,
            contactCountry: $request->contactCountry,
            contactIsCompany: $request->contactIsCompany,
            contactCompanyName: $request->contactCompanyName,
            contactVatNumber: $request->contactVatNumber,
            comment: $request->comment,
            locale: $request->locale,
            requestedAt: $request->now ?? new \DateTimeImmutable('NOW'),
        );

        $this->contactRequestRepository->add($contactRequest);

        return new RegisterContactRequestResponse($contactRequest, created: true);
    }
}
