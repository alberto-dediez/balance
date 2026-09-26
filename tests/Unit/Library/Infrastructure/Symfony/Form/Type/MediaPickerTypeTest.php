<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager;

class MediaPickerTypeTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $requestStack = self::getContainer()->get(RequestStack::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack->push($request);
    }

    public function testItExposesTheSelectedMedia(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $view = $this->createForm($media->id())->createView();

        $selected = $view->children['picture']->vars['selected'];

        $this->assertCount(1, $selected);
        $this->assertEquals((string) $media->id(), $selected[0]['id']);
        $this->assertTrue($selected[0]['isImage']);
        $this->assertStringContainsString((string) $media->id(), $selected[0]['url']);
    }

    public function testItExposesTheAllowedTypesToTheWidget(): void
    {
        $view = $this->createForm(null, [MediaType::Image])->createView();

        $attributes = $view->children['picture']->vars['attr'];

        $this->assertStringContainsString('media-picker', $attributes['data-controller']);
        $this->assertEquals('Image', $attributes['data-media-picker-types-value']);
        $this->assertNotEmpty($attributes['data-media-picker-search-path-value']);
        $this->assertNotEmpty($attributes['data-media-picker-upload-path-value']);
        $this->assertNotEmpty($attributes['data-media-picker-token-value']);
    }

    public function testItIssuesARealSecretToken(): void
    {
        $attributes = $this->createForm()->createView()->children['picture']->vars['attr'];

        $token = $attributes['data-media-picker-token-value'];

        $this->assertNotEquals('csrf-token', $token);
        $this->assertGreaterThanOrEqual(SameOriginCsrfTokenManager::TOKEN_MIN_LENGTH, strlen($token));
    }

    public function testItExposesTheRequiredVisibilityToTheWidget(): void
    {
        $view = $this->createForm(visibility: MediaVisibility::System)->createView();

        $attributes = $view->children['picture']->vars['attr'];

        $this->assertEquals('System', $attributes['data-media-picker-visibility-value']);
    }

    public function testTheTokenIsBoundToTheRequiredVisibility(): void
    {
        $withoutVisibility = $this->createForm()->createView()->children['picture']->vars['attr'];
        $shared = $this->createForm(visibility: MediaVisibility::System)->createView()->children['picture']->vars['attr'];

        $this->assertNotEquals(
            $withoutVisibility['data-media-picker-token-value'],
            $shared['data-media-picker-token-value'],
        );

        $this->assertEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(null));
        $this->assertEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(MediaVisibility::Account));
        $this->assertNotEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(MediaVisibility::System));
    }

    public function testThePickedMediaReachesTheRequiredVisibility(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $this->assertStringNotContainsString('No puedes usar ese archivo', (string) $form->getErrors(true));

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::System, $mediaPersisted->visibility());
    }

    public function testAFileAlreadyReachingAWiderAudienceIsLeftUntouched(): void
    {
        $media = MediaMother::fromNameAndVisibility('terms.png', MediaVisibility::Public);

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Public, $mediaPersisted->visibility());
    }

    public function testAFieldWithoutARequiredVisibilityLeavesThePickedMediaAsItIs(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm();

        $form->submit(['picture' => (string) $media->id()]);

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Account, $mediaPersisted->visibility());
    }

    public function testAFileOutOfReachOfTheUserIsRejected(): void
    {
        $media = MediaMother::fromNameAndAccount('foreign.png', AccountId::generate());

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $this->assertStringContainsString('No puedes usar ese archivo', (string) $form->getErrors(true));

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Account, $mediaPersisted->visibility());
    }

    public function testItRendersTheWidget(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $html = $this->renderWidget($this->createForm($media->id())->createView());

        $this->assertStringContainsString('data-media-picker-target="selection"', $html);
        $this->assertStringContainsString('data-media-picker-target="modal"', $html);
        $this->assertStringContainsString(sprintf('value="%s"', (string) $media->id()), $html);
        $this->assertStringContainsString('data-media-picker-target="itemTemplate"', $html);
        $this->assertStringContainsString('data-media-picker-target="dropzone"', $html);
        $this->assertStringContainsString('media-picker--card', $html);
    }

    public function testTheResultsShowTheTranslatedTypeOfEachMedia(): void
    {
        $html = $this->renderWidget($this->createForm()->createView());

        $this->assertStringContainsString('data-slot="type" data-type="Image" hidden>Imagen</span>', $html);
        $this->assertStringContainsString('data-slot="type" data-type="Document" hidden>Documento</span>', $html);
        $this->assertStringContainsString('data-slot="type" data-type="Other" hidden>Otro</span>', $html);
    }

    public function testItRendersTheAvatarPreview(): void
    {
        $html = $this->renderWidget($this->createForm(preview: 'avatar')->createView());

        $this->assertStringContainsString('media-picker--avatar', $html);
        $this->assertStringContainsString('dropzone--compact', $html);
    }

    public function testItRejectsAnUnknownPreview(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->createForm(preview: 'huge');
    }

    public function testTheClearButtonIsHiddenWhenNothingIsSelected(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $empty = $this->renderWidget($this->createForm()->createView());
        $selected = $this->renderWidget($this->createForm($media->id())->createView());

        $this->assertMatchesRegularExpression('/class="[^"]*d-none[^"]*"\s+data-media-picker-target="clear"/', $empty);
        $this->assertMatchesRegularExpression('/class="[^"]*"\s+data-media-picker-target="clear"/', $selected);
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*d-none[^"]*"\s+data-media-picker-target="clear"/', $selected);
    }

    public function testTheSubmittedValueBecomesAMediaId(): void
    {
        $mediaId = MediaId::generate();

        $form = $this->createForm();

        $form->submit(['picture' => (string) $mediaId]);

        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(MediaId::class, $form->get('picture')->getData());
    }

    /**
     * @param list<MediaType> $types
     *
     * @return FormInterface<mixed>
     */
    private function createForm(?MediaId $mediaId = null, array $types = [], ?MediaVisibility $visibility = null, string $preview = 'card'): FormInterface
    {
        $factory = self::getContainer()->get('form.factory');

        return $factory
            ->createBuilder()
            ->add('picture', MediaPickerType::class, [
                'types' => $types,
                'visibility' => $visibility,
                'data' => $mediaId,
                'preview' => $preview,
            ])
            ->getForm()
        ;
    }

    private function renderWidget(FormView $view): string
    {
        $twig = self::getContainer()->get('twig');

        return $twig
            ->createTemplate('{{ form_widget(form.picture) }}')
            ->render(['form' => $view])
        ;
    }

    private function logUser(): void
    {
        self::getContainer()->get(LoggedUser::class)->forceUser(UserMother::adminUser());
    }

    private function getRepository(): MediaRepository
    {
        return self::getContainer()->get(MediaRepository::class);
    }
}
