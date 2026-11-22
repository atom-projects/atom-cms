<?php

use App\Models\Miscellaneous\WebsitePermission;
use App\Models\User;
use App\Services\PermissionsService;

function duskNavigationDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

test('Dusk renders one complete public menu for guests', function () {
    installHotel();
    setSetting('theme', 'dusk');

    $response = $this->get(route('article.index'))->assertOk();
    $document = duskNavigationDocument($response->getContent());

    expect($document->query('//nav'))->toHaveCount(1);
    expect($document->query('//nav//a[@href="" or @href="#"]'))->toHaveCount(0);
    expect($document->query('//nav//form'))->toHaveCount(0);
    expect($document->query('//nav//button[normalize-space(.)="Community" or normalize-space(.)="Home"]/svg[@aria-hidden="true"]'))->toHaveCount(2);
    expect($document->query('//a[@href="' . route('help-center.ticket.index') . '"]'))->toHaveCount(0);

    $links = [];

    foreach ($document->query('//nav//a[normalize-space(.) != ""]') as $link) {
        $links[trim($link->textContent)] = $link->getAttribute('href');
    }

    expect($links)->toBe([
        'Staff' => route('staff.index'),
        'Teams' => route('teams.index'),
        'Team applications' => route('team-applications.index'),
        'Staff applications' => route('staff-applications.index'),
        'Rare values' => route('values.index'),
        'Help center' => route('help-center.index'),
        'Photos' => route('photos.index'),
        'Leaderboard' => route('leaderboard.index'),
        'News' => route('article.index'),
        'Store' => route('shop.index'),
        'Home' => route('welcome'),
        'Login' => route('login'),
        'Register' => route('register'),
    ]);
});

test('Dusk dropdowns animate entry without a cancellable exit transition', function () {
    installHotel();
    setSetting('theme', 'dusk');

    $response = $this->get(route('article.index'))->assertOk();
    $document = duskNavigationDocument($response->getContent());
    $transitions = $document->query('//*[self::nav or contains(@class, "sub-header")]//*[@x-show="open"]/@*[starts-with(name(), "x-transition")]');

    expect($transitions)->toHaveCount(3);

    foreach ($transitions as $transition) {
        expect($transition->nodeName)->toStartWith('x-transition:enter');
    }
});

test('Dusk renders personal destinations and one CSRF protected logout form when signed in', function () {
    installHotel();
    setSetting('theme', 'dusk');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.account.show'))->assertOk();
    $document = duskNavigationDocument($response->getContent());

    expect($document->query('//nav'))->toHaveCount(1);
    expect($document->query('//nav//a[@href="" or @href="#"]'))->toHaveCount(0);
    expect($document->query('//nav//*[@x-show="open"]//a[@href="' . route('me.show') . '"]'))->toHaveCount(0);
    expect($document->query('//nav//button[normalize-space(.)="Home"]'))->toHaveCount(1);

    $links = [];

    foreach ($document->query('//nav//a[normalize-space(.) != ""]') as $link) {
        $links[trim($link->textContent)] = $link->getAttribute('href');
    }

    expect($links)->toBe([
        'Staff' => route('staff.index'),
        'Teams' => route('teams.index'),
        'Team applications' => route('team-applications.index'),
        'Staff applications' => route('staff-applications.index'),
        'Rare values' => route('values.index'),
        'Help center' => route('help-center.index'),
        'Photos' => route('photos.index'),
        'Leaderboard' => route('leaderboard.index'),
        'News' => route('article.index'),
        'Store' => route('shop.index'),
        'Home' => route('me.show'),
        'My Home' => route('home.show', $user->username),
        'Badge Drawer' => route('draw-badge'),
        'Account settings' => route('settings.account.show'),
    ]);

    $forms = $document->query('//form[@action="' . route('logout') . '"]');

    expect($forms)->toHaveCount(1);
    expect($forms->item(0)->getAttribute('method'))->toBe('POST');
    expect($document->query('//nav//button[@type="submit" and @form="' . $forms->item(0)->getAttribute('id') . '"]'))->toHaveCount(1);
    expect($document->query('//form[@action="' . route('logout') . '"]//input[@name="_token" and @value="' . csrf_token() . '"]'))->toHaveCount(1);
});

test('Dusk shows ticket and housekeeping shortcuts independently', function (bool $tickets, bool $housekeeping) {
    installHotel();
    setSetting('theme', 'dusk');
    setSetting('housekeeping_url', '/housekeeping/admin');

    foreach (['manage_website_tickets' => $tickets, 'housekeeping_access' => $housekeeping] as $permission => $allowed) {
        WebsitePermission::query()->where('permission', $permission)->update(['min_rank' => $allowed ? 1 : 7]);
    }

    app(PermissionsService::class)->refresh();
    $user = User::factory()->create(['rank' => 1]);

    $response = $this->actingAs($user)->get(route('settings.account.show'))->assertOk();
    $document = duskNavigationDocument($response->getContent());
    $ticketLinks = $document->query('//a[@href="' . route('help-center.ticket.index') . '"]');
    $housekeepingLinks = $document->query('//a[@href="/housekeeping/admin"]');

    expect($ticketLinks)->toHaveCount($tickets ? 1 : 0);
    expect($housekeepingLinks)->toHaveCount($housekeeping ? 1 : 0);
    expect($document->query('//nav//a[@href="' . route('help-center.ticket.index') . '"]'))->toHaveCount(0);

    expect($document->query('//div[contains(@class, "sub-header")]//a[@href="' . route('help-center.ticket.index') . '" and @aria-label="' . __('Open tickets') . '"]'))->toHaveCount($tickets ? 1 : 0);
    expect($document->query('//a[@href="' . route('help-center.ticket.index') . '"]/following-sibling::*[1][self::a[@href="/housekeeping/admin"]]'))->toHaveCount($tickets && $housekeeping ? 1 : 0);
})->with([
    'neither permission' => [false, false],
    'tickets only' => [true, false],
    'housekeeping only' => [false, true],
    'both permissions' => [true, true],
]);

test('Dusk highlights the navigation entry owning the current destination', function (string $route, string $label) {
    installHotel();
    setSetting('theme', 'dusk');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route($route))->assertOk();
    $document = duskNavigationDocument($response->getContent());
    $activeEntries = $document->query('//nav//*[contains(concat(" ", normalize-space(@class), " "), " active ")]');

    expect($activeEntries)->toHaveCount(1);
    expect(trim($activeEntries->item(0)->textContent))->toStartWith($label);
})->with([
    'staff' => ['staff.index', 'Community'],
    'team applications' => ['team-applications.index', 'Community'],
    'leaderboard' => ['leaderboard.index', 'Leaderboard'],
    'news' => ['article.index', 'News'],
    'store' => ['shop.index', 'Store'],
    'settings' => ['settings.account.show', 'Home'],
    'badge drawer' => ['draw-badge', 'Home'],
]);
