<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Feature;

use Dskripchenko\LaravelAdmin\Testing\Concerns\ActsAsAdmin;
use Dskripchenko\LaravelAdminHealth\Tests\TestCase;

/**
 * The permission group, its labels and the menu group are registered once at
 * boot; each request must still see them in its own locale. The application
 * boots in one locale and the requests ask for both, so a string translated
 * at boot would come back in the boot locale for one of them.
 */
final class RequestLocaleTest extends TestCase
{
    use ActsAsAdmin;

    private const GROUP = ['ru' => 'Системные', 'en' => 'System'];

    /** @var array<string, array{ru: string, en: string}> */
    private const PERMISSIONS = [
        'admin.system.health.view' => ['ru' => 'Health-check: просмотр', 'en' => 'Health checks: view'],
        'admin.system.health.run' => ['ru' => 'Health-check: ручной запуск', 'en' => 'Health checks: run manually'],
    ];

    /** @var list<string> */
    private const MENU_ITEMS = [
        'system-health-results',
    ];

    public function test_permission_group_and_labels_follow_the_request_locale(): void
    {
        $this->actingAsSuperAdmin();

        foreach (['ru', 'en', 'ru'] as $locale) {
            $groups = collect($this->withHeader('Accept-Language', $locale)
                ->getJson('/api/admin/system/permissions')
                ->assertOk()
                ->json('payload.groups'));

            foreach (self::PERMISSIONS as $key => $labels) {
                $group = $groups->first(
                    static fn (array $g): bool => collect($g['items'])->contains('key', $key),
                );
                $this->assertNotNull($group, "$locale: $key");
                $this->assertSame(self::GROUP[$locale], $group['name'], "$locale: $key");
                $this->assertSame(
                    $labels[$locale],
                    collect($group['items'])->firstWhere('key', $key)['label'] ?? null,
                    "$locale: $key",
                );
            }
        }
    }

    public function test_menu_group_follows_the_request_locale(): void
    {
        $this->actingAsSuperAdmin();

        foreach (['ru', 'en', 'ru'] as $locale) {
            $items = collect($this->withHeader('Accept-Language', $locale)
                ->getJson('/api/admin/system/menu')
                ->assertOk()
                ->json('payload.items'));

            foreach (self::MENU_ITEMS as $key) {
                $this->assertSame(self::GROUP[$locale], $items->firstWhere('key', $key)['group'] ?? null, "$locale: $key");
            }
        }
    }
}
