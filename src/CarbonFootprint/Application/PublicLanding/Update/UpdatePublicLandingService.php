<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\Update;

use App\CarbonFootprint\Application\PublicLanding\PublicLandingAuthorizer;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdatePublicLandingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PublicLandingRepository $publicLandingRepository,
        private PublicLandingAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdatePublicLandingRequest $request, User $user): UpdatePublicLandingResponse
    {
        $publicLanding = $this->publicLandingRepository->find($request->publicLandingId);

        if (null == $publicLanding) {
            throw new \InvalidArgumentException('PublicLanding not found');
        }

        $this->authorizer->denyUnlessCanDoThat($publicLanding, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $translating = $locale !== LocaleGetter::default();

        $publicLanding->update(
            active: $request->active,
            title: $request->title,
            subtitle: $request->subtitle,
            intro: $request->intro,
            heroImage: $request->heroImage,
            ctaLabel: $request->ctaLabel,
            locale: $translating ? $locale : null,
        );

        $blocks = array_values(array_filter(
            $request->blocks,
            static fn(PublicLandingBlockData $block): bool => !$block->isEmpty(),
        ));

        foreach ($blocks as $position => $block) {
            $publicLanding->setBlock(
                position: $position,
                image: $block->image,
                title: $block->title,
                text: $block->text,
                locale: $translating ? $locale : null,
            );
        }

        $publicLanding->truncateBlocks(count($blocks));

        $this->publicLandingRepository->add($publicLanding);

        return new UpdatePublicLandingResponse($publicLanding);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePublicLandingRequest;
    }
}
