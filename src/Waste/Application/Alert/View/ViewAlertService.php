<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\View;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;

class ViewAlertService
{
    public function __construct(
        private readonly AlertRepository $repository,
        private readonly AlertAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewAlertRequest $request, User $user): ViewAlertResponse
    {
        $alert = $this->repository->find($request->alertId);

        if (!$alert instanceof Alert) {
            throw new \InvalidArgumentException('Alert not found!');
        }

        $this->authorizer->denyUnlessCanDoThat($alert, Action::View, $user);

        if (AlertStatus::New === $alert->status()) {
            $alert->acknowledge();
            $this->repository->add($alert);
        }

        return new ViewAlertResponse(alert: $alert);
    }
}
