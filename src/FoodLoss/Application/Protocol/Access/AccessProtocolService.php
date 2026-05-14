<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Access;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class AccessProtocolService
{
    public function __construct(
        private ProtocolRepository $protocolRepository,
        private ProtocolAuthorizer $authorizer,
    ) {}

    public function __invoke(AccessProtocolRequest $request, User $user): AccessProtocolResponse
    {
        $protocol = $this->protocolRepository->find($request->protocolId);

        if (null === $protocol) {
            throw new \InvalidArgumentException('Protocol not found');
        }

        $this->authorizer->denyUnlessCanDoThat($protocol, SecurityAction::View, $user);

        $protocol->access(accountId: $request->accountId, userId: $user->id());

        $this->protocolRepository->add($protocol);

        return new AccessProtocolResponse(path: $protocol->path());
    }
}
