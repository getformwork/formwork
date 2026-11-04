<?php

namespace Formwork\Tests\Unit\Panel\Events;

use Formwork\Events\Event;
use Formwork\Http\Request;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Panel\Events\PanelLoggedOutEvent;
use Formwork\Panel\Events\PanelNavigationLoadedEvent;
use Formwork\Panel\Navigation\NavigationItem;
use Formwork\Panel\Navigation\NavigationItemCollection;
use Formwork\Tests\TestCase;
use Formwork\Translations\Translation;
use Formwork\Users\User;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PanelLoggedInEvent::class)]
#[CoversClass(PanelLoggedOutEvent::class)]
#[CoversClass(PanelNavigationLoadedEvent::class)]
final class PanelEventsTest extends TestCase
{
    public function testLoggedInEventExposesUserAndRequest(): void
    {
        $user = $this->createStub(User::class);
        $request = new Request([], [], [], [], []);

        $event = new PanelLoggedInEvent($user, $request);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('panelLoggedIn', $event->name());
        $this->assertSame($user, $event->user());
        $this->assertSame($request, $event->request());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testLoggedOutEventExposesUser(): void
    {
        $user = $this->createStub(User::class);

        $event = new PanelLoggedOutEvent($user);

        $this->assertSame('panelLoggedOut', $event->name());
        $this->assertSame($user, $event->user());
        $this->assertSame(['user' => $user], $event->data());
    }

    public function testNavigationLoadedEventExposesNavigationAndTranslation(): void
    {
        $navigation = new NavigationItemCollection();
        $translation = new Translation('en', []);

        $event = new PanelNavigationLoadedEvent($navigation, $translation);

        $this->assertSame('panelNavigationLoaded', $event->name());
        $this->assertSame($navigation, $event->navigation());
        $this->assertSame($translation, $event->translation());
    }

    public function testListenersCanModifyTheNavigationInPlace(): void
    {
        $navigation = new NavigationItemCollection();
        $event = new PanelNavigationLoadedEvent($navigation, new Translation('en', []));

        $event->navigation()->set('custom', NavigationItem::fromArray(['id' => 'custom', 'label' => 'Custom', 'uri' => '/custom/']));

        $this->assertTrue($navigation->has('custom'));
    }
}
