<?php

namespace Formwork\Tests\Unit\Panel\Modals;

use Formwork\Cms\App;
use Formwork\Config\Config;
use Formwork\Fields\FieldFactory;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Modals\ModalButton;
use Formwork\Panel\Modals\ModalCollection;
use Formwork\Panel\Modals\ModalFactory;
use Formwork\Panel\Modals\Modals;
use Formwork\Schemes\Schemes;
use Formwork\Tests\TestCase;
use Formwork\Tests\UsesApplication;
use Formwork\Tests\UsesTempDirectory;
use Formwork\Translations\Translations;
use Formwork\Utils\FileSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;

#[CoversClass(ModalFactory::class)]
#[CoversClass(Modals::class)]
#[CoversClass(ModalCollection::class)]
#[UsesApplication]
#[UsesTempDirectory]
final class ModalFactoryTest extends TestCase
{
    private string $modalsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modalsPath = TESTS_TMP_PATH . '/modals';
        FileSystem::createDirectory($this->modalsPath);
        FileSystem::write($this->modalsPath . '/confirm.yaml', "title: 'Sure?'\nmessage: 'Really?'\nsize: large\nbuttons:\n    ok:\n        label: Ok\n");
    }

    public function testModalIsCreatedFromItsDefinition(): void
    {
        $modal = $this->factory()->make('confirm');

        $this->assertInstanceOf(Modal::class, $modal);
        $this->assertSame('confirmModal', $modal->id());
        $this->assertSame('Sure?', $modal->title());
        $this->assertSame('Really?', $modal->message());
        $this->assertSame('large', $modal->size());
        $this->assertInstanceOf(ModalButton::class, $button = $modal->buttons()->get('ok'));
        $this->assertSame('Ok', $button->label());
    }

    public function testModalWithoutADefinitionIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->factory()->make('missing');
    }

    public function testModalsAreAddedByName(): void
    {
        $modals = new Modals($this->factory());

        $modals->add('confirm');

        $this->assertTrue($modals->has('confirm'));
        $this->assertCount(1, $modals);
        $this->assertInstanceOf(Modal::class, $added = $modals->get('confirm'));
        $this->assertSame('confirmModal', $added->id());
    }

    public function testModalsAreAddedOnlyOnce(): void
    {
        $modals = new Modals($this->factory());

        $modals->add('confirm');
        $first = $modals->get('confirm');
        $modals->add('confirm');

        $this->assertCount(1, $modals);
        $this->assertSame($first, $modals->get('confirm'));
    }

    private function factory(): ModalFactory
    {
        $app = App::instance();

        $config = new Config(['system' => array_replace_recursive($app->config()->get('system'), ['panel' => ['paths' => ['modals' => $this->modalsPath]]])], resolved: true);
        $translations = new Translations($config);
        $translations->loadFromPath(dirname(__DIR__, 4) . '/formwork/translations');
        $translations->setCurrent('en');

        $app->getService(Schemes::class);

        return new ModalFactory($config, $translations, $app->getService(FieldFactory::class));
    }
}
