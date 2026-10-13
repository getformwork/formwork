<?php

namespace Formwork\Tests\Unit\Schemes;

use Formwork\Exceptions\RecursionException;
use Formwork\Schemes\Scheme;
use Formwork\Tests\TestCase;
use Formwork\Tests\Unit\Schemes\Fixtures\BuildsSchemes;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Scheme::class)]
final class SchemeTest extends TestCase
{
    use BuildsSchemes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSchemes();
    }

    protected function tearDown(): void
    {
        $this->tearDownSchemes();
        parent::tearDown();
    }

    // Identity, title, options

    public function testIdIsReturned(): void
    {
        $this->assertSame('pages.post', $this->makeScheme('pages.post', [])->id());
    }

    public function testTitleDefaultsToTheId(): void
    {
        $this->assertSame('pages.post', $this->makeScheme('pages.post', [])->title());
    }

    public function testPlainTitleIsReturned(): void
    {
        $this->assertSame('Blog post', $this->makeScheme('post', ['title' => 'Blog post'])->title());
    }

    public function testTitleInterpolatesTranslationStrings(): void
    {
        $this->assertSame('Hello world', $this->makeScheme('post', ['title' => '{{greeting}} world'])->title());
    }

    public function testTitleUsesTheCurrentLanguage(): void
    {
        $this->translations->setCurrent('it');

        $this->assertSame('Ciao world', $this->makeScheme('post', ['title' => '{{greeting}} world'])->title());
    }

    public function testTitleFallsBackToTheDefaultLanguageStrings(): void
    {
        $this->translations->setCurrent('it');

        $this->assertSame('English only', $this->makeScheme('post', ['title' => '{{only.english}}'])->title());
    }

    public function testTitleCanBeDefinedPerLanguage(): void
    {
        $scheme = $this->makeScheme('post', ['title' => ['en' => 'Post', 'it' => 'Articolo']]);
        $this->translations->setCurrent('it');

        $this->assertSame('Articolo', $scheme->title());
    }

    public function testTitleWithUnknownTranslationKeyIsKeptVerbatim(): void
    {
        $this->assertSame('{{no.such.key}}', $this->makeScheme('post', ['title' => '{{no.such.key}}'])->title());
    }

    public function testPerLanguageTitleWithUnknownTranslationKeyIsHandled(): void
    {
        $scheme = $this->makeScheme('post', ['title' => ['en' => '{{no.such.key}}']]);

        $this->assertSame('{{no.such.key}}', $scheme->title());
    }

    public function testPerLanguageTitleWithoutTheCurrentLanguageStillReturnsAString(): void
    {
        $scheme = $this->makeScheme('post', ['title' => ['it' => 'Articolo']]);

        $this->assertIsString($scheme->title());
    }

    public function testTitleIsComputedOnce(): void
    {
        $scheme = $this->makeScheme('post', ['title' => '{{greeting}}']);
        $first = $scheme->title();
        $this->translations->setCurrent('it');

        $this->assertSame($first, $scheme->title());
    }

    public function testOptionsAreExposed(): void
    {
        $scheme = $this->makeScheme('post', ['options' => ['num' => 2, 'nested' => ['key' => 'v']]]);

        $this->assertSame(2, $scheme->options()->get('num'));
        $this->assertSame('v', $scheme->options()->get('nested.key'));
    }

    public function testOptionsDefaultToEmpty(): void
    {
        $this->assertSame([], $this->makeScheme('post', [])->options()->toArray());
    }

    // fields()

    public function testFieldsAreBuiltFromTheDefinition(): void
    {
        $scheme = $this->makeScheme('post', ['fields' => [
            'title'   => ['type' => 'text', 'label' => 'Title'],
            'summary' => ['type' => 'text'],
        ]]);

        $fields = $scheme->fields();

        $this->assertSame(['title', 'summary'], $fields->keys());
        $this->assertSame('Title', $fields->get('title')->label());
        $this->assertSame('summary', $fields->get('summary')->label());
    }

    public function testFieldsAreNewInstancesOnEveryCall(): void
    {
        $scheme = $this->makeScheme('post', ['fields' => ['title' => ['type' => 'text']]]);

        $first = $scheme->fields();
        $first->get('title')->set('value', 'changed');
        $second = $scheme->fields();

        $this->assertNotSame($first, $second);
        $this->assertNotSame($first->get('title'), $second->get('title'));
        $this->assertSame('', $second->get('title')->value());
    }

    public function testFieldsBelongToTheReturnedCollection(): void
    {
        $fields = $this->makeScheme('post', ['fields' => ['title' => ['type' => 'text']]])->fields();

        $this->assertSame($fields, $fields->get('title')->parent());
    }

    public function testFieldDefaultsComeFromTheFieldTypeConfiguration(): void
    {
        $fields = $this->makeScheme('post', ['fields' => [
            'headline' => ['type' => 'headline'],
            'explicit' => ['type' => 'title', 'default' => 'Mine'],
        ]])->fields();

        $this->assertSame('Untitled', $fields->get('headline')->value());
        $this->assertSame('Mine', $fields->get('explicit')->value());
    }

    public function testDefaultLayoutHasASingleSectionWithAllTheFields(): void
    {
        $fields = $this->makeScheme('post', [
            'title'  => 'Post',
            'fields' => ['a' => ['type' => 'text'], 'b' => ['type' => 'text']],
        ])->fields();

        $sections = $fields->layout()->sections();

        $this->assertSame(['default'], $sections->keys());
        $this->assertSame('Post', $sections->get('default')->label());
        $this->assertSame(['a', 'b'], $sections->get('default')->get('fields'));
    }

    public function testDefaultLayoutSectionIsLabelledDefaultWithoutATitle(): void
    {
        $fields = $this->makeScheme('post', ['fields' => ['a' => ['type' => 'text']]])->fields();

        $this->assertSame('default', $fields->layout()->sections()->get('default')->label());
    }

    public function testCustomLayoutIsUsed(): void
    {
        $fields = $this->makeScheme('post', [
            'layout' => ['sections' => [
                'main' => ['label' => '{{section.main}}', 'fields' => ['a']],
            ]],
            'fields' => ['a' => ['type' => 'text']],
        ])->fields();

        $this->assertSame(['main'], $fields->layout()->sections()->keys());
        $this->assertSame('Main section', $fields->layout()->sections()->get('main')->label());
    }

    public function testLayoutIsTranslatedToTheCurrentLanguage(): void
    {
        $this->translations->setCurrent('it');

        $fields = $this->makeScheme('post', [
            'layout' => ['sections' => ['main' => ['label' => '{{section.main}}']]],
        ])->fields();

        $this->assertSame('Sezione principale', $fields->layout()->sections()->get('main')->label());
    }

    public function testSchemeWithoutFieldsProducesAnEmptyCollection(): void
    {
        $fields = $this->makeScheme('post', [])->fields();

        $this->assertCount(0, $fields);
    }

    // Extension

    public function testSchemeExtendsAnotherSchemeById(): void
    {
        $this->writeScheme('base', "title: Base\noptions:\n  inherited: true\n  overridden: base\nfields:\n  title:\n    type: text\n    label: Base title\n");
        $this->writeScheme('child', "extend: base\ntitle: Child\noptions:\n  overridden: child\nfields:\n  extra:\n    type: text\n");

        $child = $this->schemes->get('child');

        $this->assertSame('Child', $child->title());
        $this->assertTrue($child->options()->get('inherited'));
        $this->assertSame('child', $child->options()->get('overridden'));
        $this->assertSame(['title', 'extra'], $child->fields()->keys());
        $this->assertSame('Base title', $child->fields()->get('title')->label());
    }

    public function testChildFieldDefinitionsOverrideTheBaseOnesKey_by_Key(): void
    {
        $this->writeScheme('base', "fields:\n  title:\n    type: text\n    label: Base title\n    required: true\n");
        $this->writeScheme('child', "extend: base\nfields:\n  title:\n    label: Child title\n");

        $field = $this->schemes->get('child')->fields()->get('title');

        $this->assertSame('Child title', $field->label());
        $this->assertTrue($field->isRequired(), 'Properties not redefined by the child are kept.');
        $this->assertSame('text', $field->type());
    }

    public function testExtensionChainsAreFlattened(): void
    {
        $this->writeScheme('a', "options:\n  from: a\n  a: true\n");
        $this->writeScheme('b', "extend: a\noptions:\n  from: b\n  b: true\n");
        $this->writeScheme('c', "extend: b\noptions:\n  from: c\n");

        $options = $this->schemes->get('c')->options();

        $this->assertSame('c', $options->get('from'));
        $this->assertTrue($options->get('a'));
        $this->assertTrue($options->get('b'));
    }

    public function testListsAreConcatenatedWithTheBaseFirst(): void
    {
        $this->writeScheme('base', "options:\n  allowed: [a, b]\n");
        $this->writeScheme('child', "extend: base\noptions:\n  allowed: [c]\n");

        $this->assertSame(['a', 'b', 'c'], $this->schemes->get('child')->options()->get('allowed'));
    }

    public function testExtendingAnUnknownSchemeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scheme "ghost"');

        $this->makeScheme('child', ['extend' => 'ghost']);
    }

    public function testASchemeCannotExtendItself(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be extended by itself');

        $this->makeScheme('self', ['extend' => 'self']);
    }

    public function testASchemeCannotExtendItselfThroughAnInstanceEither(): void
    {
        $scheme = $this->makeScheme('self', []);

        $this->expectException(InvalidArgumentException::class);

        $scheme->extend($scheme);
    }

    public function testCircularExtensionIsDetected(): void
    {
        $this->writeScheme('a', "extend: b\n");
        $this->writeScheme('b', "extend: a\n");

        $this->expectException(RecursionException::class);
        $this->expectExceptionMessage('Recursion in the extension of the scheme');

        $this->schemes->get('a');
    }

    public function testLongerCircularExtensionIsDetected(): void
    {
        $this->writeScheme('a', "extend: b\n");
        $this->writeScheme('b', "extend: c\n");
        $this->writeScheme('c', "extend: a\n");

        $this->expectException(RecursionException::class);

        $this->schemes->get('a');
    }

    public function testFailedExtensionDoesNotPoisonLaterAttempts(): void
    {
        $this->writeScheme('a', "extend: b\n");
        $this->writeScheme('b', "extend: a\n");

        for ($i = 0; $i < 2; ++$i) {
            try {
                $this->schemes->get('a');
                $this->fail('Circular extension should be rejected.');
            } catch (RecursionException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->writeScheme('x', "title: X\n");
        $this->assertSame('X', $this->schemes->get('x')->title());
        $this->assertSame('x', $this->makeScheme('y', ['extend' => 'x'])->getExtendedScheme()?->id());
    }

    public function testFailedExtensionByUnknownIdDoesNotPoisonLaterAttempts(): void
    {
        try {
            $this->makeScheme('child', ['extend' => 'ghost']);
            $this->fail('Unknown scheme should be rejected.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->writeScheme('ghost', "title: Ghost\n");

        $this->assertSame('Ghost', $this->makeScheme('child', ['extend' => 'ghost'])->getExtendedScheme()?->title());
    }

    public function testExtendWithAnInstanceMergesItsData(): void
    {
        $base = $this->makeScheme('base', ['options' => ['inherited' => true]]);
        $child = $this->makeScheme('child', ['options' => ['own' => true]]);

        $child->extend($base);

        $this->assertTrue($child->toArray()['options']['inherited']);
        $this->assertTrue($child->toArray()['options']['own']);
    }

    public function testExtendWithDataKeepsTheOwnDefinitions(): void
    {
        $scheme = $this->makeScheme('child', ['title' => 'Own', 'options' => ['a' => 'own']]);

        $scheme->extendWith(['title' => 'Other', 'options' => ['a' => 'other', 'b' => 'other']]);

        $this->assertSame('Own', $scheme->toArray()['title']);
        $this->assertSame(['a' => 'own', 'b' => 'other'], $scheme->toArray()['options']);
    }

    public function testExtensionDoesNotModifyTheBaseScheme(): void
    {
        $this->writeScheme('base', "options:\n  a: base\n");
        $this->writeScheme('child', "extend: base\noptions:\n  a: child\n  b: child\n");

        $this->schemes->get('child');

        $this->assertSame(['a' => 'base'], $this->schemes->get('base')->options()->toArray());
    }

    public function testGetExtendedScheme(): void
    {
        $this->writeScheme('base', "title: Base\n");
        $this->writeScheme('child', "extend: base\n");

        $this->assertNull($this->schemes->get('base')->getExtendedScheme());
        $this->assertSame($this->schemes->get('base'), $this->schemes->get('child')->getExtendedScheme());
    }

    public function testExtendsSchemeFollowsTheWholeChain(): void
    {
        $this->writeScheme('a', "title: A\n");
        $this->writeScheme('b', "extend: a\n");
        $this->writeScheme('c', "extend: b\n");
        $this->writeScheme('unrelated', "title: U\n");

        $c = $this->schemes->get('c');

        $this->assertTrue($c->extendsScheme('b'));
        $this->assertTrue($c->extendsScheme('a'));
        $this->assertTrue($c->extendsScheme($this->schemes->get('a')));
        $this->assertFalse($c->extendsScheme('unrelated'));
        $this->assertFalse($c->extendsScheme('c'), 'A scheme does not extend itself.');
        $this->assertFalse($this->schemes->get('a')->extendsScheme('c'));
    }

    public function testExtendsSchemeIsFalseWithoutExtension(): void
    {
        $this->assertFalse($this->makeScheme('plain', [])->extendsScheme('anything'));
    }

    public function testToArrayReturnsTheMergedDefinition(): void
    {
        $this->writeScheme('base', "title: Base\nfields:\n  a:\n    type: text\n");
        $this->writeScheme('child', "extend: base\ntitle: Child\n");

        $data = $this->schemes->get('child')->toArray();

        $this->assertSame('Child', $data['title']);
        $this->assertSame('base', $data['extend']);
        $this->assertArrayHasKey('a', $data['fields']);
    }
}
