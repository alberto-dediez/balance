<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\GuideArticle;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use App\Tests\Unit\Changelog\Domain\Model\GuideArticle\GuideArticleMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GuideArticleAuthorizerTest extends TestCase
{
    public function testProfileOfEachRole(): void
    {
        $sut = new GuideArticleAuthorizer();

        $this->assertSame(GuideProfile::Admin, $sut->profileOf(UserMother::superAdminUser()));
        $this->assertSame(GuideProfile::Admin, $sut->profileOf(UserMother::adminUser()));
        $this->assertSame(GuideProfile::Owner, $sut->profileOf(UserMother::ownerUser()));
        $this->assertSame(GuideProfile::User, $sut->profileOf(UserMother::fromUsernameAndRole('user@dediez.software', Role::User)));
    }

    public function testAnImpersonatingAdminGetsTheOwnerProfile(): void
    {
        $sut = new GuideArticleAuthorizer(null, new class implements CurrentRolesProvider {
            public function currentRoles(User $user): array
            {
                return [Role::Owner, Role::User];
            }

            public function currentLoggedUserRoles(): array
            {
                return [Role::Owner, Role::User];
            }
        });

        $this->assertSame(GuideProfile::Owner, $sut->profileOf(UserMother::adminUser()));
    }

    public function testAnyoneCanList(): void
    {
        $sut = new GuideArticleAuthorizer();
        $article = GuideArticleMother::admin();

        $this->assertTrue($sut->canDoThat($article, Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($article, Action::List, UserMother::ownerUser()));
        $this->assertTrue($sut->canDoThat($article, Action::List, UserMother::fromUsernameAndRole('user@dediez.software', Role::User)));
    }

    public function testAUserCanOnlyViewArticlesOfTheirProfile(): void
    {
        $sut = new GuideArticleAuthorizer();
        $admin = GuideArticleMother::fromProfileAndPath(GuideProfile::Admin, 'a/b');
        $owner = GuideArticleMother::fromProfileAndPath(GuideProfile::Owner, 'a/b');

        $this->assertTrue($sut->canDoThat($admin, Action::View, UserMother::adminUser()));
        $this->assertFalse($sut->canDoThat($owner, Action::View, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($owner, Action::View, UserMother::ownerUser()));
        $this->assertFalse($sut->canDoThat($admin, Action::View, UserMother::ownerUser()));
    }

    public function testNobodyCanChangeArticles(): void
    {
        $sut = new GuideArticleAuthorizer();
        $article = GuideArticleMother::admin();
        $admin = UserMother::superAdminUser();

        $this->assertFalse($sut->canDoThat($article, Action::Register, $admin));
        $this->assertFalse($sut->canDoThat($article, Action::Edit, $admin));
        $this->assertFalse($sut->canDoThat($article, Action::Remove, $admin));
        $this->assertFalse($sut->canDoThat($article, Action::History, $admin));
    }

    public function testItOnlySupportsGuideArticles(): void
    {
        $sut = new GuideArticleAuthorizer();

        $this->assertFalse($sut->canDoThat('other', Action::List, UserMother::adminUser()));
    }
}
