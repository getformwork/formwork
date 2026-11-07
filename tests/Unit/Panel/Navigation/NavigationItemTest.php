<?php

namespace Formwork\Tests\Unit\Panel\Navigation;

use Formwork\Panel\Navigation\NavigationItem;
use Formwork\Panel\Navigation\NavigationItemCollection;
use Formwork\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NavigationItem::class)]
#[CoversClass(NavigationItemCollection::class)]
final class NavigationItemTest extends TestCase
{
    public function testRequiredPropertiesAreExposed(): void
    {
        $item = new NavigationItem('dashboard', ['label' => 'Dashboard', 'uri' => '/dashboard/']);

        $this->assertSame('dashboard', $item->id());
        $this->assertSame('Dashboard', $item->label());
        $this->assertSame('/dashboard/', $item->uri());
    }

    public function testOptionalPropertiesHaveDefaults(): void
    {
        $item = new NavigationItem('dashboard', ['label' => 'Dashboard', 'uri' => '/dashboard/']);

        $this->assertNull($item->permissions());
        $this->assertNull($item->badge());
        $this->assertNull($item->icon());
        $this->assertTrue($item->visible());
        $this->assertNull($item->children());
    }

    public function testOptionalPropertiesAreExposed(): void
    {
        $item = new NavigationItem('pages', [
            'label'       => 'Pages',
            'uri'         => '/pages/',
            'permissions' => 'pages.view',
            'badge'       => '3',
            'icon'        => 'pages',
            'visible'     => false,
        ]);

        $this->assertSame('pages.view', $item->permissions());
        $this->assertSame('3', $item->badge());
        $this->assertSame('pages', $item->icon());
        $this->assertFalse($item->visible());
    }

    public function testChildrenAreConvertedToACollection(): void
    {
        $item = new NavigationItem('tools', [
            'label'    => 'Tools',
            'uri'      => '/tools/',
            'children' => [
                'backups' => ['label' => 'Backups', 'uri' => '/tools/backups/'],
                'updates' => ['label' => 'Updates', 'uri' => '/tools/updates/'],
            ],
        ]);

        $children = $item->children();

        $this->assertInstanceOf(NavigationItemCollection::class, $children);
        $this->assertCount(2, $children);
        $this->assertInstanceOf(NavigationItem::class, $updates = $children->get('updates'));
        $this->assertSame('/tools/updates/', $updates->uri());
        $this->assertSame('updates', $updates->id());
    }

    public function testToArrayIncludesTheId(): void
    {
        $item = new NavigationItem('dashboard', ['label' => 'Dashboard', 'uri' => '/dashboard/']);

        $this->assertSame(['label' => 'Dashboard', 'uri' => '/dashboard/', 'id' => 'dashboard'], $item->toArray());
    }

    public function testToArrayConvertsChildrenRecursively(): void
    {
        $item = new NavigationItem('tools', [
            'label'    => 'Tools',
            'uri'      => '/tools/',
            'children' => ['backups' => ['label' => 'Backups', 'uri' => '/tools/backups/']],
        ]);

        $this->assertSame([
            'label'    => 'Tools',
            'uri'      => '/tools/',
            'children' => ['backups' => ['label' => 'Backups', 'uri' => '/tools/backups/', 'id' => 'backups']],
            'id'       => 'tools',
        ], $item->toArray());
    }

    public function testFromArrayUsesTheIdKey(): void
    {
        $item = NavigationItem::fromArray(['id' => 'users', 'label' => 'Users', 'uri' => '/users/']);

        $this->assertSame('users', $item->id());
        $this->assertSame('Users', $item->label());
    }

    public function testSerializationRoundTripIsStable(): void
    {
        $item = new NavigationItem('tools', [
            'label'    => 'Tools',
            'uri'      => '/tools/',
            'icon'     => 'tools',
            'children' => ['backups' => ['label' => 'Backups', 'uri' => '/tools/backups/', 'visible' => false]],
        ]);

        $copy = NavigationItem::fromArray($item->toArray());

        $this->assertSame($item->toArray(), $copy->toArray());
        $this->assertInstanceOf(NavigationItem::class, $backups = $copy->children()?->get('backups'));
        $this->assertFalse($backups->visible());
    }

    public function testCollectionIsCreatedFromAnAssociativeArray(): void
    {
        $collection = NavigationItemCollection::fromArray([
            'dashboard' => ['label' => 'Dashboard', 'uri' => '/dashboard/'],
            'pages'     => ['label' => 'Pages', 'uri' => '/pages/'],
        ]);

        $this->assertSame(['dashboard', 'pages'], array_keys($collection->toArray()));
        $this->assertInstanceOf(NavigationItem::class, $pages = $collection->get('pages'));
        $this->assertSame('pages', $pages->id());
    }

    public function testCollectionSerializationRoundTripIsStable(): void
    {
        $data = [
            'dashboard' => ['label' => 'Dashboard', 'uri' => '/dashboard/', 'id' => 'dashboard'],
            'pages'     => ['label' => 'Pages', 'uri' => '/pages/', 'id' => 'pages'],
        ];

        $this->assertSame($data, NavigationItemCollection::fromArray($data)->toArray());
    }

    public function testEmptyCollectionCanBeCreated(): void
    {
        $this->assertCount(0, NavigationItemCollection::fromArray([]));
        $this->assertSame([], NavigationItemCollection::fromArray([])->toArray());
    }
}
